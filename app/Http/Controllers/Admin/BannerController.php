<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BannerController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $banners = Banner::orderBy('sort_order')->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.banners.form', ['banner' => new Banner()]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }
        if ($request->hasFile('image_mobile')) {
            $data['image_mobile'] = $request->file('image_mobile')->store('banners', 'public');
        }

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('order_success', 'بنر ایجاد شد.');
    }

    public function edit(Banner $banner): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.banners.form', compact('banner'));
    }

    public function update(StoreBannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('image_mobile')) {
            $data['image_mobile'] = $request->file('image_mobile')->store('banners', 'public');
        } else {
            unset($data['image_mobile']);
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('order_success', 'بنر ویرایش شد.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('order_success', 'بنر حذف شد.');
    }
}
