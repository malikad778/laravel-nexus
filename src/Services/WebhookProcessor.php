<?php

namespace Malikad778\LaravelNexus\Services;

use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\Events\InventoryUpdated;
use Malikad778\LaravelNexus\Events\WebhookReceived;
use Malikad778\LaravelNexus\Facades\Nexus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookProcessor
{
    public function process(string $channel, Request $request): void
    {
        try {
            $driver = Nexus::driver($channel);
        } catch (Exception $e) {
            Log::error("WebhookProcessing failed. Channel [{$channel}] driver not found.", ['exception' => $e->getMessage()]);
            return; // We cannot process if driver doesn't exist.
        }

        $topic = $driver->extractWebhookTopic($request);
        $rawPayload = $request->getContent();

        // 1. Log the webhook
        $logId = DB::table('nexus_webhook_logs')->insertGetId([
            'channel' => $channel,
            'topic' => $topic,
            'payload' => $rawPayload,
            'headers' => json_encode($request->headers->all()),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Dispatch generic WebhookReceived (System Level)
        WebhookReceived::dispatch(
            $channel,
            json_decode($rawPayload, true) ?? [],
            $request->headers->all(),
            $logId
        );

        // 3. Parse and Dispatch InventoryUpdated (Domain Level)
        try {
            $updateDto = $driver->parseWebhookPayload($request);

            try {
                $product = $driver->fetchProduct($updateDto->remoteId);
                $previousQuantity = $product->quantity ?? 0;
            } catch (Exception $e) {
                // If we can't fetch the full product details, we log it and halt the event dispatch.
                // Alternatively, we could dispatch without full product context or requeue.
                // For safety, we won't invent product data.
                throw new Exception("Unable to fetch product details for remote ID: {$updateDto->remoteId}. Original Error: {$e->getMessage()}", 0, $e);
            }

            InventoryUpdated::dispatch(
                $channel,
                $product,
                $previousQuantity,
                $updateDto->quantity
            );

            DB::table('nexus_webhook_logs')->where('id', $logId)->update(['status' => 'processed']);

        } catch (Exception $e) {
            DB::table('nexus_webhook_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'exception' => $e->getMessage(),
            ]);

            // We log the error but don't rethrow to avoid failing the 200 OK response 
            // the controller needs to send back to the webhook provider.
            Log::error("WebhookProcessing failed for log ID [{$logId}].", ['exception' => $e->getMessage()]);
        }
    }
}
