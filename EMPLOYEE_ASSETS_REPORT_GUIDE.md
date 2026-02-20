# Employee Assets Detailed Report - User Guide

## Overview
The Employee Assets Detailed Report is a comprehensive reporting tool that allows administrators to view, filter, search, sort, and export detailed information about employees and their assigned assets.

## Access
**URL:** `admin/employee_assets_report.php`
**Required Role:** Admin
**Menu Location:** Admin Sidebar → "Employee Assets Report"

## Features

### 1. Available Stock Summary
At the top of the page, you'll see a summary of available assets organized by category:
- **Category Name**: The asset category
- **Count**: Number of available assets in that category
- **Value**: Total monetary value of available assets

This gives you an instant overview of what's currently in stock.

### 2. Advanced Filters

#### Search by Employee
- **Field:** Text input in the filter section
- **Search Criteria:** Employee name or email
- **Example:** Type "john" to find all employees with "john" in their name or email
- **Behavior:** Case-insensitive, partial match

#### Filter by Asset Category
- **Field:** Dropdown menu
- **Options:** All available asset categories
- **Effect:** Shows only employees who have assets from the selected category
- **Default:** "All Categories"

#### Filter by Employee Status
- **Field:** Dropdown menu
- **Options:** 
  - All Status (default)
  - Active - Shows only active employees
  - Inactive - Shows only inactive employees

#### Apply/Reset Filters
- **Filter Button**: Click to apply the selected filters
- **Reset Button**: Clears all filters and shows all employees

### 3. Sorting Options

The report provides three quick-sort buttons:
- **Name A-Z**: Sort employees alphabetically by name (ascending)
- **Name Z-A**: Sort employees alphabetically by name (descending)
- **Most Assets**: Sort employees by number of assigned assets (descending)

### 4. Employee Assets Table

The main table displays the following columns:

#### Employee Name
- Primary employee name
- "Since" date showing when the employee joined
- Example: "John Doe" with "Since: Jan 2024"

#### Email
- Employee's email address

#### Status
- **Active**: Green badge
- **Inactive**: Red badge

#### Assets Assigned
- Number of assets currently assigned to the employee
- Displayed as a blue badge
- Shows "No assets" if none assigned

#### Categories
- Asset categories as colored badges
- Multiple categories shown if employee has assets from different types
- Example: "Laptop", "Monitor", "Keyboard"

#### Total Value
- Sum of the value of all assigned assets
- Displayed in USD currency format
- Shows "$0.00" if no assets assigned

#### Details
- **View Button**: Opens a modal with detailed asset list
- Only visible if employee has assigned assets
- Modal shows each asset name with serial number

### 5. Pagination

- **Records per Page**: 25 employees
- **Navigation**: Previous/Next buttons
- **Page Numbers**: Quick jump to specific pages
- **Current Position**: Shows "Showing X to Y of Z employees"

### 6. Export Functionality

#### Export to CSV Button
- **Location**: Top-right of the filter section (green button)
- **Icon**: Excel file icon
- **Behavior**: Exports current view with applied filters
- **File Format**: CSV with UTF-8 encoding
- **Filename**: `employee_assets_report_YYYY-MM-DD_HHMMSS.csv`

#### What Gets Exported
- Employee ID
- Employee Name
- Email
- Status (Active/Inactive)
- Member Since date
- Assets Count
- Asset Categories (comma-separated)
- Total Asset Value
- Assets Details (full list with serial numbers)

#### Export with Filters
The export respects all applied filters:
- Search term
- Category filter
- Status filter

This means you can:
1. Apply filters to narrow down employees
2. Export only the filtered results
3. Get a focused dataset for analysis

## Use Cases

### Use Case 1: Find Active Employees with Laptops
1. Set "Employee Status" to "Active"
2. Set "Asset Category" to "Laptops"
3. Click "Filter"
4. View results or export to CSV

### Use Case 2: Search for a Specific Employee
1. Type employee name or email in search box
2. Click "Filter" or press Enter
3. View their assigned assets
4. Click "View" to see detailed asset list

### Use Case 3: Identify Employees with Most Assets
1. Click "Most Assets" sort button
2. View employees sorted by asset count
3. Export the list if needed

### Use Case 4: Check Available Stock
1. View the "Available Assets in Stock" cards at the top
2. See count and value by category
3. Plan asset assignments based on availability

### Use Case 5: Export All Employee-Asset Data
1. Don't apply any filters (or click Reset)
2. Click "Export to CSV"
3. Open in Excel or other spreadsheet software
4. Perform additional analysis

### Use Case 6: Quarterly Asset Review
1. Export all employee-asset data
2. Review asset distribution
3. Identify employees without assets
4. Plan for new asset purchases based on stock levels

## Data Interpretation

### Employee with No Assets
- **Assets Assigned**: Shows "No assets"
- **Categories**: Shows "-"
- **Total Value**: Shows "$0.00"
- **Details**: Shows "-"

### Employee with Multiple Assets
- **Assets Assigned**: Shows count badge (e.g., "3 Assets")
- **Categories**: Multiple badges for different categories
- **Total Value**: Sum of all asset values
- **Details**: "View" button opens modal with full list

### Available Stock Interpretation
- High count = Good availability for assignments
- Low count = May need to purchase more
- Value shows total investment in available assets

## Tips and Best Practices

### Efficient Searching
- Use partial names: "john" finds "Johnson", "John Doe", etc.
- Search by email domain: "@company.com" finds all company emails
- Combine search with filters for precise results

### Effective Filtering
- Use category filter to check asset distribution by type
- Use status filter to separate active/inactive employees
- Combine multiple filters for targeted reports

### Export Strategy
- Export regularly for record-keeping
- Use filters to create focused reports
- Include timestamp in analysis notes

### Regular Reviews
- Monthly: Check asset distribution
- Quarterly: Export full report for analysis
- Annually: Review total asset values and depreciation

### Asset Planning
- Check available stock before approving requests
- Monitor category-wise distribution
- Plan purchases based on low stock levels

## Technical Notes

### Performance
- Page loads 25 records at a time for optimal performance
- Filters are applied at database level for efficiency
- Export processes all matching records (not just current page)

### Security
- Admin-only access with role verification
- CSRF token protection on export
- Input sanitization on all filters
- Audit logging for all export actions

### Browser Compatibility
- Works in all modern browsers
- Responsive design for tablets and desktops
- Excel-compatible CSV export

## Troubleshooting

### No Results Found
**Problem**: Filter returns no results
**Solution**: 
- Click "Reset" to clear filters
- Verify filter criteria
- Check if employees exist in database

### Export Not Working
**Problem**: CSV file not downloading
**Solution**:
- Check browser popup blocker
- Ensure you're logged in as admin
- Verify CSRF token is valid (refresh page)

### Values Not Showing
**Problem**: Total value shows $0.00 for employees with assets
**Solution**:
- Assets may not have price information
- Check asset records in Asset Management
- Update asset prices as needed

### Modal Not Opening
**Problem**: "View" button doesn't show details
**Solution**:
- Ensure JavaScript is enabled
- Check browser console for errors
- Refresh the page

## Related Features

- **Asset Management**: Manage individual assets
- **Asset Assignments**: View all assignments
- **Reports & Export**: Quick export options
- **Users Management**: Manage employee accounts

## Support

For issues or questions:
1. Check audit logs for export history
2. Verify your admin permissions
3. Review the database queries in the code
4. Contact system administrator

---

**Version:** 1.0
**Last Updated:** 2026-02-19
**Author:** Asset Management System
