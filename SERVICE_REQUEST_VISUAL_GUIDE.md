# Asset Service Request System - Visual Guide

## System Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                    SERVICE REQUEST WORKFLOW                         │
└────────────────────────────────────────────────────────────────────┘

EMPLOYEE SUBMITS REQUEST
        │
        ├─> System validates asset ownership
        ├─> Creates pending request
        ├─> Sends email to admins
        ├─> Logs action in audit trail
        │
        ▼
ADMIN REVIEWS REQUEST
        │
        ├─> Option 1: APPROVE
        │   ├─> Adds admin response
        │   ├─> Sends email to employee
        │   └─> Employee can proceed with repair
        │
        └─> Option 2: REJECT
            ├─> Adds rejection reason
            ├─> Sends email to employee
            └─> Request closed
                │
                ▼ (if approved)
EMPLOYEE COMPLETES REPAIR
        │
        ├─> Marks request as completed
        ├─> Uploads bill (optional)
        ├─> Adds repair cost (optional)
        └─> Request archived
```

---

## Employee Interface

### 1. Service Requests Page Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│ SIDEBAR                    │  MAIN CONTENT AREA                         │
├────────────────────────────┼───────────────────────────────────────────┤
│ Dashboard                  │  ╔═══════════════════════════════════════╗│
│ My Assets                  │  ║ Asset Service Requests    [+ New Req] ║│
│ Request Asset              │  ╚═══════════════════════════════════════╝│
│ ★ Service Requests ★       │                                            │
│ My Profile                 │  [Filter: All Requests ▼]                  │
│                            │                                            │
│                            │  ┌──────────────────────────────────────┐ │
│                            │  │ [PENDING] Dell Latitude 5420         │ │
│                            │  ├──────────────────────────────────────┤ │
│                            │  │ Problem: Screen flickering           │ │
│                            │  │ Since: Feb 15, 2026                  │ │
│                            │  │ Remarks: Happens when plugged in     │ │
│                            │  │                                      │ │
│                            │  │ Submitted: Feb 18, 2026 10:30 AM     │ │
│                            │  └──────────────────────────────────────┘ │
│                            │                                            │
│                            │  ┌──────────────────────────────────────┐ │
│                            │  │ [APPROVED] HP Monitor 24"            │ │
│                            │  ├──────────────────────────────────────┤ │
│                            │  │ Problem: Dead pixels on left side    │ │
│                            │  │ Since: Feb 10, 2026                  │ │
│                            │  │                                      │ │
│                            │  │ Admin: Approved for warranty repair  │ │
│                            │  │                                      │ │
│                            │  │ [Mark Completed] ─────────────────   │ │
│                            │  └──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2. New Service Request Modal

```
┌────────────────────────────────────────────────────────────────┐
│  ⚙ New Service Request                               [×]       │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ℹ Submit a service request for any asset that needs          │
│    repair or maintenance.                                     │
│                                                                │
│  Select Asset *                                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ Dell Latitude 5420 - SN-LAP-001 (Laptop)            ▼   │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  Problem Description *                                         │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ Screen is flickering intermittently. The issue occurs   │ │
│  │ mainly when the laptop is plugged into power. Battery   │ │
│  │ mode seems fine. Getting worse over past week.          │ │
│  │                                                          │ │
│  └──────────────────────────────────────────────────────────┘ │
│  Be specific about the issue for faster resolution            │
│                                                                │
│  Problem Since *                                               │
│  ┌────────────────┐                                           │
│  │ 2026-02-15  📅 │                                           │
│  └────────────────┘                                           │
│  When did you first notice this problem?                      │
│                                                                │
│  Additional Remarks                                            │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ Only happens when connected to power adapter            │ │
│  └──────────────────────────────────────────────────────────┘ │
│  Any additional information or context...                     │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                    [Cancel]  [📤 Submit Request]               │
└────────────────────────────────────────────────────────────────┘
```

### 3. Mark as Completed Modal

```
┌────────────────────────────────────────────────────────────────┐
│  ✓ Mark as Completed                                 [×]       │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ✓ Confirm that the repair/service has been completed.        │
│                                                                │
│  Repair Amount (Optional)                                      │
│  ┌──────┬─────────────────────────────────────────────────┐   │
│  │ USD▼ │ 150.00                                          │   │
│  └──────┴─────────────────────────────────────────────────┘   │
│  Enter the repair/service cost if applicable.                 │
│                                                                │
│  Upload Bill/Invoice (Optional)                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ [Choose File] invoice_repair.pdf                         │ │
│  └──────────────────────────────────────────────────────────┘ │
│  Accepted formats: PDF, JPG, PNG (Max 5MB)                    │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                    [Cancel]  [✓ Confirm Completion]            │
└────────────────────────────────────────────────────────────────┘
```

### 4. Request Status Cards - Visual Elements

```
┌─────────────────────────────────────────────────────────────────┐
│ PENDING REQUEST (Yellow Left Border)                           │
├─────────────────────────────────────────────────────────────────┤
│ 💻 Dell Latitude 5420                         [⏰ PENDING]     │
│ Category: Laptop                                               │
│ Serial: SN-LAP-001                                             │
│                                                                │
│ ⚠ Problem:                                                     │
│ Screen flickering when plugged into power                      │
│                                                                │
│ 📅 Problem Since: Feb 15, 2026                                 │
│ 💬 Remarks: Only when using power adapter                      │
│                                                                │
│ 🕐 Feb 18, 2026 10:30 AM                                       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ APPROVED REQUEST (Green Left Border)                           │
├─────────────────────────────────────────────────────────────────┤
│ 🖥 HP Monitor 24"                              [✓ APPROVED]    │
│ Category: Monitor                                              │
│ Serial: SN-MON-045                                             │
│                                                                │
│ ⚠ Problem:                                                     │
│ Dead pixels on left side of screen                            │
│                                                                │
│ 📅 Problem Since: Feb 10, 2026                                 │
│                                                                │
│ ✅ Admin Response:                                             │
│ Approved for warranty repair. Contact HP support at...        │
│                                                                │
│ 🕐 Feb 16, 2026 2:15 PM          [✓ Mark Completed]           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ COMPLETED REQUEST (Gray Left Border)                           │
├─────────────────────────────────────────────────────────────────┤
│ ⌨ Logitech Keyboard                           [✓ COMPLETED]    │
│ Category: Keyboard                                             │
│ Serial: SN-KEY-089                                             │
│                                                                │
│ ⚠ Problem:                                                     │
│ Some keys not responding                                       │
│                                                                │
│ ✅ Completed On: Feb 20, 2026                                  │
│ 💰 Repair Cost: USD 45.00                                      │
│ 📄 [View Bill]                                                 │
│                                                                │
│ 🕐 Feb 12, 2026 9:00 AM                                        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ REJECTED REQUEST (Red Left Border)                             │
├─────────────────────────────────────────────────────────────────┤
│ 🖱 Wireless Mouse                              [✗ REJECTED]    │
│ Category: Mouse                                                │
│ Serial: SN-MOU-034                                             │
│                                                                │
│ ⚠ Problem:                                                     │
│ Battery drains quickly                                         │
│                                                                │
│ ❌ Admin Response:                                             │
│ This is normal for wireless mice. Please use the charging     │
│ dock provided or replace batteries regularly.                 │
│                                                                │
│ 🕐 Feb 14, 2026 11:00 AM                                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Admin Interface

