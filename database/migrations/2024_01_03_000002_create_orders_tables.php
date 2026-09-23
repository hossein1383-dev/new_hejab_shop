<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // اطلاعات مهمان (Guest Checkout — بخش ۲۹.۱: همیشه در دسترس باشد)
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();

            // Snapshot آدرس ارسال در لحظه سفارش — تغییر بعدی آدرس ذخیره‌شده
            // کاربر نباید سفارش‌های قبلی را تغییر دهد (Data Integrity، بخش ۳).
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->string('shipping_province');
            $table->string('shipping_city');
            $table->string('shipping_address_line');
            $table->string('shipping_postal_code', 20)->nullable();

            $table->string('shipping_method')->default('standard');

            // همه مبالغ Integer (کوچک‌ترین واحد پول) — بدون Floating Point (بخش ۳۵)
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('total');

            $table->string('status')->default('pending_payment')->index();
            // pending_payment, paid, processing, preparing, shipped, delivered, cancelled, refunded, returned

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot در لحظه سفارش — اگر محصول بعداً تغییر/حذف شود، تاریخچه سفارش دست‌نخورده بماند
            $table->string('product_name');
            $table->string('sku');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = تغییر خودکار سیستم
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
