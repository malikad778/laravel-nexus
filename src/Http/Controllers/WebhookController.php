<?php

namespace Malikad778\LaravelNexus\Http\Controllers;

use Malikad778\LaravelNexus\Http\Middleware\VerifyNexusWebhookSignature;
use Malikad778\LaravelNexus\Services\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __construct(protected WebhookProcessor $processor)
    {
        // Apply middleware.
        // Note: In Laravel 11 package, middleware registration might differ,
        // but this standard approach works for most.
        $this->middleware(VerifyNexusWebhookSignature::class);
    }

    public function handle(Request $request, string $channel)
    {
        $this->processor->process($channel, $request);

        return response()->json(['status' => 'received']);
    }
}
