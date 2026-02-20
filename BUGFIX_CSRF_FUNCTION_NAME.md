# Bug Fix: CSRF Function Name Error

## Issue
**Fatal Error:**
```
Fatal error: Uncaught Error: Call to undefined function validate_csrf_token() 
in C:\xampp\htdocs\company-assets\employee\service_request.php:23
Stack trace: #0 {main} 
thrown in C:\xampp\htdocs\company-assets\employee\service_request.php on line 23
```

## Root Cause
The `employee/service_request.php` file was calling a function named `validate_csrf_token()`, but the actual function defined in `security.php` is named `verify_csrf_token()`.

### Incorrect Usage
```php
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token. Please try again.';
}
```

### Correct Usage
```php
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token. Please try again.';
}
```

## Files Modified
- `employee/service_request.php` (lines 23 and 80)

## Changes Made
1. **Line 23**: Replaced `validate_csrf_token()` with `verify_csrf_token()`
2. **Line 80**: Replaced `validate_csrf_token()` with `verify_csrf_token()`

## Verification
All CSRF function calls in `security.php`:
- `generate_csrf_token()` - Generates new CSRF token
- `verify_csrf_token($token)` - Validates CSRF token

All other pages in the application correctly use `verify_csrf_token()`:
- `employee/request_asset.php` ✓
- `employee/profile.php` ✓
- `admin/*` pages ✓

## Impact
**Before Fix:**
- Page crashed with fatal error
- Service request feature completely broken
- No access to service request functionality

**After Fix:**
- Page loads successfully
- CSRF tokens validated correctly
- Service request forms work properly
- Security validation active

## Testing Steps
1. Navigate to `employee/service_request.php`
2. Verify page loads without errors
3. Click "New Service Request" button
4. Fill out and submit form
5. Verify CSRF validation works
6. Verify request is created successfully

## Security Note
This was a typo in the function name, not a security vulnerability. The CSRF protection mechanism was always intended to be active; the incorrect function name simply prevented the page from loading. Now that the correct function name is used, CSRF protection is working as designed.

## Commit
- **Commit Hash**: 2d2a703
- **Message**: Fix fatal error - Replace validate_csrf_token with verify_csrf_token
- **Files Changed**: 1
- **Lines Changed**: 2 insertions(+), 2 deletions(-)

## Related Documentation
- See `SERVICE_REQUEST_USER_GUIDE.md` for feature documentation
- See `SERVICE_REQUEST_VISUAL_GUIDE.md` for UI details
- See `security.php` for CSRF function definitions
