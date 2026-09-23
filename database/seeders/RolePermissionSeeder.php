<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Products' => ['products.manage', 'products.view'],
            'Orders' => ['orders.manage', 'orders.view'],
            'Users' => ['users.manage', 'users.view'],
            'Inventory' => ['inventory.manage', 'inventory.view'],
            'Discounts' => ['discounts.manage', 'coupons.manage'],
            'Settings' => ['settings.manage'],
            'Reports' => ['reports.view'],
            'Categories' => ['categories.manage'],
            'Reviews' => ['reviews.moderate'],
        ];

        foreach ($groups as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::firstOrCreate(['slug' => $slug], [
                    'name' => $slug,
                    'group' => $group,
                ]);
            }
        }

        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'دسترسی کامل به تمام بخش‌ها']
        );
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Admin: همه‌چیز به‌جز تنظیمات حساس سیستم (Assumption مستند طبق بخش ۴۶)
        $admin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin->permissions()->sync(
            Permission::where('slug', '!=', 'settings.manage')->pluck('id')
        );

        $manager = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager']);
        $manager->permissions()->sync(
            Permission::whereIn('slug', [
                'products.manage', 'products.view', 'orders.manage', 'orders.view',
                'inventory.manage', 'inventory.view', 'categories.manage',
                'discounts.manage', 'coupons.manage', 'reports.view',
            ])->pluck('id')
        );

        $editor = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $editor->permissions()->sync(
            Permission::whereIn('slug', ['products.manage', 'products.view', 'categories.manage'])->pluck('id')
        );

        $warehouse = Role::firstOrCreate(['slug' => 'warehouse'], ['name' => 'Warehouse']);
        $warehouse->permissions()->sync(
            Permission::whereIn('slug', ['inventory.manage', 'inventory.view', 'orders.view'])->pluck('id')
        );

        $support = Role::firstOrCreate(['slug' => 'support'], ['name' => 'Support']);
        $support->permissions()->sync(
            Permission::whereIn('slug', ['orders.view', 'reviews.moderate'])->pluck('id')
        );

        Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer', 'description' => 'مشتری فروشگاه']);
    }
}
