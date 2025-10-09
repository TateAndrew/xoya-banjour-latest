# Role and Permission Management Guide

## Overview

This application uses **Spatie Laravel Permission** package to manage roles and permissions. This guide will help you understand how to use the role and permission system effectively.

## Table of Contents

1. [Installation & Setup](#installation--setup)
2. [Basic Concepts](#basic-concepts)
3. [Managing Roles](#managing-roles)
4. [Managing Permissions](#managing-permissions)
5. [Assigning Roles to Users](#assigning-roles-to-users)
6. [Checking Permissions in Code](#checking-permissions-in-code)
7. [Using Middleware](#using-middleware)
8. [Blade/Vue Directives](#bladevue-directives)
9. [API Reference](#api-reference)

---

## Installation & Setup

The Spatie Laravel Permission package is already installed and configured. To set up initial roles and permissions:

### Run the Seeder

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

This will create the following default roles:
- **Super Admin** - Full system access
- **Admin** - Administrative access
- **Manager** - Management level access
- **User** - Standard user access
- **Guest** - Limited read-only access

---

## Basic Concepts

### Roles
Roles are groups of permissions. Examples: Admin, Manager, User, Guest.

### Permissions
Permissions are individual actions that can be performed. Examples: "view users", "create posts", "delete comments".

### Guard
Guards define different authentication systems. By default, we use the 'web' guard.

---

## Managing Roles

### Via Web Interface

1. Navigate to `/roles` in your browser
2. Click "Create Role" to add a new role
3. Assign permissions by checking the boxes
4. Click "Save" to create the role

### Via Code

```php
use Spatie\Permission\Models\Role;

// Create a role
$role = Role::create(['name' => 'editor']);

// Assign permissions to role
$role->givePermissionTo('edit articles');
$role->givePermissionTo(['edit articles', 'delete articles']);

// Sync permissions (removes old, adds new)
$role->syncPermissions(['edit articles', 'delete articles']);

// Remove permission
$role->revokePermissionTo('delete articles');

// Get all permissions for a role
$permissions = $role->permissions;

// Delete a role
$role->delete();
```

---

## Managing Permissions

### Via Web Interface

1. Navigate to `/permissions` in your browser
2. Click "Create Permission" to add a new permission
3. Enter the permission name (use lowercase with spaces)
4. Click "Save"

### Via Code

```php
use Spatie\Permission\Models\Permission;

// Create a permission
$permission = Permission::create(['name' => 'edit articles']);

// Create multiple permissions
Permission::create(['name' => 'view articles']);
Permission::create(['name' => 'delete articles']);
Permission::create(['name' => 'publish articles']);

// Get all permissions
$permissions = Permission::all();

// Delete a permission
$permission->delete();
```

### Permission Naming Convention

Use lowercase with spaces for permission names:
- ✅ Good: "view users", "edit posts", "delete comments"
- ❌ Bad: "ViewUsers", "EDIT_POSTS", "deleteComments"

---

## Assigning Roles to Users

### Via Web Interface

1. Navigate to `/users`
2. Click "Edit" on a user
3. Select roles from the checkboxes
4. Click "Update"

### Via Code

```php
use App\Models\User;

$user = User::find(1);

// Assign a role
$user->assignRole('admin');

// Assign multiple roles
$user->assignRole(['admin', 'editor']);

// Sync roles (removes old, adds new)
$user->syncRoles(['admin']);

// Remove role
$user->removeRole('admin');

// Check if user has role
if ($user->hasRole('admin')) {
    // User is an admin
}

// Check if user has any of the roles
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User has at least one of these roles
}

// Check if user has all roles
if ($user->hasAllRoles(['admin', 'editor'])) {
    // User has all these roles
}

// Get all user roles
$roles = $user->roles;
```

### Assigning Direct Permissions to Users

```php
// Give permission directly to user
$user->givePermissionTo('edit articles');

// Give multiple permissions
$user->givePermissionTo(['edit articles', 'delete articles']);

// Sync permissions
$user->syncPermissions(['edit articles']);

// Revoke permission
$user->revokePermissionTo('edit articles');

// Check if user has permission
if ($user->hasPermissionTo('edit articles')) {
    // User can edit articles
}

// Get all permissions (including via roles)
$permissions = $user->getAllPermissions();

// Get direct permissions only
$directPermissions = $user->permissions;
```

---

## Checking Permissions in Code

### In Controllers

```php
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function edit(Post $post)
    {
        // Check permission
        if (!Auth::user()->can('edit posts')) {
            abort(403, 'Unauthorized');
        }
        
        // Or use authorization
        $this->authorize('edit posts');
        
        // Your code here
    }
}
```

### Using Gate Facade

```php
use Illuminate\Support\Facades\Gate;

// Check permission
if (Gate::allows('edit posts')) {
    // User can edit posts
}

if (Gate::denies('delete posts')) {
    // User cannot delete posts
}

// Check for specific user
if (Gate::forUser($user)->allows('edit posts')) {
    // This user can edit posts
}
```

### Using User Model Methods

```php
// Check single permission
if ($user->can('edit posts')) {
    // User can edit posts
}

// Check multiple permissions (any)
if ($user->hasAnyPermission(['edit posts', 'delete posts'])) {
    // User has at least one permission
}

// Check multiple permissions (all)
if ($user->hasAllPermissions(['edit posts', 'publish posts'])) {
    // User has all permissions
}

// Via role
if ($user->hasRole('admin')) {
    // User is admin
}
```

---

## Using Middleware

### In Routes

```php
// Require permission
Route::get('/posts/edit', function () {
    // Only users with 'edit posts' permission
})->middleware('permission:edit posts');

// Require multiple permissions (all)
Route::get('/posts/publish', function () {
    // User must have all these permissions
})->middleware('permission:edit posts|publish posts');

// Require any permission
Route::get('/posts/manage', function () {
    // User must have at least one permission
})->middleware('permission:edit posts,delete posts');

// Require role
Route::get('/admin', function () {
    // Only admins
})->middleware('role:admin');

// Require multiple roles (any)
Route::get('/admin', function () {
    // Admins or super admins
})->middleware('role:admin|super admin');

// Require role and permission
Route::group(['middleware' => ['role:admin', 'permission:edit posts']], function () {
    // Routes here
});
```

### In Controllers

```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:edit posts')->only(['edit', 'update']);
        $this->middleware('permission:delete posts')->only('destroy');
        $this->middleware('role:admin')->except('index');
    }
}
```

---

## Blade/Vue Directives

### In Blade Templates

```blade
@role('admin')
    <!-- Only admins can see this -->
    <a href="/admin">Admin Panel</a>
@endrole

@hasrole('admin')
    <!-- Same as @role -->
@endhasrole

@hasanyrole('admin|editor')
    <!-- Admins and editors can see this -->
@endhasanyrole

@hasallroles('admin|editor')
    <!-- Only users with both roles can see this -->
@endhasallroles

@can('edit posts')
    <!-- Users with edit posts permission -->
    <a href="/posts/edit">Edit</a>
@endcan

@canany(['edit posts', 'delete posts'])
    <!-- Users with any of these permissions -->
@endcanany
```

### In Vue Components (Inertia.js)

```vue
<template>
  <div>
    <!-- Check if user has permission -->
    <button v-if="$page.props.auth.user.permissions.includes('edit posts')">
      Edit Post
    </button>
    
    <!-- Check if user has role -->
    <div v-if="$page.props.auth.user.roles.includes('admin')">
      Admin Panel
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

// Check permission
const canEditPosts = user.permissions?.includes('edit posts');

// Check role
const isAdmin = user.roles?.includes('admin');
</script>
```

---

## API Reference

### Role Management Endpoints

```
GET    /roles                      - List all roles
GET    /roles/create               - Show create role form
POST   /roles                      - Create a new role
GET    /roles/{role}               - Show role details
GET    /roles/{role}/edit          - Edit role form
PUT    /roles/{role}               - Update role
DELETE /roles/{role}               - Delete role
POST   /roles/{role}/assign-permissions - Assign permissions to role
```

### Permission Management Endpoints

```
GET    /permissions                - List all permissions
GET    /permissions/create         - Show create permission form
POST   /permissions                - Create a new permission
GET    /permissions/{permission}   - Show permission details
GET    /permissions/{permission}/edit - Edit permission form
PUT    /permissions/{permission}   - Update permission
DELETE /permissions/{permission}   - Delete permission
```

### User Role/Permission Endpoints

```
POST   /users/{user}/assign-role        - Assign role to user
POST   /users/{user}/remove-role        - Remove role from user
POST   /users/{user}/sync-roles         - Sync user roles
POST   /users/{user}/give-permission    - Give permission to user
POST   /users/{user}/revoke-permission  - Revoke permission from user
```

---

## Best Practices

1. **Use Roles for General Access**: Assign roles to users (Admin, Manager, User)
2. **Use Permissions for Specific Actions**: Define granular permissions (edit posts, delete users)
3. **Name Permissions Consistently**: Use format "verb noun" (view users, create posts)
4. **Cache Permissions**: Spatie automatically caches permissions for 24 hours
5. **Don't Over-Complicate**: Start simple and add complexity as needed
6. **Use Middleware**: Protect routes with role/permission middleware
7. **Check Permissions, Not Roles**: In code, check `$user->can('edit posts')` instead of `$user->hasRole('admin')`

---

## Default Permissions

The system comes with these pre-configured permissions:

### User Management
- view users
- create users
- edit users
- delete users
- manage user roles
- manage user permissions

### Phone Number Management
- view phone numbers
- create phone numbers
- edit phone numbers
- delete phone numbers
- purchase phone numbers

### SIP Trunk Management
- view sip trunks
- create sip trunks
- edit sip trunks
- delete sip trunks
- activate sip trunks
- deactivate sip trunks

### Call Management
- make calls
- view call history
- manage conferences

### System Administration
- access admin panel
- manage system settings
- view system logs

---

## Troubleshooting

### Cache Issues

If permissions aren't updating:

```bash
php artisan permission:cache-reset
```

Or in code:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### Common Errors

**"Role does not exist"**
- Make sure you've run the seeder
- Check the role name (case-sensitive)

**"User already has role"**
- This is normal, use `syncRoles()` instead of `assignRole()`

**"Permission not found"**
- Ensure the permission exists in the database
- Check spelling and case

---

## Support

For more information, visit:
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [GitHub Repository](https://github.com/spatie/laravel-permission)

---

## License

This implementation uses the Spatie Laravel Permission package, which is open-sourced software licensed under the MIT license.

