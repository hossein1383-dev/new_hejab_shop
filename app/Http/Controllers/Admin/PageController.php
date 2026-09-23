<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Page\StorePageRequest;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $pages = Page::latest()->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.pages.form', ['page' => new Page()]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        Page::create($request->validated());

        return redirect()->route('admin.pages.index')->with('order_success', 'صفحه ایجاد شد.');
    }

    public function edit(Page $page): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.pages.form', compact('page'));
    }

    public function update(StorePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        return redirect()->route('admin.pages.index')->with('order_success', 'صفحه ویرایش شد.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $page->delete();

        return redirect()->route('admin.pages.index')->with('order_success', 'صفحه حذف شد.');
    }
}
