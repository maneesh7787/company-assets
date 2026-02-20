# Testing Guide for Asset Assignment and Swap Restrictions

## Overview
This document outlines the testing procedures for the newly implemented restrictions on asset assignment and swap functionality.

## Changes Implemented

### Change 1: Asset Assignment Restricted to Approved Requests
**Requirement**: Admin can only assign assets to employees who have submitted an approved asset request.

**What Changed**:
- Employee dropdown in "Assign Asset" modal now only shows employees with approved requests
- Backend validation prevents assignment to employees without approved requests
- Error message displayed if assignment attempted to ineligible employee

### Change 2: Current Employee Excluded from Swap Dropdown
**Requirement**: When swapping an asset, the current employee (who has the asset) should not appear in the new employee dropdown.

**What Changed**:
- Current employee option is disabled in the swap dropdown
- JavaScript function disables the option dynamically when modal opens
- Prevents accidental swap to same employee

---

## Testing Scenarios

### Scenario 1: Assign Asset - Only Approved Request Employees Visible

**Prerequisites**:
1. Have at least 3 employees in the system
2. Employee A: Has submitted request, status = 'approved'
3. Employee B: Has submitted request, status = 'pending'
4. Employee C: Has not submitted any request

**Test Steps**:
1. Login as admin (admin@company.com / Admin@123)
2. Navigate to Admin → Asset Management
3. Find an available asset
4. Click the green "Assign" button
5. Open the "Employee" dropdown in the modal

**Expected Results**:
✅ Employee A appears in the dropdown (has approved request)
❌ Employee B does NOT appear (request is pending, not approved)
❌ Employee C does NOT appear (no request submitted)

**Pass Criteria**: Only employees with approved requests are shown in the dropdown.

---

### Scenario 2: Assign Asset - Backend Validation

**Prerequisites**:
1. Employee with approved request exists in system
2. Available asset exists

**Test Steps**:
1. Login as admin
2. Navigate to Admin → Asset Management  
3. Click "Assign" on an available asset
4. Select an employee with approved request
5. Click "Assign Asset"

**Expected Results**:
✅ Success message: "Asset assigned successfully"
✅ Asset status changes to "Assigned"
✅ Asset shows employee name in "Assigned To" column
✅ Audit log created with ASSET_ASSIGN action
✅ Email sent to employee

**Pass Criteria**: Assignment succeeds for employee with approved request.

---

### Scenario 3: Assign Asset - Error for No Approved Request

**Setup to Test** (if needed for manual testing):
Since the dropdown now filters employees, you'd need to manually test the backend validation by:
1. Temporarily commenting out the INNER JOIN filter in the query
2. Or using API/direct POST to test backend validation

**Test Steps** (Backend Validation):
1. Try to assign asset to employee without approved request
2. Submit the form

**Expected Results**:
❌ Error message: "Cannot assign asset. Employee must have an approved asset request first."
✅ Asset remains unassigned
✅ No assignment record created
✅ No email sent

**Pass Criteria**: Backend validation prevents assignment to ineligible employees.

---

### Scenario 4: Swap Asset - Current Employee Disabled

**Prerequisites**:
1. Asset assigned to Employee A
2. At least 2 other employees with approved requests (Employee B, Employee C)

**Test Steps**:
1. Login as admin
2. Navigate to Admin → Asset Management
3. Find asset assigned to Employee A
4. Click the cyan "Swap" button
5. Open the "New Employee" dropdown in modal

**Expected Results**:
❌ Employee A is disabled/grayed out in the dropdown
✅ Employee B appears and is selectable
✅ Employee C appears and is selectable
✅ Cannot select Employee A

**Pass Criteria**: Current employee option is disabled in swap dropdown.

---

### Scenario 5: Swap Asset - Successful Swap

**Prerequisites**:
1. Asset assigned to Employee A
2. Employee B has approved request
3. Employee B is not the current holder

**Test Steps**:
1. Login as admin
2. Navigate to Admin → Asset Management
3. Find asset assigned to Employee A
4. Click "Swap" button
5. Select Employee B from dropdown
6. Set swap date
7. Click "Swap Asset"

**Expected Results**:
✅ Success message: "Asset swapped successfully"
✅ Asset still shows status "Assigned"
✅ "Assigned To" column now shows Employee B
✅ Employee A's assignment marked as 'returned'
✅ Employee B's assignment created as 'active'
✅ Audit log shows ASSET_SWAP action
✅ Email sent to Employee B

