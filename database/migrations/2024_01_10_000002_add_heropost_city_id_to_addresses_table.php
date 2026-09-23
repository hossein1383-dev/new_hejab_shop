<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // nullable چون آدرس‌های قبلی هنوز این شناسه را ندارند؛ فرم جدید
            // همیشه پرش می‌کند، ولی کد باید Fallback به نرخ ثابت را برای
            // آدرس‌های قدیمی‌تر (بدون این شناسه) پشتیبانی کند.
            $table->unsignedInteger('heropost_city_id')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('heropost_city_id');
        });
    }
};
