# User Management System

This document describes the complete CRUD system for user management implemented in the Laravel application.

## Features

### ✅ Complete CRUD Operations
- **Create**: Add new users with roles and permissions (not working 404 message showing why)
- **Read**: View user list with search and filtering, individual user details
- **Update**: Edit user information, roles, and permissions  (showing null exception in errors.name)
- **Delete**: Remove users (with protection for own account)

### ✅ Role-Based Access Control (RBAC)
- Uses **Spatie Laravel Permission** package
- Predefined roles: Super Admin, Admin, Manager, User, Guest
- Granular permissions for different operations
- Middleware-based permission checking

### ✅ Advanced Features
- **Search & Filtering**: Search by name, email, phone; filter by status and role
- **Status Management**: Active, Inactive, Suspended user states
- **Role Assignment**: Multiple roles per user
- **Permission Management**: Direct permission assignment
- **Pagination**: Efficient data loading
- **Responsive Design**: Mobile-friendly interface

### ✅ User Experience
- **Toastr Notifications**: Success/error messages
- **Form Validation**: Client and server-side validation
- **Loading States**: Submit buttons with spinners
- **Clean UI**: Modern, intuitive interface

## Installation & Setup

### 1. Install Dependencies
```bash
composer require spatie/laravel-permission
npm install
```

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Seed Roles & Permissions
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

## Database Structure

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - Unique email address
- `phone` - Phone number (optional)
- `password` - Hashed password
- `status` - User status (active/inactive/suspended)
- `email_verified_at` - Email verification timestamp
- `created_at`, `updated_at` - Timestamps

### Roles & Permissions (Spatie Package)
- `roles` table - User roles
- `permissions` table - System permissions
- `model_has_roles` table - User-role relationships
- `model_has_permissions` table - User-permission relationships

## Routes

### User Management Routes
```php
// View users (requires 'view users' permission)
GET /users                    → users.index
GET /users/{user}            → users.show

// Create users (requires 'create users' permission)
GET /users/create            → users.create
POST /users                 → users.store

// Edit users (requires 'edit users' permission)
GET /users/{user}/edit      → users.edit
PUT /users/{user}           → users.update
POST /users/{user}/toggle-status → users.toggle-status

// Delete users (requires 'delete users' permission)
DELETE /users/{user}        → users.destroy
```

## Permissions

### User Management Permissions
- `view users` - View user list and details
- `create users` - Create new users
- `edit users` - Edit existing users
- `delete users` - Delete users
- `manage user roles` - Assign/remove user roles
- `manage user permissions` - Assign/remove user permissions

### Other System Permissions
- Phone number management
- SIP trunk management
- Call management
- System administration

## Roles & Access Levels

### Super Admin
- **Access**: All permissions
- **Use Case**: System administrators

### Admin
- **Access**: User management, phone numbers, SIP trunks, calls
- **Use Case**: Department managers

### Manager
- **Access**: Limited user management, phone numbers, SIP trunks, calls
- **Use Case**: Team leaders

### User
- **Access**: Basic phone number viewing, making calls
- **Use Case**: Regular users

### Guest
- **Access**: View phone numbers only
- **Use Case**: Limited access users

## Frontend Components

### Vue.js Components
- `Users/Index.vue` - User list with search/filter
- `Users/Create.vue` - Create user form
- `Users/Edit.vue` - Edit user form
- `Users/Show.vue` - User details view

### Features
- **Responsive Design**: Mobile-first approach
- **Form Validation**: Real-time error display
- **Toastr Notifications**: User feedback
- **Loading States**: Visual feedback during operations
- **Search & Filter**: Advanced data filtering

## Usage Examples

### Creating a New User
1. Navigate to Users → Add User
2. Fill in required fields (email, password)
3. Select optional fields (name, phone, status)
4. Assign roles and permissions
5. Submit form

### Managing User Roles
1. Edit existing user
2. Check/uncheck desired roles
3. Save changes
4. User immediately has new permissions

### Filtering Users
1. Use search box for name/email/phone
2. Filter by status (active/inactive/suspended)
3. Filter by role
4. Clear filters to reset

## Security Features

### Permission Middleware
- Route-level permission checking
- Automatic access control
- Graceful error handling

### Data Protection
- Password hashing
- CSRF protection
- Input validation
- SQL injection prevention

### User Safety
- Cannot delete own account
- Status-based access control
- Audit trail via timestamps

## Customization

### Adding New Permissions
1. Update `RoleAndPermissionSeeder`
2. Add permission to desired roles
3. Run seeder: `php artisan db:seed --class=RoleAndPermissionSeeder`

### Adding New Roles
1. Modify seeder file
2. Define role permissions
3. Run seeder

### Custom Validation Rules
- Modify `UserController` validation
- Add custom validation methods
- Update frontend validation

## Troubleshooting

### Common Issues
1. **Routes not loading**: Clear route cache (`php artisan route:clear`)
2. **Permissions not working**: Clear permission cache (`php artisan permission:cache-reset`)
3. **Validation errors**: Check form data and validation rules

### Debug Commands
```bash
# List all routes
php artisan route:list

# Check user permissions
php artisan tinker
$user = User::find(1);
$user->getAllPermissions();

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## API Integration

### RESTful Endpoints
All user management operations are available via RESTful API endpoints with proper authentication and authorization.

### Response Format
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "user": {...}
  }
}
```

## Performance Considerations

### Database Optimization
- Eager loading of relationships
- Pagination for large datasets
- Indexed foreign keys

### Caching
- Permission caching via Spatie
- Route caching in production
- Query result caching

## Future Enhancements

### Planned Features
- User activity logging
- Bulk user operations
- Advanced reporting
- User import/export
- Two-factor authentication
- User groups/teams

### Scalability
- Database sharding support
- Microservice architecture
- API rate limiting
- Advanced caching strategies

## Support

For technical support or feature requests, please refer to the project documentation or contact the development team.

---

**Last Updated**: August 25, 2025
**Version**: 1.0.0
**Laravel Version**: 12.x
**Spatie Permission**: 6.21.0
