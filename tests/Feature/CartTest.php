<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphaned_cart_item_from_deleted_product_is_removed_automatically(): void
    {
        $product = Product::factory()->create();
        app(\App\Services\InventoryService::class)->recordMovement($product, null, 'purchase', 5);
        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getOrCreateCart(null, 'session-orphan-test');
        $cartService->addItem($cart, $product, null, 1);

        $product->delete(); // Soft Delete — شبیه‌سازی حذف محصول از پنل درحالی‌که در سبد کسی است

        $cart = $cartService->withDetails($cart->fresh());

        $this->assertCount(0, $cart->items);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cannot_add_product_with_variants_to_cart_without_selecting_variant(): void
    {
        $product = Product::factory()->create();
        $attribute = \App\Models\Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $value = \App\Models\AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'قرمز', 'slug' => 'red']);
        $variant = $product->variants()->create(['sku' => 'CART-TEST-RED', 'status' => 'active']);
        $variant->attributeValues()->sync([$value->id]);

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('product_variant_id');
    }

    public function test_can_add_product_with_variants_when_variant_is_selected(): void
    {
        $product = Product::factory()->create();
        $attribute = \App\Models\Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $value = \App\Models\AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'قرمز', 'slug' => 'red']);
        $variant = $product->variants()->create(['sku' => 'CART-TEST-RED-2', 'status' => 'active']);
        $variant->attributeValues()->sync([$value->id]);
        app(\App\Services\InventoryService::class)->recordMovement($product, $variant, 'purchase', 5);

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_product_card_hides_quick_add_button_when_product_has_variants(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['status' => 'active']);
        $attribute = \App\Models\Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $value = \App\Models\AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'قرمز', 'slug' => 'red']);
        $variant = $product->variants()->create(['sku' => 'CARD-TEST-RED', 'status' => 'active']);
        $variant->attributeValues()->sync([$value->id]);

        $response = $this->get('/products');

        $response->assertOk()
            ->assertDontSee('افزودن به سبد')
            ->assertSee('مشاهده محصول');
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $product = Product::factory()->create(['price' => 150000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.items_count', 2);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 2);

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.items_count', 2); // Cap شده روی موجودی واقعی
    }

    public function test_cannot_add_item_with_zero_stock(): void
    {
        $product = Product::factory()->create();
        // موجودی صفر (هیچ Movementی ثبت نشده)

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_update_and_remove_cart_item(): void
    {
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart(null, 'test-session-update');
        $cart = $cartService->addItem($cart, $product, null, 3);
        $itemId = $cart->items->first()->id;

        $cart = $cartService->updateQuantity($cart, $itemId, 1);
        $this->assertEquals(1, $cart->itemsCount());

        $cart = $cartService->removeItem($cart, $itemId);
        $this->assertEquals(0, $cart->itemsCount());
    }

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $product = Product::factory()->create(['price' => 200000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $guestSessionId = 'guest-session-merge-test';
        $guestCart = $cartService->getOrCreateCart(null, $guestSessionId);
        $cartService->addItem($guestCart, $product, null, 2);

        $user = User::factory()->create();

        // این دقیقاً همان متدی است که AuthController::login هنگام ورود موفق صدا می‌زند.
        $cartService->mergeGuestCartIntoUser($user, $guestSessionId);

        $userCart = $cartService->getOrCreateCart($user, 'irrelevant-after-login');

        $this->assertEquals(2, $userCart->itemsCount());
        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'status' => 'active']);
        $this->assertDatabaseHas('carts', ['session_id' => $guestSessionId, 'status' => 'converted']);
    }
}