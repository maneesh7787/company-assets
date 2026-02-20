# Employee Assets Report - Page Layout Preview

## Page Structure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ SIDEBAR                │ MAIN CONTENT AREA                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│ Dashboard              │ ┌────────────────────────────────────────────────┐ │
│ User Management        │ │ Available Assets in Stock                      │ │
│ Categories             │ ├────────────────────────────────────────────────┤ │
│ Asset Management       │ │ Laptops    Monitors   Keyboards    Mice        │ │
│ Assignments            │ │   15         23         45          30         │ │
│ Requests               │ │ $45,000   $11,500     $2,250      $1,200       │ │
│ Audit Logs             │ └────────────────────────────────────────────────┘ │
│ Reports & Export       │                                                     │
│ ★ Employee Assets ★    │ ┌────────────────────────────────────────────────┐ │
│                        │ │ Filters & Search                    [Reset] [↓CSV]│
│                        │ ├────────────────────────────────────────────────┤ │
│                        │ │ [Search Employee] [Category▼] [Status▼] [Filter]│
│                        │ └────────────────────────────────────────────────┘ │
│                        │                                                     │
│                        │ Showing 1 to 25 of 150   [A-Z][Z-A][Most Assets]  │
│                        │                                                     │
│                        │ ┌────────────────────────────────────────────────┐ │
│                        │ │ Employee Assets Details                        │ │
│                        │ ├──────────┬────────┬────────┬────────┬─────────┤ │
│                        │ │ Name     │ Email  │ Status │ Assets │ Value   │ │
│                        │ ├──────────┼────────┼────────┼────────┼─────────┤ │
│                        │ │ John Doe │ john@  │ Active │   3    │ $4,500  │ │
│                        │ │ Jane S.  │ jane@  │ Active │   2    │ $3,200  │ │
│                        │ │ ...      │ ...    │ ...    │ ...    │ ...     │ │
│                        │ └──────────┴────────┴────────┴────────┴─────────┘ │
│                        │                                                     │
│                        │ [← Prev] [1] [2] [3] ... [6] [Next →]             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Component Details

### 1. Available Stock Cards (Top Section)

```
┌─────────────────────────────────────────────────────────────────────┐
│  Available Assets in Stock                                          │
├─────────────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │ Laptops  │  │ Monitors │  │ Keyboards│  │   Mice   │           │
│  │    15    │  │    23    │  │    45    │  │    30    │           │
│  │ $45,000  │  │ $11,500  │  │ $2,250   │  │ $1,200   │           │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘           │
└─────────────────────────────────────────────────────────────────────┘
```

### 2. Filter Panel

```
┌─────────────────────────────────────────────────────────────────────┐
│  Filters & Search                           [🔄 Reset] [📥 Export]  │
├─────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌──────────────┐  ┌──────────┐  ┌────────┐  │
│  │ Search Employee │  │ Category  ▼  │  │ Status ▼ │  │ Filter │  │
│  │ john@company... │  │ Laptops      │  │ Active   │  │        │  │
│  └─────────────────┘  └──────────────┘  └──────────┘  └────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

### 3. Results Summary & Sorting

```
┌─────────────────────────────────────────────────────────────────────┐
│  Showing 1 to 25 of 150 employees                                   │
│                                                                     │
│                    [Name A-Z] [Name Z-A] [Most Assets]             │
└─────────────────────────────────────────────────────────────────────┘
```

### 4. Employee Details Table

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Employee Assets Details                                                    │
├───────────────┬──────────────┬────────┬─────────┬────────────┬──────────────┤
│ Employee      │ Email        │ Status │ Assets  │ Categories │ Total Value  │
│ Name          │              │        │ Count   │            │              │
├───────────────┼──────────────┼────────┼─────────┼────────────┼──────────────┤
│ John Doe      │ john@co.com  │ Active │ [3]     │ Laptop     │ $4,500       │
│ Since: Jan 24 │              │        │         │ Monitor    │              │
│               │              │        │         │            │ [👁️ View]   │
├───────────────┼──────────────┼────────┼─────────┼────────────┼──────────────┤
│ Jane Smith    │ jane@co.com  │ Active │ [2]     │ Laptop     │ $3,200       │
│ Since: Mar 24 │              │        │         │            │              │
│               │              │        │         │            │ [👁️ View]   │
├───────────────┼──────────────┼────────┼─────────┼────────────┼──────────────┤
│ Bob Wilson    │ bob@co.com   │ Inactive│ [1]    │ Monitor    │ $800         │
│ Since: Dec 23 │              │        │         │            │              │
│               │              │        │         │            │ [👁️ View]   │
├───────────────┼──────────────┼────────┼─────────┼────────────┼──────────────┤
│ Alice Brown   │ alice@co.com │ Active │ No      │ -          │ $0.00        │
│ Since: May 24 │              │        │ assets  │            │              │
│               │              │        │         │            │ -            │
└───────────────┴──────────────┴────────┴─────────┴────────────┴──────────────┘
```

### 5. Details Modal (When Clicking "View")

