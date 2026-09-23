<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تصویر دسکتاپ و موبایل بنر جدا می‌شود — چون نسبت ابعاد صفحه‌نمایش لپ‌تاپ
 * (خیلی عریض) و گوشی (باریک و بلند) آنقدر متفاوت است که یک عکس نمی‌تواند
 * روی هر دو خوب دربیاید بدون افتادن سوژه اصلی از قاب.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('image_mobile')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image_mobile');
        });
    }
};
