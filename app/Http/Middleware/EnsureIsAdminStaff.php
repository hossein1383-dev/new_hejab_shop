<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * دسترسی به کل پنل مدیریت را به کاربران دارای یکی از نقش‌های Staff محدود
 * می‌کند (بخش ۴). بررسی دقیق‌تر هر Permission داخل خود Controller/Policy
 * انجام می‌شود؛ این فقط دروازه ورودی است.
 */
class EnsureIsAdminStaff
{
    private const STAFF_ROLES = ['super-admin', 'admin', 'manager', 'editor', 'warehouse', 'support'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->roles->pluck('slug')->intersect(self::STAFF_ROLES)->isEmpty()) {
            abort(403, 'شما دسترسی به پنل مدیریت را ندارید.');
        }

        return $next($request);
    }
}