### 1. Service Requests Management Page

```
┌─────────────────────────────────────────────────────────────────────────┐
│ SIDEBAR                    │  MAIN CONTENT AREA                         │
├────────────────────────────┼───────────────────────────────────────────┤
│ Dashboard                  │  ╔═══════════════════════════════════════╗│
│ User Management            │  ║ Asset Service Requests Management     ║│
│ Categories                 │  ╚═══════════════════════════════════════╝│
│ Asset Management           │  Review and manage employee requests       │
│ Assignments                │                                            │
│ Asset Requests             │  ┌──────────┬──────────┬──────────┬─────┐ │
│ ★ Service Requests ★       │  │ PENDING  │ APPROVED │COMPLETED │REJCT││
│ Audit Logs                 │  │   12     │    8     │    45    │  3  ││
│ Reports & Export           │  └──────────┴──────────┴──────────┴─────┘ │
│                            │                                            │
│                            │  ┌────────────────────────────────────┐   │
│                            │  │ Search: [_________] Status:[All ▼] │   │
│                            │  │ Employee:[All ▼]  [Filter] [Reset] │   │
│                            │  └────────────────────────────────────┘   │
│                            │                                            │
│                            │  ┌──────────────────────────────────────┐ │
│                            │  │ [PENDING] Dell Latitude 5420         │ │
│                            │  ├──────────────────────────────────────┤ │
│                            │  │ 👤 Employee: John Doe                │ │
│                            │  │    john.doe@company.com              │ │
│                            │  │                                      │ │
│                            │  │ ⚠ Problem:                           │ │
│                            │  │ Screen flickering when plugged in    │ │
│                            │  │                                      │ │
│                            │  │ 📅 Problem Since: Feb 15, 2026       │ │
│                            │  │                                      │ │
│                            │  │ [✓ Approve]  [✗ Reject]              │ │
│                            │  └──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2. Statistics Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│                    Service Request Statistics                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │   PENDING    │  │   APPROVED   │  │  COMPLETED   │         │
│  │      12      │  │       8      │  │      45      │         │
│  │   ⏰ 🟡      │  │   ✓ 🟢      │  │   ✓✓ ⚫      │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
│                                                                 │
│  ┌──────────────┐                                              │
│  │   REJECTED   │                                              │
│  │       3      │                                              │
│  │   ✗ 🔴      │                                              │
│  └──────────────┘                                              │
└─────────────────────────────────────────────────────────────────┘
```

