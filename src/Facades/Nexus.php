<?php

namespace Malikad778\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Malikad778\LaravelNexus\Contracts\InventoryDriver channel(string $name = null)
 * @method static \Malikad778\LaravelNexus\Contracts\InventoryDriver driver(string $name = null)
 * @method static \Malikad778\LaravelNexus\InventoryManager context(array $context)
 * @method static \Malikad778\LaravelNexus\Builders\CatalogSyncBuilder catalog(mixed $products = null)
 * @method static \Malikad778\LaravelNexus\Builders\ProductSyncBuilder product(mixed $product)
 * @method static \Malikad778\LaravelNexus\Builders\BatchBuilder batch(string $name = null)
 *
 * @see \Malikad778\LaravelNexus\InventoryManager
 */
class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}

