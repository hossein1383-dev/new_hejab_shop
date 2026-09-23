<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * چون هردو تصویر بنر (دسکتاپ/موبایل) اختیاری شدند، ستون image دیتابیس هم
 * باید nullable شود. از Drop+Add به‌جای ->nullable()->change() استفاده شده
 * تا نیازی به پکیج doctrine/dbal نباشد (همان الگوی Migration های قبلی).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('image')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('image')->after('title');
        });
    }
};
