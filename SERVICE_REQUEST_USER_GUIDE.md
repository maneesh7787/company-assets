# Asset Service/Support Request System - User Guide

## Overview

The Asset Service/Support Request System enables employees to request repairs and maintenance for their assigned assets through a streamlined workflow with admin oversight.

## Table of Contents

1. [Employee Workflow](#employee-workflow)
2. [Admin Workflow](#admin-workflow)
3. [Features](#features)
4. [Security](#security)
5. [Troubleshooting](#troubleshooting)

---

## Employee Workflow

### 1. Submitting a Service Request

**Navigation:** Employee Dashboard → Service Requests → "New Service Request"

**Steps:**
1. Click the "New Service Request" button
2. Select the asset that needs service from your assigned assets
3. Describe the problem in detail
4. Select the date when you first noticed the problem
5. Add any additional remarks (optional)
6. Click "Submit Request"

**Required Information:**
- Asset selection (must be assigned to you)
- Problem description (detailed explanation)
- Problem since date (when issue started)

**Optional Information:**
- Additional remarks or context

**After Submission:**
- Request status: **Pending**
- Admin receives email notification
- You can track status on the Service Requests page

### 2. Tracking Request Status

**Status Types:**
- **Pending** (Yellow) - Awaiting admin review
- **Approved** (Green) - Admin approved, proceed with repair
- **Rejected** (Red) - Admin rejected the request
- **Completed** (Gray) - Repair completed and confirmed

**View Details:**
- All your service requests are displayed as cards
- Each card shows:
  - Asset name and serial number
  - Problem description
  - Problem since date
  - Current status
  - Admin response (if any)
  - Submission date and time

### 3. Marking Request as Completed

**When to Complete:**
- Only after the asset has been repaired/serviced
- Request must be in "Approved" status

**Steps:**
1. Find the approved request on your Service Requests page
2. Click the "Mark Completed" button
3. In the modal:
   - **Repair Amount** (Optional): Enter the cost of repair
   - **Currency**: Select USD or INR
   - **Upload Bill** (Optional): Attach invoice/receipt (PDF, JPG, PNG)
4. Click "Confirm Completion"

**File Upload Guidelines:**
- Accepted formats: PDF, JPG, JPEG, PNG
- Maximum file size: 5MB (recommended)
- File should be a clear invoice or receipt

---

## Admin Workflow

### 1. Viewing Service Requests

**Navigation:** Admin Dashboard → Service Requests

**Dashboard Overview:**
- Statistics cards showing counts:
  - Pending requests (yellow)
  - Approved requests (green)
  - Completed requests (gray)
  - Rejected requests (red)

### 2. Filtering and Searching

**Filter Options:**
- **Search**: Search by asset name, serial number, or employee name
- **Status**: Filter by pending, approved, rejected, or completed
- **Employee**: View requests from a specific employee

**How to Filter:**
1. Enter search terms or select filters
2. Click "Filter" button
3. Click "Reset" to clear all filters

### 3. Approving or Rejecting Requests

**Review Process:**
1. Review the request card showing:
   - Employee details
   - Asset information
   - Problem description
   - Problem since date
   - Employee remarks

2. Click either:
   - **Approve** button (green) - To allow repair
   - **Reject** button (red) - To deny request

3. In the modal:
   - Add a response message for the employee
   - Explain your decision or provide instructions
   - Click "Confirm"

**After Action:**
- Employee receives email notification
- Request status updates
- Your response is visible to the employee
- Action is logged in audit trail

### 4. Viewing Completed Requests

**Information Available:**
- Completion date
- Repair amount (if provided)
- Bill/invoice (if uploaded)
- Complete history of the request

**Downloading Bills:**
- Click "View Bill" button on completed requests
- Bill opens in new tab for viewing/downloading

---

## Features

### Employee Features

#### Service Request Page
- **Card-Based Layout**: Visual, easy-to-scan interface
- **Status Filtering**: Quick filter by request status
- **Color Coding**: Instant visual status recognition
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Real-time Updates**: See admin responses immediately

#### Request Submission
- **Asset Validation**: Only assigned assets appear
- **Date Picker**: Easy date selection for "problem since"
- **Text Areas**: Ample space for detailed descriptions
- **CSRF Protection**: Secure form submission

#### Completion Tracking
- **Optional Fields**: Flexibility in providing repair details
- **File Upload**: Easy bill attachment
- **Currency Support**: USD and INR options
- **Visual Feedback**: Success/error messages

### Admin Features

#### Dashboard Analytics
- **Statistics Cards**: Visual overview of all requests
- **Color-Coded Metrics**: Quick status identification
- **Real-time Counts**: Always up-to-date numbers

#### Management Tools
- **Multi-Filter Search**: Combine multiple criteria
- **Bulk Operations**: Efficient request handling
- **Detailed Views**: All information in one place
- **Action Buttons**: Quick approve/reject

#### Reporting
- **Complete History**: Track all requests
- **Bill Management**: View and download invoices
- **Audit Trail**: All actions logged
- **Email Notifications**: Stay informed of new requests

---

## Security

### Data Protection
- **CSRF Tokens**: All forms protected against cross-site attacks
- **Role-Based Access**: Employees and admins have appropriate permissions
- **Input Validation**: All data sanitized and validated
- **SQL Injection Prevention**: Prepared statements used throughout

### File Upload Security
- **Type Validation**: Only PDF, JPG, PNG allowed
- **Extension Verification**: Double-check file types
- **Unique Filenames**: Prevents overwriting and conflicts
- **.htaccess Protection**: Prevents PHP execution in uploads directory
- **Secure Storage**: Files stored outside web root when possible

### Audit Trail
- **Action Logging**: All service request actions logged
- **User Attribution**: Every action tied to a user
- **Timestamp Recording**: Precise action timing
- **Complete History**: Full audit trail maintained

### Email Security
- **Notification System**: Automated but controlled
- **Content Filtering**: Safe email content
- **Recipient Validation**: Only intended recipients receive emails

---

## Troubleshooting

### Common Issues

#### Issue: Cannot submit service request
**Possible Causes:**
- No assets assigned to you
- Required fields not filled
- Network connectivity issue

**Solutions:**
1. Verify you have assets assigned (check "My Assets" page)
2. Ensure all required fields are filled:
   - Asset selected
   - Problem description entered
   - Problem since date selected
3. Check internet connection
4. Contact admin if issue persists

#### Issue: "Mark Completed" button not appearing
**Cause:** Request is not in "Approved" status

**Solution:** Wait for admin to approve your request first

#### Issue: File upload fails
**Possible Causes:**
- File too large
- Wrong file format
- Upload permissions issue

**Solutions:**
1. Ensure file is under 5MB
2. Use only PDF, JPG, or PNG format
3. Try a different file
4. Contact IT if problem continues

#### Issue: Cannot see admin response
**Cause:** Page not refreshed after admin action

**Solution:** Refresh the page (F5) to see latest updates

### Admin Troubleshooting

#### Issue: Cannot approve/reject request
**Possible Causes:**
- Session timeout
- CSRF token expired
- Network issue

**Solutions:**
1. Refresh the page and try again
2. Log out and log back in
3. Check internet connection

#### Issue: Email notifications not sending
**Possible Causes:**
- Email configuration issue
- SMTP server down
- Invalid email addresses

**Solutions:**
1. Check email configuration in config.php
2. Verify SMTP settings
3. Test email functionality
4. Contact system administrator

---

## Best Practices

### For Employees

1. **Be Detailed**: Provide comprehensive problem descriptions
2. **Act Quickly**: Submit requests as soon as issues are noticed
3. **Keep Records**: Save bills and receipts for uploaded documentation
4. **Follow Up**: Check request status regularly
5. **Communicate**: Use remarks field for additional context

### For Admins

1. **Respond Promptly**: Review pending requests daily
2. **Be Clear**: Provide detailed responses to employees
3. **Track Patterns**: Monitor recurring issues
4. **Budget Planning**: Use completion data for budget forecasting
5. **Regular Reviews**: Audit completed requests periodically

---

## Technical Specifications

### Database
- **Table**: `asset_service_requests`
- **Relationships**: Links to users, assets
- **Indexes**: Optimized for fast querying
- **Constraints**: Foreign keys ensure data integrity

### File Storage
- **Location**: `uploads/service_bills/`
- **Naming**: `bill_{request_id}_{timestamp}.{extension}`
- **Security**: .htaccess prevents code execution
- **Backup**: Include in regular backup procedures

### Performance
- **Page Load**: < 2 seconds typical
- **Filter Response**: < 1 second
- **File Upload**: Depends on file size and connection
- **Email Delivery**: Usually < 30 seconds

---

## Support

### Getting Help

**For Employees:**
- Check this guide first
- Contact your supervisor
- Email IT support

**For Admins:**
- Review system documentation
- Check audit logs for issues
- Contact system administrator

### Feature Requests

Have ideas for improvements? Submit feedback through:
- IT support ticket system
- Email to system administrator
- Team meetings

---

## Glossary

**Service Request**: A formal request to repair or maintain an asset

**Pending**: Request submitted, awaiting admin review

**Approved**: Admin authorized the repair to proceed

**Rejected**: Admin denied the request (with explanation)

**Completed**: Repair finished and confirmed by employee

**CSRF Token**: Security measure to prevent unauthorized form submissions

**Audit Log**: Record of all system actions for security and tracking

---

## Version History

**Version 1.0** - Initial Release (February 2026)
- Service request submission
- Admin approval workflow
- Completion tracking with bills
- Email notifications
- Audit logging

---

*For technical support, please contact your system administrator.*
