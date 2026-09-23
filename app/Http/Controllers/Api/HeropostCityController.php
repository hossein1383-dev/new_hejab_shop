<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeropostCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * برای پرکردن Dropdown شهر در فرم آدرس (پروفایل و Checkout) بر اساس
 * استان انتخاب‌شده — بدون این، باید کل لیست شهرهای ایران یک‌جا در HTML
 * صفحه embed می‌شد.
 */
class HeropostCityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cities = HeropostCity::query()
            ->when($request->filled('province'), fn ($q) => $q->where('province_name', $request->input('province')))
            ->orderBy('name')
            ->get(['heropost_city_id as id', 'name']);

        return response()->json(['success' => true, 'data' => $cities]);
    }
}
