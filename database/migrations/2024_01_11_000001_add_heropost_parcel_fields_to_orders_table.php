<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بخش ۵۲: ثبت مرسوله در هیروپست بعد از پرداخت — شناسه داخلی مرسوله (برای
 * لغو بعدی) و کد رهگیری قابل‌نمایش به مشتری، هردو جدا نگه داشته می‌شوند
 * چون ممکن است یکی برای API لازم باشد و دیگری فقط برای نمایش.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('heropost_parcel_id')->nullable()->after('shipping_method');
            $table->string('heropost_tracking_code')->nullable()->after('heropost_parcel_id');
            $table->unsignedInteger('shipping_heropost_city_id')->nullable()->after('shipping_city');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['heropost_parcel_id', 'heropost_tracking_code', 'shipping_heropost_city_id']);
        });
    }
};
