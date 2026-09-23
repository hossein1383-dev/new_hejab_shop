<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کش محلی لیست شهرهای هیروپست — بخش ۱۵. برای استعلام قیمت پستی به یک
 * ToCityID عددی نیاز داریم (نه متن آزاد شهر)، پس این لیست را یک‌بار (با
 * دستور php artisan heropost:sync-cities) از API آنها می‌گیریم و محلی
 * نگه می‌داریم — نه اینکه در فرم آدرس هر بار به API آنها وصل شویم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heropost_cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('heropost_city_id')->unique();
            $table->string('name');
            $table->string('province_name');
            $table->unsignedInteger('province_code')->nullable();
            $table->timestamps();

            $table->index('province_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heropost_cities');
    }
};
