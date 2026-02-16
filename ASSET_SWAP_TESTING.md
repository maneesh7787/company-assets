# Asset Swap Functionality - Testing Guide

## Overview
The asset swap functionality allows Admin/IT users to transfer an asset from one employee to another in a single operation. This replaces the previous two-step process of returning an asset and then reassigning it.

## Feature Description

### What Does Asset Swap Do?
When an admin swaps an asset:
1. The current assignment is marked as 'returned' with the swap date
2. A new assignment is created for the new employee with the swap date
3. The asset status remains 'assigned' (no status change)
4. An audit log entry is created with action 'ASSET_SWAP'
5. An email notification is sent to the new employee

### Benefits
- **Single-step operation**: No need to return and then reassign
- **Clean history**: Both employees' assignment histories are preserved
- **Audit trail**: Complete tracking of asset movement between employees
- **Email notifications**: New employee is automatically notified

## Testing Checklist

### Prerequisites
1. ✅ Import database.sql
2. ✅ Configure config.php with database credentials
3. ✅ Login as admin user (admin@company.com / Admin@123)
4. ✅ Have at least 2 active employees in the system
5. ✅ Have at least 1 asset assigned to an employee

### Test Cases

#### Test Case 1: Swap Asset Successfully
**Steps:**
1. Go to Admin → Asset Management
2. Find an asset with status "Assigned"
3. Click the cyan/info colored "Swap" button (exchange icon)
4. Select a different employee from the dropdown
5. Set the swap date (default is today)
6. Click "Swap Asset"

**Expected Results:**
- ✅ Success message: "Asset swapped successfully"
- ✅ Asset still shows status "Assigned"
- ✅ Asset now shows new employee in "Assigned To" column
- ✅ Old employee's assignment record shows returned_date = swap date
- ✅ New employee's assignment record created with assigned_date = swap date
- ✅ Audit log shows ASSET_SWAP action with both employee names
- ✅ Email sent to new employee (check email logs)

#### Test Case 2: Validate Same Employee Error
**Steps:**
1. Try to swap an asset
2. Select the same employee who currently has the asset
3. Click "Swap Asset"

**Expected Results:**
- ✅ Error message: "Asset is already assigned to this employee"
- ✅ No changes made to database
- ✅ No audit log created

#### Test Case 3: Validate Unassigned Asset Error
**Steps:**
1. Try to access swap functionality for an available asset
2. Note: Swap button should NOT appear for available assets

**Expected Results:**
- ✅ Swap button not visible for available assets
- ✅ Only appears for assets with status "Assigned"

#### Test Case 4: Check Assignment History
**Steps:**
1. After swapping an asset, go to Admin → Asset Assignments
2. Filter to show "All" assignments
3. Find the asset that was swapped

**Expected Results:**
- ✅ Two assignment records visible:
  - Old assignment: status = "returned", returned_date = swap date
  - New assignment: status = "active", assigned_date = swap date

#### Test Case 5: Check Employee Views
**Steps:**
1. Login as the old employee (employee who had the asset)
2. Check "My Assets" page
3. Logout and login as new employee
4. Check "My Assets" page

**Expected Results:**
- ✅ Old employee: Asset shows in history with returned status
- ✅ New employee: Asset shows as currently assigned

#### Test Case 6: Check Audit Logs
**Steps:**
1. Go to Admin → Audit Logs
2. Filter by action "ASSET_SWAP"
3. View the swap entry

**Expected Results:**
- ✅ Log entry shows:
  - User who performed swap
  - Asset ID and name
  - Old employee ID and name
  - New employee ID and name
  - Timestamp of swap

## UI Elements

### Swap Button
- **Location**: Asset Management table, Actions column
- **Visibility**: Only for assets with status "Assigned"
- **Icon**: Exchange arrows (fa-exchange-alt)
- **Color**: Info/Cyan (btn-info)
- **Tooltip**: "Swap Asset"

### Swap Modal
- **Title**: "Swap Asset"
- **Fields**:
  - Asset name (read-only display)
  - New Employee (dropdown with all active employees)
  - Swap Date (date picker, default = today)
- **Buttons**:
  - Cancel (secondary)
  - Swap Asset (info/cyan)

## Database Changes

### asset_assignments Table
When swapping asset #1 from employee #5 to employee #10 on 2026-02-16:

**Old Assignment Record (ID: 3):**
```
id: 3
asset_id: 1
employee_id: 5
assigned_date: 2026-01-15
returned_date: 2026-02-16  ← Updated
status: returned            ← Updated
```

**New Assignment Record (ID: 4):**
```
id: 4                       ← New record
asset_id: 1
employee_id: 10
assigned_date: 2026-02-16
returned_date: NULL
status: active
```

**Asset Record (ID: 1):**
```
id: 1
status: assigned            ← Unchanged
...
```

### audit_logs Table
```
id: 125
user_id: 1 (admin)
action: ASSET_SWAP
description: Swapped asset #1 (Dell Laptop) from employee #5 (John Doe) to employee #10 (Jane Smith)
created_at: 2026-02-16 10:30:00
```

## Security Considerations

### Implemented Security
1. ✅ CSRF token validation on form submission
2. ✅ Admin role verification (require_role(['admin']))
3. ✅ Prepared statements for all database queries
4. ✅ Input validation and sanitization
5. ✅ Transaction-based operation (rollback on failure)
6. ✅ XSS prevention with escape_output()

### Validations
1. ✅ Asset must exist
2. ✅ Asset must be in 'assigned' status
3. ✅ Active assignment must exist for the asset
4. ✅ New employee must exist and be active
5. ✅ New employee cannot be the same as current employee
6. ✅ Swap date validation (handled by HTML5 date input)

## Troubleshooting

### Issue: Swap button not appearing
**Solution**: Check that:
- Asset status is 'assigned'
- You're logged in as admin
- Browser cache is cleared

### Issue: "No active assignment found" error
**Solution**: 
- Check asset_assignments table for active records
- Ensure asset status is 'assigned'
- Database integrity issue - may need manual correction

### Issue: Email not sent
**Solution**:
- Email functionality uses PHP mail() by default
- Configure SMTP in email.php for production
- Check error logs for email sending errors

## Code References

### Files Modified
- `/admin/assets.php` - Main asset management page

### Code Sections
- **Swap Action Handler**: Lines 225-297
- **Swap Button**: Added to table actions around line 375-380
- **Swap Modal**: Lines 726-765
- **JavaScript Function**: swapAsset() around line 819

## Additional Notes

### When to Use Swap vs Return+Assign
- **Use Swap**: When transferring asset from one employee to another
- **Use Return**: When asset is being decommissioned or going back to inventory
- **Use Assign**: When giving an available asset to an employee for the first time

### Audit Trail Benefits
The swap operation maintains complete history:
- Old employee's record shows when they returned the asset
- New employee's record shows when they received it
- Audit log shows who authorized the swap
- Both employees' assignment histories remain intact

## Success Criteria
The swap functionality is working correctly if:
1. ✅ Asset transfers from employee A to employee B in one operation
2. ✅ Asset status remains 'assigned'
3. ✅ Old assignment marked as 'returned'
4. ✅ New assignment created as 'active'
5. ✅ Audit log created with ASSET_SWAP action
6. ✅ Email sent to new employee
7. ✅ No data corruption or orphaned records
8. ✅ Transaction rolls back on any error

---

**Testing Complete**: Please verify all test cases above and report any issues found.