### 3. Approve/Reject Modal

```
┌────────────────────────────────────────────────────────────────┐
│  ✓ Approve Service Request                           [×]       │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  Response to Employee                                          │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ Your service request has been approved. Please contact   │ │
│  │ IT support to schedule the repair. The laptop will be   │ │
│  │ covered under warranty.                                  │ │
│  │                                                          │ │
│  └──────────────────────────────────────────────────────────┘ │
│  Provide feedback or instructions to the employee.            │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                         [Cancel]  [✓ Approve]                  │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│  ✗ Reject Service Request                            [×]       │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  Response to Employee                                          │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ This issue appears to be user error. The battery drain  │ │
│  │ is normal for wireless mice. Please use the provided    │ │
│  │ charging dock or replace batteries as needed.           │ │
│  │                                                          │ │
│  └──────────────────────────────────────────────────────────┘ │
│  Provide feedback or instructions to the employee.            │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                         [Cancel]  [✗ Reject]                   │
└────────────────────────────────────────────────────────────────┘
```

---

## Color Coding System

```
STATUS COLORS:

🟡 PENDING    - Yellow border (#ffc107)
               - Awaiting admin review
               - Action required from admin

🟢 APPROVED   - Green border (#28a745)
               - Admin authorized repair
               - Employee can proceed

🔴 REJECTED   - Red border (#dc3545)
               - Admin denied request
               - Includes explanation

⚫ COMPLETED  - Gray border (#6c757d)
               - Repair finished
               - Includes cost/bill if provided
```

---

## User Flows

### Flow 1: Employee Submits Request

```
1. Employee → Service Requests
         ↓
2. Click "New Service Request"
         ↓
3. Fill Form:
   - Select asset (Dell Laptop)
   - Problem: "Screen flickering"
   - Since: Feb 15, 2026
   - Remarks: "When plugged in"
         ↓
4. Submit Request
         ↓
5. See Confirmation
         ↓
6. Status: PENDING (yellow card)
         ↓
7. Admin receives email
```

### Flow 2: Admin Reviews Request

```
1. Admin → Service Requests
         ↓
2. See PENDING count: 12
         ↓
3. Click on request card
         ↓
4. Review details:
   - Employee: John Doe
   - Asset: Dell Laptop
   - Problem: Screen flickering
   - Since: Feb 15, 2026
         ↓
5. Decision Point:
   ├─> Click APPROVE
   │   ├─> Add response
   │   ├─> Confirm
   │   └─> Employee notified
   │
   └─> Click REJECT
       ├─> Add explanation
       ├─> Confirm
       └─> Employee notified
```

### Flow 3: Employee Completes Repair

