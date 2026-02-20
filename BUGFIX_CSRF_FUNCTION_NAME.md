# Bug Fix: CSRF Function Name Error

## Issues
Multiple files had fatal errors due to incorrect CSRF function name.

### Issue 1: Employee Service Request Page
**Fatal Error:**
```
Fatal error: Uncaught Error: Call to undefined function validate_csrf_token() 
in C:\xampp\htdocs\company-assets\employee\service_request.php:23
Stack trace: #0 {main} 
thrown in C:\xampp\htdocs\company-assets\employee\service_request.php on line 23
```

### Issue 2: Admin Service Request Page
**Fatal Error:**
```
Fatal error: Uncaught Error: Call to undefined function validate_csrf_token() 
in C:\xampp\htdocs\company-assets\admin\service_requests.php:21
```

**Result:** Service requests submitted by employees were not visible on admin side because the page crashed.

## Root Cause
Both `employee/service_request.php` and `admin/service_requests.php` were calling a function named `validate_csrf_token()`, but the actual function defined in `security.php` is named `verify_csrf_token()`.

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
- `admin/service_requests.php` (line 21)

## Changes Made

### Employee Service Request Page
1. **Line 23**: Replaced `validate_csrf_token()` with `verify_csrf_token()`
2. **Line 80**: Replaced `validate_csrf_token()` with `verify_csrf_token()`

### Admin Service Requests Page
3. **Line 21**: Replaced `validate_csrf_token()` with `verify_csrf_token()`

## Verification
All CSRF function calls in `security.php`:
- `generate_csrf_token()` - Generates new CSRF token
- `verify_csrf_token($token)` - Validates CSRF token

All other pages in the application correctly use `verify_csrf_token()`:
- `employee/request_asset.php` ✓
- `employee/profile.php` ✓
- `admin/*` pages ✓

## Impact

### Employee Page - Before Fix:
- Page crashed with fatal error
- Service request feature completely broken
- No access to service request functionality

### Employee Page - After Fix:
- Page loads successfully
- CSRF tokens validated correctly
- Service request forms work properly
- Security validation active

### Admin Page - Before Fix:
- Page crashed when processing approve/reject actions
- Service requests submitted by employees were NOT visible
- Admin could not manage service requests

### Admin Page - After Fix:
- Page loads and displays all service requests
- Admin can view pending, approved, rejected, and completed requests
- Approve/reject functionality works correctly
- Complete service request management functional

## Testing Steps

### Employee Side
1. Navigate to `employee/service_request.php`
2. Verify page loads without errors
3. Click "New Service Request" button
4. Fill out and submit form
5. Verify CSRF validation works
6. Verify request is created successfully

### Admin Side
1. Navigate to `admin/service_requests.php`
2. Verify page loads without errors
3. Verify service requests from employees are visible
4. Click "Approve" or "Reject" on a pending request
5. Fill out admin response and submit
6. Verify CSRF validation works
7. Verify request status is updated
8. Verify employee receives email notification

## Security Note
This was a typo in the function name, not a security vulnerability. The CSRF protection mechanism was always intended to be active; the incorrect function name simply prevented the page from loading. Now that the correct function name is used, CSRF protection is working as designed.

## Commits

### Fix 1: Employee Service Request Page
- **Commit Hash**: 2d2a703
- **Message**: Fix fatal error - Replace validate_csrf_token with verify_csrf_token
- **Files Changed**: employee/service_request.php
- **Lines Changed**: 2 insertions(+), 2 deletions(-)

### Fix 2: Admin Service Requests Page
- **Commit Hash**: f44f2d3
- **Message**: Fix admin service requests - Replace validate_csrf_token with verify_csrf_token
- **Files Changed**: admin/service_requests.php
- **Lines Changed**: 1 insertion(+), 1 deletion(-)

## Related Documentation
- See `SERVICE_REQUEST_USER_GUIDE.md` for feature documentation
- See `SERVICE_REQUEST_VISUAL_GUIDE.md` for UI details
- See `security.php` for CSRF function definitions
