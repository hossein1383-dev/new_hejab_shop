<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** شارژ کیف پول از طریق درگاه پرداخت — بخش ۱۸. جدا از Payment چون به هیچ Order ای وابسته نیست. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // تومان
            $table->string('reference')->unique();
            $table->string('gateway')->default('fake');
            $table->string('status')->default('pending')->index(); // pending, paid, failed
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
