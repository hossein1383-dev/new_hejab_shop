<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // مثلاً Color, Size
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // مثلاً Black, White, M, L
            $table->string('slug');
            $table->string('meta')->nullable(); // مثلاً کد رنگ Hex برای Color
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->unsignedBigInteger('price')->nullable(); // null یعنی از قیمت Product اصلی استفاده شود
            $table->unsignedBigInteger('compare_price')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('image')->nullable();
            $table->string('status')->default('active')->index(); // active, inactive
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status']);
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_variant_id', 'attribute_value_id'], 'pvv_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