```
┌─────────────────────────────────────────────────────────────┐
│  Assets Assigned to John Doe                           [×]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  💻 Dell Latitude 5420 (SN-LAP-001)                        │
│  ─────────────────────────────────────────────────────      │
│                                                             │
│  🖥️  HP E24 G4 Monitor (SN-MON-045)                        │
│  ─────────────────────────────────────────────────────      │
│                                                             │
│  ⌨️  Logitech MX Keys (SN-KEY-123)                          │
│  ─────────────────────────────────────────────────────      │
│                                                             │
│                                    [Close]                  │
└─────────────────────────────────────────────────────────────┘
```

### 6. Pagination Controls

```
┌─────────────────────────────────────────────────────────────────────┐
│  [← Previous]  [1]  [2]  [3]  [4]  [5]  [6]  [Next →]              │
│                     ^^^                                             │
│                  Active Page                                        │
└─────────────────────────────────────────────────────────────────────┘
```

## Color Scheme

### Status Badges
- **Active**: 🟢 Green badge with white text
- **Inactive**: 🔴 Red badge with white text

### Asset Count Badges
- **Has Assets**: 🔵 Blue rounded pill badge
- **No Assets**: ⚪ Gray muted text

### Category Badges
- **All Categories**: 🔷 Info blue badges
- **Multiple**: Displayed side-by-side with spacing

### Action Buttons
- **View**: 🔵 Outline primary button
- **Filter**: 🔵 Primary blue button
- **Reset**: ⚪ Secondary gray button
- **Export**: 🟢 Success green button

## Responsive Behavior

### Desktop (1920x1080)
```
┌─────────────────────────────────────────────────────────────────┐
│ [Sidebar 250px] [Content Area Full Width]                      │
│                                                                 │
│ Stock Cards: 4 columns                                          │
│ Table: All columns visible                                      │
│ Filters: Single row, inline                                     │
└─────────────────────────────────────────────────────────────────┘
```

### Tablet (768x1024)
```
┌─────────────────────────────────────────────────────────────────┐
│ [Content Full Width, Sidebar Collapsible]                      │
│                                                                 │
│ Stock Cards: 2 columns                                          │
│ Table: Horizontal scroll                                        │
│ Filters: Stacked in grid                                        │
└─────────────────────────────────────────────────────────────────┘
```

### Mobile (375x667)
```
┌───────────────────────────────────────┐
│ [Content Full Width]                 │
│ [Hamburger Menu]                      │
│                                       │
│ Stock Cards: 1 column, stacked        │
│ Table: Horizontal scroll              │
│ Filters: Stacked vertically           │
│ Buttons: Full width                   │
└───────────────────────────────────────┘
```

## User Flow Examples

### Example 1: Search for Employee
```
1. User types "john" in search box
   ┌─────────────────┐
   │ john@company... │
   └─────────────────┘
   
2. Clicks Filter button
   [Filter]
   
3. Results update instantly
   Found 3 employees matching "john"
   
4. Click Export to save results
   [📥 Export to CSV]
```

### Example 2: Filter by Category
```
1. User selects category
   ┌──────────────┐
   │ Laptops   ▼  │
   └──────────────┘
   
2. Clicks Filter
   [Filter]
   
3. See only employees with laptops
   12 employees found
   
4. Sort by most assets
   [Most Assets]
```

### Example 3: View Employee Details
```
1. Find employee in table
   John Doe | john@co.com | Active | 3 | ...
   
2. Click View button
   [👁️ View]
   
3. Modal opens with asset list
   ┌─────────────────────────────┐
   │ Assets Assigned to John Doe │
   │ - Laptop                    │
   │ - Monitor                   │
   │ - Keyboard                  │
   └─────────────────────────────┘
```

## Export CSV Format

```
Employee ID, Employee Name, Email, Status, Member Since, Assets Count, ...
1, John Doe, john@company.com, Active, 2024-01-15, 3, Laptop;Monitor, ...
2, Jane Smith, jane@company.com, Active, 2024-03-20, 2, Laptop, ...
3, Bob Wilson, bob@company.com, Inactive, 2023-12-10, 1, Monitor, ...
```

## Key Metrics Displayed

### Per Employee
- Total Assets Count
- Total Asset Value
- Asset Categories
- Join Date
- Current Status

### Overall
- Total Employees
- Available Stock by Category
- Stock Value by Category

## Performance Indicators

### Page Load Time
- Initial load: < 2 seconds
- Filter application: < 1 second
- Export generation: < 3 seconds (for 1000 records)

### Database Queries
- Main query: 1 optimized JOIN
- Stock query: 1 GROUP BY query
- Category list: 1 simple SELECT
- Total: 3 queries per page load

### Resource Usage
- Memory: ~2MB per page
- Database load: Minimal with indexes
- Network: ~50KB HTML + assets

---

**Page URL:** `admin/employee_assets_report.php`
**Access Level:** Admin Only
**Supported Browsers:** Chrome, Firefox, Safari, Edge (latest versions)
