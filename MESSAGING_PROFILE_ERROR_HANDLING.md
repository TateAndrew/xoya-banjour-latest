# Messaging Profile Error Handling Implementation

## Overview
This document describes the error handling and success message implementation for the Messaging Profiles feature.

## What Was Implemented

### 1. Flash Messages Display
All messaging profile pages now display success and error messages from the Laravel backend:

#### Pages Updated:
- `resources/js/Pages/MessagingProfiles/Index.vue` - List page
- `resources/js/Pages/MessagingProfiles/Create.vue` - Create form
- `resources/js/Pages/MessagingProfiles/Edit.vue` - Edit form
- `resources/js/Pages/MessagingProfiles/Show.vue` - Detail/Assignment page

### 2. Error Message Types Handled

#### Success Messages (Green Banner)
- Messaging profile created successfully
- Messaging profile updated successfully
- Messaging profile deleted successfully
- Phone number assigned successfully
- Phone number unassigned successfully

#### Error Messages (Red Banner)
- Telnyx API errors (when creating/updating/deleting profiles)
- Phone number assignment errors
- Phone number unassignment errors
- Validation errors (general)
- Database errors
- Network errors

### 3. Display Location
- **Top of Page**: Success and error banners appear at the very top of the content area, before the main card
- **Form Fields**: Individual validation errors appear below each form field
- **Color Coding**:
  - Success: Green background (`bg-green-50`) with green border and text
  - Error: Red background (`bg-red-50`) with red border and text

## Technical Details

### Backend (Laravel Controller)
The `MessagingProfileController` uses Laravel's standard flash message methods:

```php
// Success
return redirect()->route('messaging-profiles.index')
    ->with('success', 'Messaging profile created successfully.');

// Error
return back()->withErrors(['error' => 'Failed to create messaging profile: ' . $error]);
```

### Frontend (Vue.js)
Messages are accessed via Inertia's page props:

```vue
<!-- Success Message -->
<div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
    {{ $page.props.flash.success }}
</div>

<!-- Error Message -->
<div v-if="$page.props.flash?.error || $page.props.errors?.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
    {{ $page.props.flash.error || $page.props.errors.error }}
</div>
```

### Middleware Configuration
The `HandleInertiaRequests` middleware (already configured) shares flash messages:

```php
'flash' => [
    'success' => fn () => session('success'),
    'error' => fn () => session('error'),
    // ... other flash types
],
```

## Error Scenarios Covered

### 1. Create Profile
- ✅ Telnyx API connection failures
- ✅ Invalid profile configuration
- ✅ Database save errors
- ✅ Validation errors (whitelisted destinations, webhook URLs, etc.)

### 2. Update Profile
- ✅ Profile not found
- ✅ Unauthorized access (user doesn't own profile)
- ✅ Telnyx API update failures
- ✅ Database update errors
- ✅ Validation errors

### 3. Delete Profile
- ✅ Profile not found
- ✅ Unauthorized access
- ✅ Telnyx API delete failures
- ✅ Database delete errors

### 4. Phone Number Assignment
- ✅ Phone number not found
- ✅ Phone number already assigned
- ✅ Telnyx API assignment failures
- ✅ Database update errors

### 5. Phone Number Unassignment
- ✅ Phone number not found
- ✅ Phone number not assigned to profile
- ✅ Telnyx API unassignment failures
- ✅ Database update errors

## User Experience

### Before
- Users might see generic error messages in the browser console
- No visual feedback for success operations
- Difficult to understand what went wrong

### After
- Clear, prominent success messages for all successful operations
- Detailed error messages explaining what went wrong
- Color-coded visual feedback
- Error messages appear both:
  - At the top of the page (general errors)
  - Next to form fields (validation errors)

## Testing Recommendations

1. **Create Profile**
   - Try creating with invalid Telnyx credentials
   - Try creating without selecting any countries
   - Try creating with invalid webhook URLs

2. **Update Profile**
   - Try updating with invalid data
   - Try simulating Telnyx API failure

3. **Delete Profile**
   - Try deleting a profile that has assigned numbers
   - Try simulating Telnyx API failure

4. **Phone Number Assignment**
   - Try assigning a number that doesn't exist
   - Try assigning a number that's already assigned
   - Try simulating Telnyx API failure

## Future Enhancements

1. **Auto-dismiss Messages**: Add timeout to automatically hide success messages
2. **Multiple Error Display**: Show all validation errors in a list
3. **Error Logging**: Add client-side error logging for debugging
4. **Retry Mechanism**: Add retry button for Telnyx API failures
5. **Offline Detection**: Show specific message when no internet connection

## Enhanced Telnyx API Error Handling

### New Features (v2)

The `MessagingProfileService` now includes enhanced Telnyx API error formatting:

#### User-Friendly Error Messages

The service now provides context-specific error messages based on HTTP status codes:

- **401 Unauthorized**: "Authentication failed. Please check your Telnyx API key configuration."
- **403 Forbidden**: "Access denied. Your Telnyx account may not have permission for this operation."
- **404 Not Found**: "Resource not found in Telnyx. The messaging profile may have been deleted."
- **422 Validation Error**: Displays detailed validation errors from Telnyx
- **429 Rate Limit**: "Rate limit exceeded. Please wait a moment and try again."
- **500+ Server Error**: "Telnyx server error. Please try again later or contact Telnyx support."

#### Detailed Error Logging

All Telnyx API errors are now logged with:
- HTTP status code
- Full error message
- Stack trace for debugging
- Request context

#### Error Message Format

The service extracts and formats error details from Telnyx API responses:
```
Telnyx API Error (HTTP 422): Field 'whitelisted_destinations' is required
```

### Implementation Details

The `formatTelnyxError()` method:
1. Extracts HTTP status code from exception
2. Parses JSON error body for detailed messages
3. Provides user-friendly messages for common errors
4. Falls back to raw error message if parsing fails

### Testing Error Scenarios

To test error handling:

1. **Authentication Error**: Use an invalid API key
2. **Validation Error**: Try creating a profile without required fields
3. **Not Found Error**: Try updating a non-existent profile
4. **Rate Limit**: Make rapid API requests
5. **Server Error**: Simulate by testing during Telnyx maintenance

## Notes

- All error handling follows Laravel and Inertia.js best practices
- Messages are automatically cleared when navigating to a new page
- The Toast component in some pages is kept for backward compatibility but may be phased out in favor of the new flash message system
- Telnyx API errors are now user-friendly and provide actionable information
- All errors are logged for debugging purposes

