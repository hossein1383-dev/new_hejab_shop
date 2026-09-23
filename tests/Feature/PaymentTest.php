<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(int $price = 100000, int $qty = 2, int $stock = 10)
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => $price]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', $stock);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-payment-' . uniqid());
        $cartService->addItem($cart, $product, null, $qty);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        return [$order, $product];
    }

    public function test_successful_payment_marks_order_paid_and_confirms_inventory_deduction(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 2, stock: 10);
        $inventoryService = app(InventoryService::class);
        $paymentService = app(PaymentService::class);

        $this->assertEquals(8, $inventoryService->availableQuantity($product, null)); // ۲ رزرو شده

        $initResult = $paymentService->initiate($order);
        $payment = $paymentService->handleCallback([
            'reference' => $initResult['reference'],
            'outcome' => 'success',
            'amount' => $order->total,
        ]);

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('paid', $order->fresh()->status);

        // بعد از پرداخت موفق: رزرو آزاد و موجودی واقعی کسر شده (نه فقط رزرو)
        $product->refresh();
        $this->assertEquals(8, $inventoryService->availableQuantity($product, null));
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 8, 'reserved_quantity' => 0]);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity' => -2]);
    }

    public function test_user_can_pay_order_with_sufficient_wallet_balance(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 2, stock: 10);
        $user = $order->user;
        app(\App\Services\WalletService::class)->credit($user, 300000);

        $payment = app(PaymentService::class)->payWithWallet($order, $user);

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('wallet', $payment->gateway);
        $this->assertEquals('paid', $order->fresh()->status);
        // total شامل هزینه ارسال هم هست (نه فقط قیمت محصولات)، پس به‌جای
        // فرض کردن یک عدد ثابت، از خودِ total واقعی سفارش استفاده می‌شود.
        $this->assertEquals(300000 - $order->total, app(\App\Services\WalletService::class)->getOrCreate($user)->balance);
        $this->assertEquals(8, app(InventoryService::class)->availableQuantity($product, null)); // 10 - 2 قطعی کسر شد
    }

    public function test_wallet_payment_fails_with_insufficient_balance_and_leaves_order_pending(): void
    {
        [$order] = $this->createPendingOrder(price: 100000, qty: 2, stock: 10);
        $user = $order->user;
        app(\App\Services\WalletService::class)->credit($user, 50000); // کمتر از ۲۰۰,۰۰۰ مبلغ سفارش

        try {
            app(PaymentService::class)->payWithWallet($order, $user);
            $this->fail('انتظار می‌رفت به‌خاطر موجودی ناکافی Exception بدهد.');
        } catch (\DomainException) {
            // انتظار می‌رود
        }

        $this->assertEquals('pending_payment', $order->fresh()->status);
        $this->assertEquals(50000, app(\App\Services\WalletService::class)->getOrCreate($user)->balance); // دست‌نخورده
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_wallet_payment_rejects_order_belonging_to_another_user(): void
    {
        [$order] = $this->createPendingOrder(price: 100000, qty: 2, stock: 10);
        $intruder = User::factory()->create();
        app(\App\Services\WalletService::class)->credit($intruder, 500000);

        $this->expectException(\DomainException::class);
        app(PaymentService::class)->payWithWallet($order, $intruder);
    }

    public function test_can_checkout_and_pay_entirely_with_wallet_via_http(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);
        app(\App\Services\WalletService::class)->credit($user, 500000);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-wallet-checkout');
        $cartService->addItem($cart, $product, null, 1);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        // total شامل هزینه ارسال هم می‌شود (نه فقط ۱۰۰,۰۰۰ قیمت محصول)
        $order = \App\Models\Order::latest()->firstOrFail();
        $this->assertEquals(500000 - $order->total, app(\App\Services\WalletService::class)->getOrCreate($user)->balance);
    }

    public function test_failed_payment_cancels_order_and_releases_reservation(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 3, stock: 10);
        $inventoryService = app(InventoryService::class);
        $paymentService = app(PaymentService::class);

        $initResult = $paymentService->initiate($order);
        $paymentService->handleCallback([
            'reference' => $initResult['reference'],
            'outcome' => 'failed',
            'amount' => $order->total,
        ]);

        $this->assertEquals('cancelled', $order->fresh()->status);

        // رزرو باید کامل آزاد شده باشد (موجودی کامل قابل فروش برگردد)
        $this->assertEquals(10, $inventoryService->availableQuantity($product, null));
    }

    public function test_duplicate_callback_is_idempotent_and_does_not_double_deduct_inventory(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 1, stock: 10);
        $inventoryService = app(InventoryService::class);
        $paymentService = app(PaymentService::class);

        $initResult = $paymentService->initiate($order);
        $callbackData = [
            'reference' => $initResult['reference'],
            'outcome' => 'success',
            'amount' => $order->total,
        ];

        $paymentService->handleCallback($callbackData);
        $paymentService->handleCallback($callbackData); // Callback تکراری (بخش ۱۴)

        $product->refresh();
        $this->assertEquals(9, $inventoryService->availableQuantity($product, null));
        $this->assertDatabaseCount('inventory_movements', 2); // فقط purchase + یک sale، نه دوتا sale
    }

    public function test_amount_mismatch_fails_payment_and_releases_reservation(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 1, stock: 10);
        $inventoryService = app(InventoryService::class);
        $paymentService = app(PaymentService::class);

        $initResult = $paymentService->initiate($order);
        $paymentService->handleCallback([
            'reference' => $initResult['reference'],
            'outcome' => 'success',
            'amount' => $order->total - 1000, // مبلغ دستکاری‌شده — کمتر از مبلغ واقعی
        ]);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(10, $inventoryService->availableQuantity($product, null));
    }

    public function test_cannot_initiate_payment_for_already_paid_order(): void
    {
        [$order] = $this->createPendingOrder();
        $paymentService = app(PaymentService::class);

        $initResult = $paymentService->initiate($order);
        $paymentService->handleCallback([
            'reference' => $initResult['reference'],
            'outcome' => 'success',
            'amount' => $order->total,
        ]);

        $this->expectException(\DomainException::class);
        $paymentService->initiate($order->fresh());
    }
}
