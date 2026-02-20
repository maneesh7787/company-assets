# Service Request Feature - Troubleshooting Guide

## Common Issues and Solutions

### Issue 1: Employee Cannot See "New Service Request" Button

**Symptom:**
- Employee navigates to service request page
- Page appears blank or incomplete
- "New Service Request" button not visible

**Cause:**
Variable name mismatch - page content was captured but not passed to layout template.

**Solution:**
Fixed in commit `da6520e` - Changed `$content` to `$page_content` on line 490.

**Status:** ✅ RESOLVED

---

### Issue 2: Fatal Error on Employee Service Request Page

**Symptom:**
```
Fatal error: Uncaught Error: Call to undefined function validate_csrf_token() 
in employee/service_request.php:23
```

**Cause:**
Incorrect CSRF function name - used `validate_csrf_token()` instead of `verify_csrf_token()`.

**Solution:**
Fixed in commit `2d2a703`:
- Line 23: Changed to `verify_csrf_token()`
- Line 80: Changed to `verify_csrf_token()`

**Status:** ✅ RESOLVED

---

### Issue 3: Service Requests Not Visible on Admin Side

**Symptom:**
- Employee submits service request successfully
- Admin navigates to `admin/service_requests.php`
- Service requests are not visible
- Page may show error or crash

**Cause:**
Same CSRF function name error on admin side - used `validate_csrf_token()` instead of `verify_csrf_token()`.

**Solution:**
Fixed in commit `f44f2d3`:
- Line 21: Changed to `verify_csrf_token()`

**Status:** ✅ RESOLVED

---

## Complete Resolution Timeline

### Step 1: Variable Name Fix (da6520e)
**Problem:** Page content not displaying
**Fix:** Corrected variable name `$content` → `$page_content`
**Result:** Page structure now displays correctly

### Step 2: Employee CSRF Fix (2d2a703)
**Problem:** Fatal error on form submission
**Fix:** Corrected function name `validate_csrf_token()` → `verify_csrf_token()`
**Result:** Employee can submit service requests

### Step 3: Admin CSRF Fix (f44f2d3)
**Problem:** Admin page crashes, requests not visible
**Fix:** Corrected function name `validate_csrf_token()` → `verify_csrf_token()`
**Result:** Admin can view and manage all service requests

---

## Current Status: ✅ ALL ISSUES RESOLVED

### Employee Side - Working Features
- ✅ Page loads correctly with all UI elements
- ✅ "New Service Request" button visible
- ✅ Can submit new service requests
- ✅ Can view request history with status
- ✅ Can mark approved requests as completed
- ✅ Can upload bills and add repair costs
- ✅ CSRF validation working correctly

### Admin Side - Working Features
- ✅ Page loads correctly with statistics
- ✅ All service requests visible (pending, approved, rejected, completed)
- ✅ Can filter by status and employee
- ✅ Can search requests
- ✅ Can approve/reject requests with admin response
- ✅ Can view completed requests with bills
- ✅ CSRF validation working correctly
- ✅ Email notifications sent to employees

### End-to-End Workflow
1. ✅ Employee submits service request
2. ✅ Admin receives email notification
3. ✅ Admin views request on dashboard
4. ✅ Admin approves or rejects with response
5. ✅ Employee receives email notification
6. ✅ Employee marks request as completed after repair
7. ✅ Employee uploads bill and adds cost (optional)
8. ✅ Admin views completed request with details

---

## Verification Steps

### Test Employee Functionality
```bash
1. Login as employee
2. Navigate to "Service Requests" menu
3. Verify page loads with "New Service Request" button
4. Click "New Service Request"
5. Select an assigned asset
6. Fill in problem description and date
7. Submit the request
8. Verify success message appears
9. Verify request appears in the list with "Pending" status
```

### Test Admin Functionality
```bash
1. Login as admin
2. Navigate to "Service Requests" menu
3. Verify page loads with statistics
4. Verify the employee's request is visible
5. Click "Approve" on the request
6. Add admin response
7. Submit approval
8. Verify success message appears
9. Verify request status changes to "Approved"
10. Verify employee receives email
```

### Test Complete Workflow
```bash
1. As employee: Submit service request
2. As admin: Approve the request
3. As employee: Mark as completed, add cost, upload bill
4. As admin: View completed request with bill
```

---

## Prevention Measures

### Code Review Checklist
- ✅ Always use `verify_csrf_token()` for CSRF validation
- ✅ Always use `$page_content` for output buffering in layout
- ✅ Run PHP syntax check: `php -l filename.php`
- ✅ Search for function usage: `grep -r "function_name" .`

### Testing Checklist
- ✅ Test page loads without errors
- ✅ Test form submissions work
- ✅ Test CSRF validation is active
- ✅ Test both employee and admin sides
- ✅ Test end-to-end workflows

---

## Related Documentation
- `SERVICE_REQUEST_USER_GUIDE.md` - Complete user manual
- `SERVICE_REQUEST_VISUAL_GUIDE.md` - Visual guide with diagrams
- `BUGFIX_CSRF_FUNCTION_NAME.md` - Detailed bug fix documentation
- `security.php` - CSRF function definitions

---

## Support
If you encounter any other issues:
1. Check PHP error logs
2. Verify database connection
3. Check CSRF token generation
4. Review this troubleshooting guide
5. Consult the related documentation above

**Last Updated:** 2026-02-20
**Status:** All Known Issues Resolved ✅
