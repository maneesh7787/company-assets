# Installation Guide

## Quick Start Guide for Asset Management Portal

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Composer (optional, not required for this project)

### Step 1: Setup Database

1. **Create Database:**
   ```bash
   mysql -u root -p
   ```
   
2. **Import Schema:**
   ```bash
   mysql -u root -p < database.sql
   ```
   
   This will:
   - Create `asset_management` database
   - Create all required tables
   - Insert default admin user
   - Insert sample asset categories

### Step 2: Configure Application

1. **Edit Database Configuration:**
   Open `config.php` and update:
   ```php
   define('DB_HOST', 'localhost');     // Your MySQL host
   define('DB_USER', 'root');          // Your MySQL username
   define('DB_PASS', '');              // Your MySQL password
   define('DB_NAME', 'asset_management');
   ```

2. **Update Application URL (Optional):**
   ```php
   define('APP_URL', 'http://localhost/company-assets');
   ```

3. **Configure Email (For Production):**
   Edit `email.php` to use SMTP instead of PHP mail():
   - Recommended: Use PHPMailer or similar library
   - Configure SMTP server details
   - Update email credentials

### Step 3: Set Permissions

```bash
# Make files readable by web server
chmod -R 755 /path/to/company-assets

# Ensure web server user can read files
chown -R www-data:www-data /path/to/company-assets  # Ubuntu/Debian
# OR
chown -R apache:apache /path/to/company-assets      # CentOS/RHEL
```

### Step 4: Apache Configuration

1. **Enable mod_rewrite:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. **Configure Virtual Host (Optional but Recommended):**
   Create `/etc/apache2/sites-available/asset-management.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName assets.yourcompany.com
       DocumentRoot /var/www/html/company-assets
       
       <Directory /var/www/html/company-assets>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/assets-error.log
       CustomLog ${APACHE_LOG_DIR}/assets-access.log combined
   </VirtualHost>
   ```
   
   Enable the site:
   ```bash
   sudo a2ensite asset-management
   sudo systemctl restart apache2
   ```

### Step 5: Access Application

1. **Navigate to:**
   ```
   http://localhost/company-assets/
   ```
   OR
   ```
   http://assets.yourcompany.com/
   ```

2. **Default Login:**
   - **Email:** admin@company.com
   - **Password:** Admin@123

3. **⚠️ IMPORTANT:** Change default password immediately after first login!

### Step 6: Create Additional Users

1. Login as admin
2. Go to "User Management"
3. Add HR users and Employee users
4. Assign appropriate roles

### Step 7: Initial Setup

1. **Add Asset Categories:**
   - Go to "Asset Categories"
   - Add your organization's asset types

2. **Add Assets:**
   - Go to "Asset Management"
   - Add your company's assets

3. **Add Employees:**
   - Go to "User Management"
   - Add all employees with role="employee"

### Production Deployment Checklist

- [ ] Update database credentials in `config.php`
- [ ] Set `display_errors` to `0` in `config.php`
- [ ] Enable HTTPS (update `session.cookie_secure` to `1` in `security.php`)
- [ ] Configure SMTP email service
- [ ] Change default admin password
- [ ] Set strong database password
- [ ] Configure regular database backups
- [ ] Review and adjust file permissions
- [ ] Configure SSL certificate
- [ ] Set up error logging
- [ ] Test all features in production environment
- [ ] Configure firewall rules
- [ ] Enable security headers in .htaccess

### Troubleshooting

**Database Connection Error:**
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `config.php`
- Ensure database exists: `SHOW DATABASES;`

**Permission Denied:**
- Check file permissions: `ls -la`
- Ensure web server user has read access
- Check Apache error logs: `tail -f /var/log/apache2/error.log`

**Page Not Found:**
- Verify mod_rewrite is enabled
- Check .htaccess file exists
- Verify Apache AllowOverride is set to All

**Session Issues:**
- Check PHP session directory permissions
- Verify session settings in php.ini
- Clear browser cookies

**Email Not Sending:**
- Check PHP mail() configuration
- Review email.php for SMTP setup
- Check server mail logs

### Security Hardening

1. **SSL/TLS:**
   ```bash
   # Install certbot for Let's Encrypt
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d assets.yourcompany.com
   ```

2. **Firewall:**
   ```bash
   # Allow only HTTP/HTTPS
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

3. **Hide PHP Version:**
   In `php.ini`:
   ```ini
   expose_php = Off
   ```

4. **Regular Updates:**
   ```bash
   sudo apt update && sudo apt upgrade
   ```

### Backup Strategy

1. **Database Backup:**
   ```bash
   # Daily backup
   mysqldump -u root -p asset_management > backup_$(date +%Y%m%d).sql
   ```

2. **Automated Backup (Cron):**
   ```bash
   # Edit crontab
   crontab -e
   
   # Add daily backup at 2 AM
   0 2 * * * /usr/bin/mysqldump -u root -pYOURPASS asset_management > /backups/asset_$(date +\%Y\%m\%d).sql
   ```

### Support

For technical support or issues:
1. Check VERIFICATION_REPORT.md for feature verification
2. Review audit logs in the system
3. Check Apache/PHP error logs
4. Contact system administrator

### System Requirements

**Minimum:**
- PHP 7.4+
- MySQL 5.7+
- 512MB RAM
- 1GB disk space

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- 2GB RAM
- 5GB disk space
- SSD storage

### Performance Optimization

1. **Enable PHP OPcache:**
   In `php.ini`:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

2. **MySQL Optimization:**
   ```sql
   -- Add indexes if needed
   CREATE INDEX idx_user_email ON users(email);
   CREATE INDEX idx_asset_serial ON assets(serial_number);
   ```

3. **Enable Gzip Compression:**
   In `.htaccess`:
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
   </IfModule>
   ```

---

**Installation Complete!** 🎉

Your Asset Management Portal is now ready to use. Login with the default admin credentials and start managing your company's assets.
