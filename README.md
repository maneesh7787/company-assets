# Asset Management Portal

A complete, secure Asset Management Portal for Business Marketing Companies built with PHP, MySQL, Bootstrap, and jQuery.

## Features

### Security
- Password hashing with `password_hash()` and `password_verify()`
- Prepared statements for all database queries (SQL injection prevention)
- Session-based authentication with session regeneration
- Role-based access control (Admin, HR, Employee)
- CSRF token protection on all forms
- XSS prevention with output escaping
- Input validation and sanitization
- Audit logging for all important actions
- Inactive user blocking

### User Roles

#### Admin/IT
- Dashboard with comprehensive statistics
- User management (add, edit, activate/deactivate, password reset)
- Asset category management
- Asset management (add, edit, delete, assign, swap, return)
- Asset request approval/rejection
- Asset assignments tracking
- Audit logs with filtering
- CSV export (assets, employees, audit logs)

#### HR
- View-only dashboard
- View all employees
- View asset assignments
- View reports and statistics
- Cannot modify data

#### Employee
- View assigned assets
- Submit asset requests
- View request history
- Edit profile
- Change password

### Database Tables
- `users` - User accounts with roles
- `asset_categories` - Asset category definitions
- `assets` - Asset inventory
- `asset_requests` - Employee asset requests
- `request_items` - Items in each request
- `asset_assignments` - Asset assignment tracking
- `audit_logs` - Complete audit trail

## Installation

1. **Import Database**
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configure Database Connection**
   Edit `config.php` and update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'asset_management');
   ```

3. **Set Permissions**
   Ensure web server has read access to all files.

4. **Access Application**
   Navigate to: `http://localhost/company-assets/`

## Default Login

**Admin Account:**
- Email: `admin@company.com`
- Password: `Admin@123`

**Note:** Change the default password immediately after first login.

## Password Requirements

- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character (@$!%*?&#)

## Email Notifications

Email notifications are sent for:
- New asset requests (to Admin and HR)
- Asset assignments (to employees)
- Request approvals/rejections (to employees)

**Note:** Email functionality uses PHP `mail()` function. Configure a mail server or update `email.php` to use SMTP for production.

## Security Best Practices

1. **Production Deployment:**
   - Change `display_errors` to `0` in config.php
   - Use HTTPS (update session.cookie_secure in security.php)
   - Configure proper mail server
   - Use strong database passwords
   - Restrict file permissions

2. **Regular Maintenance:**
   - Review audit logs regularly
   - Update user passwords periodically
   - Remove inactive users
   - Backup database regularly

## Technology Stack

- **Backend:** PHP 7.4+ (Procedural)
- **Database:** MySQL 5.7+ with mysqli
- **Frontend:** HTML5, CSS3, Bootstrap 5.1.3
- **JavaScript:** jQuery 3.6.0
- **Icons:** Font Awesome 6.0
- **Tables:** DataTables 1.11.5

## File Structure

```
company-assets/
├── admin/              # Admin panel pages
├── hr/                 # HR panel pages
├── employee/           # Employee panel pages
├── config.php          # Configuration
├── db.php              # Database connection
├── security.php        # Security functions
├── email.php           # Email utilities
├── login.php           # Login page
├── logout.php          # Logout handler
├── dashboard.php       # Dashboard router
├── layout.php          # Layout template
├── database.sql        # Database schema
└── .htaccess           # Apache security rules
```

## Support

For issues or questions, please contact the system administrator.

## License

Proprietary - Internal Use Only