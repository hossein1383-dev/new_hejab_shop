<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->string('link_url')->nullable();
            $table->string('position')->default('home_hero'); // محل نمایش
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['position', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
