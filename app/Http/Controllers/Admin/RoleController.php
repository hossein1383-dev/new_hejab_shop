<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function edit(Request $request, Role $role): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $permissionsByGroup = Permission::orderBy('group')->get()->groupBy('group');
        $assignedIds = $role->permissions->pluck('id')->all();

        return view('admin.roles.edit', compact('role', 'permissionsByGroup', 'assignedIds'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        // ابرمدیر همیشه همه Permission ها را دارد؛ از پنل قابل تغییر نیست
        // تا سیستم هرگز بدون هیچ Super Admin کامل باقی نماند (بخش ۴۴: Security > Convenience).
        if ($role->slug === 'super-admin') {
            return back()->with('order_error', 'دسترسی‌های Super Admin از پنل قابل تغییر نیست.');
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('order_success', 'دسترسی‌های نقش به‌روزرسانی شد.');
    }
}
