<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** بخش ۵۲: انتخاب مشتری بین پیشتاز(۱)/ویژه(۳) در Checkout، برای اینکه هنگام ثبت مرسوله همان سرویس واقعی استفاده شود. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('shipping_service_type_id')->default(1)->after('shipping_heropost_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_service_type_id');
        });
    }
};
