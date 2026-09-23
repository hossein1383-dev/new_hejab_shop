<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // fake, zarinpal, ... (بخش ۱۴: معماری مستقل از Gateway خاص)
            $table->string('reference')->unique()->nullable(); // شناسه پرداخت نزد Gateway
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending')->index(); // pending, paid, failed, expired
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // initiate, callback, verify
            $table->string('status'); // success, failed
            $table->text('raw_response')->nullable(); // برای Audit — هرگز شامل Secret نباشد (بخش ۳۶)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
