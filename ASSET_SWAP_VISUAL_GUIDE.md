# Asset Swap Feature - Visual Guide

## Overview
The asset swap feature allows administrators to transfer an asset from one employee to another in a single operation.

## UI Components

### 1. Swap Button Location
The swap button appears in the **Asset Management** table for assets with status "Assigned".

**Table View:**
```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ Asset Management                                           [+ Add New Asset]    │
├─────────────────────────────────────────────────────────────────────────────────┤
│ ID │ Asset Name  │ Category │ Serial    │ Status   │ Assigned To │ Actions    │
├────┼─────────────┼──────────┼───────────┼──────────┼─────────────┼────────────┤
│ 1  │ Dell Laptop │ Laptop   │ SN-001    │ Assigned │ John Doe    │ [Edit]     │
│    │             │          │           │          │             │ [Swap]     │ ← NEW!
│    │             │          │           │          │             │ [Return]   │
├────┼─────────────┼──────────┼───────────┼──────────┼─────────────┼────────────┤
│ 2  │ HP Monitor  │ Monitor  │ SN-002    │ Available│ -           │ [Edit]     │
│    │             │          │           │          │             │ [Assign]   │
│    │             │          │           │          │             │ [Delete]   │
└────┴─────────────┴──────────┴───────────┴──────────┴─────────────┴────────────┘
```

**Button Styles:**
- **Edit**: Blue/Primary (fa-edit icon)
- **Swap**: Cyan/Info (fa-exchange-alt icon) ← **NEW BUTTON**
- **Return**: Yellow/Warning (fa-undo icon)
- **Assign**: Green/Success (fa-user-plus icon) - Only for available assets
- **Delete**: Red/Danger (fa-trash icon) - Only for non-assigned assets

### 2. Swap Modal

When clicking the Swap button, a modal opens:

```
┌─────────────────────────────────────────────────────┐
│ Swap Asset                                      [×] │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Swapping asset: Dell Laptop                       │
│                                                     │
│  New Employee *                                     │
│  ┌───────────────────────────────────────────┐     │
│  │ Select Employee                     ▼     │     │
│  └───────────────────────────────────────────┘     │
│  Options:                                           │
│    - Jane Smith (jane@company.com)                 │
│    - Bob Johnson (bob@company.com)                 │
│    - Alice Williams (alice@company.com)            │
│    (Current employee excluded from list)           │
│                                                     │
│  Swap Date *                                        │
│  ┌───────────────────────────────────────────┐     │
│  │ 2026-02-16                          📅    │     │
│  └───────────────────────────────────────────┘     │
│                                                     │
│  ┌───────────────────────────────────────────┐     │
│  │ ℹ️ This will return the asset from the    │     │
│  │   current employee and assign it to the   │     │
│  │   new employee. Email notifications will  │     │
│  │   be sent.                                │     │
│  └───────────────────────────────────────────┘     │
│                                                     │
│              [Cancel]  [Swap Asset]                │
└─────────────────────────────────────────────────────┘
```

### 3. Success Message

After successful swap:

```
┌─────────────────────────────────────────────────────────┐
│ ✓ Asset swapped successfully                       [×] │
└─────────────────────────────────────────────────────────┘
```

### 4. Updated Table After Swap

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ ID │ Asset Name  │ Category │ Serial    │ Status   │ Assigned To │ Actions    │
├────┼─────────────┼──────────┼───────────┼──────────┼─────────────┼────────────┤
│ 1  │ Dell Laptop │ Laptop   │ SN-001    │ Assigned │ Jane Smith  │ [Edit]     │
│    │             │          │           │          │  ↑ UPDATED  │ [Swap]     │
│    │             │          │           │          │             │ [Return]   │
└────┴─────────────┴──────────┴───────────┴──────────┴─────────────┴────────────┘
```

## Workflow Diagram

```
┌─────────────────┐
│   Admin Panel   │
│ Asset Mgmt Page │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ Find Asset (Assigned)   │
│ Click Swap Button       │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│   Swap Modal Opens      │
│ - Select New Employee   │
│ - Set Swap Date         │
│ - Click "Swap Asset"    │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐     ┌──────────────────┐
│  Backend Processing     │────▶│ Validation       │
│ (Transaction Started)   │     │ - Asset exists?  │
└────────┬────────────────┘     │ - Is assigned?   │
         │                      │ - Employee OK?   │
         │                      │ - Not same emp?  │
         │                      └──────────────────┘
         ▼
┌─────────────────────────────────────────────┐
│  Database Operations (In Transaction)       │
│  1. Mark old assignment as 'returned'       │
│  2. Create new assignment as 'active'       │
│  3. Asset status stays 'assigned'           │
│  4. Log ASSET_SWAP in audit_logs           │
└────────┬────────────────────────────────────┘
         │
         ▼
┌─────────────────────────┐
│  Send Email             │
│  To: New Employee       │
│  Subject: Asset Assigned│
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│  Commit Transaction     │
│  Show Success Message   │
│  Reload Page            │
└─────────────────────────┘
```

## Database Changes Flow

### Before Swap

**assets table (id=1):**
```
┌────┬──────────────┬──────────┐
│ id │  asset_name  │  status  │
├────┼──────────────┼──────────┤
│ 1  │ Dell Laptop  │ assigned │
└────┴──────────────┴──────────┘
```

**asset_assignments table:**
```
┌────┬──────────┬─────────────┬───────────────┬───────────────┬────────┐
│ id │ asset_id │ employee_id │ assigned_date │ returned_date │ status │
├────┼──────────┼─────────────┼───────────────┼───────────────┼────────┤
│ 5  │    1     │      3      │  2026-01-15   │     NULL      │ active │
└────┴──────────┴─────────────┴───────────────┴───────────────┴────────┘
                    ↑
              (John Doe)
