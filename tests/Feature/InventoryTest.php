<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_movement_increases_stock(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 100);

        $this->assertEquals(100, $service->availableQuantity($product, null));
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => 'purchase', 'quantity' => 100]);
    }

    public function test_sale_movement_decreases_stock(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 10);
        $service->recordMovement($product, null, 'sale', -3);

        $this->assertEquals(7, $service->availableQuantity($product, null));
    }

    public function test_cannot_reduce_stock_below_zero(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 5);

        $this->expectException(\DomainException::class);
        $service->recordMovement($product, null, 'sale', -10);
    }

    public function test_reserve_reduces_available_quantity_without_changing_physical_stock(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 20);
        $service->reserve($product, null, 5);

        $this->assertEquals(15, $service->availableQuantity($product, null));
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 20, 'reserved_quantity' => 5]);
    }

    public function test_cannot_reserve_more_than_available(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 5);

        $this->expectException(\DomainException::class);
        $service->reserve($product, null, 10);
    }

    public function test_release_restores_available_quantity(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);

        $service->recordMovement($product, null, 'purchase', 20);
        $service->reserve($product, null, 5);
        $service->release($product, null, 5);

        $this->assertEquals(20, $service->availableQuantity($product, null));
    }
}