**Pass Criteria**: Asset successfully swaps from Employee A to Employee B.

---

### Scenario 6: Swap Asset - Dropdown State Reset

**Test Steps**:
1. Open swap modal for Asset 1 (assigned to Employee A)
2. Verify Employee A is disabled
3. Close the modal
4. Open swap modal for Asset 2 (assigned to Employee B)
5. Verify Employee B is disabled (not Employee A)

**Expected Results**:
✅ Each time modal opens, correct current employee is disabled
✅ Previous employee from last modal is re-enabled
✅ Dropdown properly resets for each asset

**Pass Criteria**: Modal correctly identifies and disables the current employee for each asset.

---

## Database Verification Queries

### Check Employee Requests Status
```sql
SELECT u.id, u.name, ar.status, ar.created_at
FROM users u
LEFT JOIN asset_requests ar ON u.id = ar.employee_id
WHERE u.role = 'employee'
ORDER BY u.name;
```

### Check Asset Assignments
```sql
SELECT 
    a.id as asset_id,
    a.asset_name,
    aa.employee_id,
    u.name as employee_name,
    aa.status as assignment_status,
    aa.assigned_date,
    aa.returned_date
FROM assets a
LEFT JOIN asset_assignments aa ON a.id = aa.asset_id AND aa.status = 'active'
LEFT JOIN users u ON aa.employee_id = u.id
ORDER BY a.id;
```

### Check Audit Logs for ASSET_ASSIGN
```sql
SELECT 
    al.id,
    u.name as admin_name,
    al.action,
    al.description,
    al.created_at
FROM audit_logs al
JOIN users u ON al.user_id = u.id
WHERE al.action IN ('ASSET_ASSIGN', 'ASSET_SWAP')
ORDER BY al.created_at DESC
LIMIT 10;
```

---

## Browser Developer Tools Checks

### Check JavaScript Console
Open browser console (F12) and verify:
- No JavaScript errors when opening assign modal
- No JavaScript errors when opening swap modal
- `swapAsset()` function called with 3 parameters

### Check Network Tab
Monitor POST requests:
- Verify CSRF token is sent
- Verify correct action ('assign' or 'swap')
- Verify employee_id is sent

---

## Edge Cases to Test

### Edge Case 1: No Employees with Approved Requests
**Scenario**: No employees have approved requests

**Expected**: 
- Assign dropdown shows only "Select Employee" placeholder
- Cannot assign any asset
- Appropriate message or empty state

### Edge Case 2: All Employees Have Approved Requests
**Scenario**: All active employees have approved requests

**Expected**:
- All employees appear in assign dropdown
- Assignment works normally

### Edge Case 3: Employee Request Approved After Assignment
**Scenario**: 
1. Employee has no approved request
2. Employee gets request approved
3. Try to assign asset

**Expected**:
- Employee now appears in dropdown
- Assignment succeeds

### Edge Case 4: Swap to Same Employee (Manual Test)
**Scenario**: Try to select disabled current employee

**Expected**:
- Option is disabled (grayed out)
- Cannot be selected
- Form validation may trigger if somehow bypassed

---

## Rollback Instructions

If issues are found, changes can be reverted:

```bash
git revert f18cab2
```

This will revert to previous behavior where:
- All active employees appear in assign dropdown
- No validation for approved requests
- Current employee appears in swap dropdown (but validation prevents swap to same)

---

## Success Criteria Summary

All tests must pass:
- ✅ Only employees with approved requests can be assigned assets
- ✅ Dropdown filters employees correctly
- ✅ Backend validation prevents ineligible assignments
- ✅ Current employee is disabled in swap dropdown
- ✅ Swap functionality works correctly to other employees
- ✅ Audit logs are created correctly
- ✅ Email notifications are sent
- ✅ No JavaScript errors in browser console
- ✅ UI is responsive and user-friendly

---

## Known Limitations

1. **Dropdown Filter**: The employee list is loaded once when page loads. If a request is approved while admin is on the page, they need to refresh to see the updated list.

2. **Multiple Approved Requests**: If an employee has multiple approved requests, they only appear once in the dropdown (handled by DISTINCT in query).

3. **Disabled Option Visual**: The disabled current employee appears in the dropdown but is grayed out. Some users might wonder why. Consider adding a note in the modal if needed.

---

## Support

For issues or questions about these changes:
1. Check the git commit: `f18cab2`
2. Review the implementation in `admin/assets.php`
3. Check audit logs for any failed attempts
4. Verify database state using queries above
