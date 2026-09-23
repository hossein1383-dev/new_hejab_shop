<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.view') || $request->user()->hasPermission('users.manage'), 403);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('email', 'like', '%' . $request->input('search') . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'status' => ['required', 'in:active,inactive,banned'],
        ]);

        $user->update(['status' => $data['status']]);
        $user->roles()->sync($data['roles'] ?? []);

        return redirect()->route('admin.users.index')->with('order_success', 'اطلاعات کاربر به‌روزرسانی شد.');
    }
}