```
1. Employee → Service Requests
         ↓
2. Find APPROVED request
         ↓
3. Click "Mark Completed"
         ↓
4. Fill Optional Fields:
   - Repair Amount: $150.00
   - Currency: USD
   - Upload: invoice.pdf
         ↓
5. Confirm Completion
         ↓
6. Status: COMPLETED (gray card)
         ↓
7. Admin can view bill/amount
```

---

## Mobile Responsive Design

### Mobile View (375px width)

```
┌─────────────────────────┐
│ ☰ Asset Service Req.   │
├─────────────────────────┤
│                         │
│ [+ New Request]         │
│                         │
│ Filter: [All ▼]         │
│                         │
│ ┌─────────────────────┐ │
│ │ [PENDING]           │ │
│ │ Dell Laptop         │ │
│ │                     │ │
│ │ Screen flickering   │ │
│ │ Since: Feb 15       │ │
│ │                     │ │
│ │ Feb 18, 10:30 AM    │ │
│ └─────────────────────┘ │
│                         │
│ ┌─────────────────────┐ │
│ │ [APPROVED]          │ │
│ │ HP Monitor          │ │
│ │                     │ │
│ │ Dead pixels         │ │
│ │ Since: Feb 10       │ │
│ │                     │ │
│ │ ✅ Approved for     │ │
│ │ warranty repair     │ │
│ │                     │ │
│ │ [Mark Completed]    │ │
│ └─────────────────────┘ │
│                         │
└─────────────────────────┘
```

---

## Email Notifications

### Email 1: New Request (To Admin)

```
Subject: New Asset Service Request

Dear Admin,

Employee John Doe has submitted a service request for Dell Latitude 5420.

Problem: Screen is flickering intermittently when plugged into power.

Problem Since: February 15, 2026

Please review and take action.

[View Request in System]

---
Asset Management System
```

### Email 2: Request Approved (To Employee)

```
Subject: Service Request Approved

Dear John Doe,

Your service request for Dell Latitude 5420 (SN: SN-LAP-001) has been approved.

Admin Response: Your service request has been approved. Please contact 
IT support to schedule the repair. The laptop will be covered under warranty.

You can now proceed with the repair/service. Once completed, please update 
the request status.

Thank you.

[View Request Details]

---
Asset Management System
```

### Email 3: Request Rejected (To Employee)

```
Subject: Service Request Rejected

Dear John Doe,

Your service request for Wireless Mouse (SN: SN-MOU-034) has been rejected.

Admin Response: This issue appears to be user error. The battery drain is 
normal for wireless mice. Please use the provided charging dock or replace 
batteries as needed.

[View Request Details]

---
Asset Management System
```

---

## Database Schema

```
asset_service_requests
├── id (INT, PK, AUTO_INCREMENT)
├── employee_id (INT, FK → users.id)
├── asset_id (INT, FK → assets.id)
├── problem_description (TEXT, REQUIRED)
├── problem_since (DATE, REQUIRED)
├── remarks (TEXT, OPTIONAL)
├── status (ENUM: pending, approved, rejected, completed)
├── admin_response (TEXT, OPTIONAL)
├── repair_amount (DECIMAL(10,2), OPTIONAL)
├── repair_currency (ENUM: USD, INR)
├── bill_attachment (VARCHAR(255), OPTIONAL)
├── created_at (TIMESTAMP, DEFAULT NOW)
├── updated_at (TIMESTAMP, AUTO UPDATE)
├── approved_at (TIMESTAMP, NULL)
├── approved_by (INT, FK → users.id)
└── completed_at (TIMESTAMP, NULL)

INDEXES:
- idx_employee (employee_id)
- idx_asset (asset_id)
- idx_status (status)
- idx_created (created_at)
```

---

## File Structure

```
company-assets/
├── employee/
│   └── service_request.php .......... Employee interface
├── admin/
│   └── service_requests.php ......... Admin management
├── uploads/
│   ├── .htaccess .................... Security config
│   └── service_bills/ ............... Bill storage
│       ├── bill_1_1708437600.pdf
│       ├── bill_2_1708438200.jpg
│       └── ...
└── database.sql ..................... Schema definition
```

---

*This visual guide provides a comprehensive overview of the Asset Service Request System UI and workflows.*
