<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جایگزینی کامل احراز هویت ایمیل/رمز عبور با شماره‌تلفن/OTP (طبق تصمیم صریح
 * کارفرما). رمز عبور دیگر معنا ندارد و حذف می‌شود؛ ایمیل اختیاری می‌شود.
 *
 * نکته فنی مهم: در SQLite، قبل از Drop کردن ستونی که روی آن Unique Index
 * هست، باید خودِ Index را جدا Drop کرد، وگرنه SQLite با خطای داخلی مواجه
 * می‌شود (چون DROP COLUMN آن را خودکار پاک نمی‌کند). به‌جای ->change()
 * (که نیاز به doctrine/dbal دارد) از Drop+Add ساده استفاده شده (بخش ۴۵).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password', 'email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->after('name');
            $table->string('password')->after('phone_verified_at');
        });
    }
};
