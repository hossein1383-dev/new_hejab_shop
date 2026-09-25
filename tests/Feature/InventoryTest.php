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

    public function test_total_available_quantity_sums_across_all_variants(): void
    {
        $product = Product::factory()->create();
        $variant1 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant2 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $service = app(InventoryService::class);

        $service->recordMovement($product, $variant1, 'purchase', 10);
        $service->recordMovement($product, $variant2, 'purchase', 15);

        $this->assertEquals(25, $service->totalAvailableQuantity($product));
    }

    public function test_total_available_quantity_updates_when_new_variant_added(): void
    {
        $product = Product::factory()->create();
        $variant1 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $service = app(InventoryService::class);
        $service->recordMovement($product, $variant1, 'purchase', 10);

        $this->assertEquals(10, $service->totalAvailableQuantity($product));

        $variant2 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $service->recordMovement($product, $variant2, 'purchase', 20);

        $this->assertEquals(30, $service->totalAvailableQuantity($product));
    }

    public function test_total_available_quantity_reflects_reservations(): void
    {
        $product = Product::factory()->create();
        $variant = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $service = app(InventoryService::class);
        $service->recordMovement($product, $variant, 'purchase', 10);
        $service->reserve($product, $variant, 3);

        $this->assertEquals(7, $service->totalAvailableQuantity($product));
    }

    public function test_total_available_quantity_with_no_variants_uses_base_inventory(): void
    {
        $product = Product::factory()->create();
        $service = app(InventoryService::class);
        $service->recordMovement($product, null, 'purchase', 50);

        $this->assertEquals(50, $service->totalAvailableQuantity($product));
    }
}
