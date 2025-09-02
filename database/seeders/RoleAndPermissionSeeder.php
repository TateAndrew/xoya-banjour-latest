<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage user roles',
            'manage user permissions',
            
            // Phone number management
            'view phone numbers',
            'create phone numbers',
            'edit phone numbers',
            'delete phone numbers',
            'purchase phone numbers',
            
            // SIP trunk management
            'view sip trunks',
            'create sip trunks',
            'edit sip trunks',
            'delete sip trunks',
            'activate sip trunks',
            'deactivate sip trunks',
            
            // Call management
            'make calls',
            'view call history',
            'manage conferences',
            
            // System administration
            'access admin panel',
            'manage system settings',
            'view system logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $roles = [
            'Super Admin' => $permissions,
            'Admin' => [
                'view users', 'create users', 'edit users', 'delete users',
                'view phone numbers', 'create phone numbers', 'edit phone numbers', 'delete phone numbers', 'purchase phone numbers',
                'view sip trunks', 'create sip trunks', 'edit sip trunks', 'delete sip trunks', 'activate sip trunks', 'deactivate sip trunks',
                'make calls', 'view call history', 'manage conferences',
                'access admin panel', 'view system logs'
            ],
            'Manager' => [
                'view users', 'create users', 'edit users',
                'view phone numbers', 'create phone numbers', 'edit phone numbers', 'purchase phone numbers',
                'view sip trunks', 'create sip trunks', 'edit sip trunks', 'activate sip trunks', 'deactivate sip trunks',
                'make calls', 'view call history', 'manage conferences'
            ],
            'User' => [
                'view phone numbers', 'make calls', 'view call history', 'manage conferences'
            ],
            'Guest' => [
                'view phone numbers'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        // Assign Super Admin role to the first user (if exists)
        $firstUser = \App\Models\User::first();
        if ($firstUser) {
            $firstUser->assignRole('Super Admin');
        }
    }
}
