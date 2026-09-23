<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->index();
            $table->string('barcode')->nullable()->index();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();

            // مبالغ به صورت Integer (کوچک‌ترین واحد پول، مثلاً ریال) برای جلوگیری از خطای Floating Point (بخش ۳۵)
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('compare_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('weight', 8, 2)->nullable(); // kg
            $table->string('dimensions')->nullable(); // "L x W x H cm"

            $table->string('status')->default('draft')->index(); // draft, active, inactive
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->boolean('is_bestseller')->default(false)->index();

            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->boolean('is_thumbnail')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('provider')->default('file'); // file, youtube, aparat
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_videos');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('products');
    }
};
