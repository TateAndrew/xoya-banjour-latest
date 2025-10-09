# Role & Permission Quick Reference Card

## 🚀 Common Operations

### Check User Permission
```php
// Method 1: Using can()
if (Auth::user()->can('edit users')) {
    // Allowed
}

// Method 2: Using Gate
use Illuminate\Support\Facades\Gate;
if (Gate::allows('edit users')) {
    // Allowed
}

// Method 3: In controller
$this->authorize('edit users');
```

### Check User Role
```php
if (Auth::user()->hasRole('admin')) {
    // User is admin
}

// Check multiple roles (any)
if (Auth::user()->hasAnyRole(['admin', 'manager'])) {
    // Has at least one role
}

// Check multiple roles (all)
if (Auth::user()->hasAllRoles(['admin', 'editor'])) {
    // Has all roles
}
```

### Assign Role to User
```php
use App\Models\User;

$user = User::find(1);

// Assign single role
$user->assignRole('admin');

// Assign multiple roles
$user->assignRole(['admin', 'editor']);

// Sync roles (replace all)
$user->syncRoles(['admin']);

// Remove role
$user->removeRole('admin');
```

### Give Permission to User
```php
// Direct permission to user
$user->givePermissionTo('edit posts');

// Multiple permissions
$user->givePermissionTo(['edit posts', 'delete posts']);

// Sync permissions
$user->syncPermissions(['edit posts']);

// Revoke permission
$user->revokePermissionTo('delete posts');
```

### Create Role
```php
use Spatie\Permission\Models\Role;

$role = Role::create(['name' => 'editor']);

// With permissions
$role->givePermissionTo(['edit posts', 'delete posts']);
```

### Create Permission
```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'edit posts']);
```

### Protect Routes
```php
// Single permission
Route::get('/admin', function() {
    //
})->middleware('permission:access admin panel');

// Multiple permissions (all required)
Route::get('/posts/edit', function() {
    //
})->middleware('permission:edit posts|publish posts');

// Any permission
Route::get('/posts', function() {
    //
})->middleware('permission:edit posts,delete posts');

// By role
Route::get('/admin', function() {
    //
})->middleware('role:admin');

// Multiple roles (any)
Route::get('/admin', function() {
    //
})->middleware('role:admin|super admin');

// Group routes
Route::group(['middleware' => ['role:admin']], function () {
    Route::get('/admin/users', 'UserController@index');
    Route::get('/admin/roles', 'RoleController@index');
});
```

### Blade Directives
```blade
{{-- Check role --}}
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

{{-- Check permission --}}
@can('edit posts')
    <a href="/posts/edit">Edit</a>
@endcan

{{-- Check any role --}}
@hasanyrole('admin|editor')
    <p>You are admin or editor</p>
@endhasanyrole

{{-- Check any permission --}}
@canany(['edit posts', 'delete posts'])
    <p>You can edit or delete</p>
@endcanany
```

### Vue/Inertia Usage
```vue
<template>
  <div>
    <!-- Check permission -->
    <button v-if="can('edit users')">
      Edit User
    </button>
    
    <!-- Check role -->
    <div v-if="hasRole('admin')">
      Admin Panel
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const can = (permission) => {
  return user.permissions?.includes(permission);
};

const hasRole = (role) => {
  return user.roles?.includes(role);
};
</script>
```

## 📋 Web Interface URLs

| Resource | URL |
|----------|-----|
| Roles List | `/roles` |
| Create Role | `/roles/create` |
| Edit Role | `/roles/{id}/edit` |
| View Role | `/roles/{id}` |
| Permissions List | `/permissions` |
| Create Permission | `/permissions/create` |
| Edit Permission | `/permissions/{id}/edit` |
| View Permission | `/permissions/{id}` |
| Users List | `/users` |
| Create User | `/users/create` |
| Edit User | `/users/{id}/edit` |

## 🎯 Permission Naming Convention

Use lowercase with spaces:

✅ **Good**
- view users
- create posts
- edit articles
- delete comments
- manage settings

❌ **Bad**
- ViewUsers
- CREATE_POSTS
- editArticles
- Delete-Comments
- manageSettings

## 🔄 Cache Commands

```bash
# Clear permission cache
php artisan permission:cache-reset

# Or in code
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

## 🗂️ Get User Data

```php
$user = User::find(1);

// Get all roles
$roles = $user->roles;
$roleNames = $user->getRoleNames(); // Collection of names

// Get all permissions (including via roles)
$permissions = $user->getAllPermissions();

// Get direct permissions only
$directPermissions = $user->permissions;

// Get specific role
$adminRole = $user->roles()->where('name', 'admin')->first();
```

## 🎨 Default Roles & Their Permissions

### Super Admin
✅ All permissions

### Admin
- User management (all)
- Phone number management (all)
- SIP trunk management (all)
- Call management (all)
- Access admin panel
- View system logs

### Manager
- View, create, edit users
- Phone number management (no delete)
- SIP trunk management
- Call management

### User
- View phone numbers
- Make calls
- View call history
- Manage conferences

### Guest
- View phone numbers only

## 🛠️ Tinker Commands

```bash
php artisan tinker
```

```php
// Create role
$role = Role::create(['name' => 'editor']);

// Create permission
$permission = Permission::create(['name' => 'edit posts']);

// Assign to user
$user = User::find(1);
$user->assignRole('editor');
$user->givePermissionTo('edit posts');

// Check
$user->hasRole('editor'); // true
$user->can('edit posts'); // true

// See all
Role::all();
Permission::all();
```

## 📞 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Permissions not updating | `php artisan permission:cache-reset` |
| "Role does not exist" | Check spelling, run seeder |
| User can't access route | Check middleware, verify permissions |
| Seeder errors | Clear cache: `php artisan config:clear` |

## 💡 Pro Tips

1. **Always check permissions, not roles** in your code:
   ```php
   // Good
   if ($user->can('edit posts')) { }
   
   // Less flexible
   if ($user->hasRole('admin')) { }
   ```

2. **Use middleware** to protect routes instead of checking in controllers

3. **Name permissions consistently** using "verb noun" format

4. **Use roles for general access**, permissions for specific actions

5. **Don't forget to clear cache** after changing permissions

---

## 📚 Full Documentation

For complete guide: See `ROLE_PERMISSION_GUIDE.md`
For implementation details: See `ROLE_PERMISSION_IMPLEMENTATION_SUMMARY.md`

---

**Happy coding! 🚀**

