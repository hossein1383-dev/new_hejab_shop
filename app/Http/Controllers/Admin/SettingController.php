<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const EDITABLE_KEYS = [
        'site_name', 'site_description', 'contact_email', 'contact_phone', 'contact_address',
        'instagram_url', 'telegram_url', 'whatsapp_url', 'eitaa_url',
        'working_days', 'working_hours',
        'enamad_id', 'enamad_code',
        'heropost_sender_name', 'heropost_sender_mobile', 'heropost_sender_address', 'heropost_sender_city_id',
    ];

    public function __construct(private readonly SettingService $settingService)
    {
    }

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $settings = $this->settingService->all();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'telegram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'eitaa_url' => ['nullable', 'url', 'max:255'],
            'working_days' => ['nullable', 'string', 'max:100'],
            'working_hours' => ['nullable', 'string', 'max:255'],
            'enamad_id' => ['nullable', 'string', 'max:50'],
            'enamad_code' => ['nullable', 'string', 'max:255'],
            'heropost_sender_name' => ['nullable', 'string', 'max:255'],
            'heropost_sender_mobile' => ['nullable', 'string', 'max:20'],
            'heropost_sender_address' => ['nullable', 'string', 'max:500'],
            'heropost_sender_city_id' => ['nullable', 'integer'],
        ]);

        $this->settingService->setMany($data);

        \Illuminate\Support\Facades\Cache::forget('footer.data');
        \Illuminate\Support\Facades\Cache::forget('site-name');

        return back()->with('order_success', 'تنظیمات ذخیره شد.');
    }
}