```

### After Swap (Employee 3 → Employee 7)

**assets table (id=1):**
```
┌────┬──────────────┬──────────┐
│ id │  asset_name  │  status  │
├────┼──────────────┼──────────┤
│ 1  │ Dell Laptop  │ assigned │ ← Unchanged
└────┴──────────────┴──────────┘
```

**asset_assignments table:**
```
┌────┬──────────┬─────────────┬───────────────┬───────────────┬──────────┐
│ id │ asset_id │ employee_id │ assigned_date │ returned_date │  status  │
├────┼──────────┼─────────────┼───────────────┼───────────────┼──────────┤
│ 5  │    1     │      3      │  2026-01-15   │  2026-02-16   │ returned │ ← Updated
│ 9  │    1     │      7      │  2026-02-16   │     NULL      │  active  │ ← New
└────┴──────────┴─────────────┴───────────────┴───────────────┴──────────┘
              (John Doe)              ↑              (Jane Smith)
                              Swap Date
```

**audit_logs table:**
```
┌─────┬─────────┬─────────────┬────────────────────────────────────────┬────────────┐
│ id  │ user_id │   action    │            description                 │ created_at │
├─────┼─────────┼─────────────┼────────────────────────────────────────┼────────────┤
│ 147 │    1    │ ASSET_SWAP  │ Swapped asset #1 (Dell Laptop) from   │ 2026-02-16 │
│     │ (admin) │             │ employee #3 (John Doe) to employee #7  │ 10:30:00   │
│     │         │             │ (Jane Smith)                           │            │
└─────┴─────────┴─────────────┴────────────────────────────────────────┴────────────┘
```

## Employee Views

### John Doe's View (Old Employee)
**My Assets Page:**
```
┌──────────────────────────────────────────────────────────────────┐
│ My Assets                                                        │
├──────────────────────────────────────────────────────────────────┤
│ Currently Assigned Assets: 0                                     │
│                                                                  │
│ Assignment History:                                              │
│ ┌────────────────┬───────────────┬───────────────┬──────────┐   │
│ │ Asset Name     │ Assigned Date │ Returned Date │ Status   │   │
│ ├────────────────┼───────────────┼───────────────┼──────────┤   │
│ │ Dell Laptop    │ 2026-01-15    │ 2026-02-16    │ Returned │   │
│ │ (SN-001)       │               │               │          │   │
│ └────────────────┴───────────────┴───────────────┴──────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

### Jane Smith's View (New Employee)
**My Assets Page:**
```
┌──────────────────────────────────────────────────────────────────┐
│ My Assets                                                        │
├──────────────────────────────────────────────────────────────────┤
│ Currently Assigned Assets: 1                                     │
│                                                                  │
│ Active Assignments:                                              │
│ ┌────────────────┬──────────┬───────────────┬─────────────┐     │
│ │ Asset Name     │ Category │ Serial Number │ Assigned    │     │
│ ├────────────────┼──────────┼───────────────┼─────────────┤     │
│ │ Dell Laptop    │ Laptop   │ SN-001        │ 2026-02-16  │     │
│ └────────────────┴──────────┴───────────────┴─────────────┘     │
│                                                                  │
│ 📧 Email Notification Sent                                       │
│    Subject: Asset Assigned to You                                │
│    Content: Dell Laptop (SN-001) assigned on 2026-02-16         │
└──────────────────────────────────────────────────────────────────┘
```

## Comparison: Old Way vs New Way

### Old Way (Return + Assign)
```
Step 1: Admin clicks "Return" on asset
   ↓
Step 2: Asset status → available
   ↓
Step 3: Assignment marked as returned
   ↓
Step 4: Admin clicks "Assign" on same asset
   ↓
Step 5: Select new employee
   ↓
Step 6: Asset status → assigned
   ↓
Step 7: New assignment created
```
**Problems:**
- Two separate operations
- Asset temporarily "available" between steps
- Risk of forgetting second step
- More clicks and time required

### New Way (Swap)
```
Step 1: Admin clicks "Swap" on asset
   ↓
Step 2: Select new employee
   ↓
Step 3: Click "Swap Asset"
   ↓
Step 4: Everything happens in one transaction:
        - Old assignment → returned
        - New assignment → created
        - Asset stays assigned
        - Audit log created
        - Email sent
```
**Benefits:**
- Single operation
- Asset never shows as "available"
- Atomic transaction (all or nothing)
- Faster and more intuitive
- Better audit trail

## Code Highlight

### Key Function
```php
// admin/assets.php - Line 225
elseif ($action == 'swap') {
    // Get asset and validate
    // Get current employee
    // Get new employee
    // Validate not same employee
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Mark old assignment as returned
    // Create new assignment
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log audit
    log_audit($conn, $_SESSION['user_id'], 'ASSET_SWAP', ...);
    
    // Send email to new employee
    send_assignment_notification(...);
}
```

## Security Features

✅ **CSRF Protection**: Token verified on submission  
✅ **Role-Based Access**: Admin only (require_role(['admin']))  
✅ **SQL Injection Prevention**: Prepared statements  
✅ **Transaction Safety**: Atomic operation with rollback  
✅ **Input Validation**: All inputs validated and sanitized  
✅ **XSS Prevention**: Output escaped with htmlspecialchars  
✅ **Audit Trail**: Complete logging of who, what, when  

## Summary

The asset swap feature provides a streamlined way to transfer assets between employees while maintaining complete audit trails and data integrity. It's secure, efficient, and user-friendly!
