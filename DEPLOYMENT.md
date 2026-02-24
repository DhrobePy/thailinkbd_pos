# cPanel Deployment Guide
## Thai Link BD Inventory Management System

This guide provides step-by-step instructions for deploying the Thai Link BD Inventory Management System on cPanel-based shared hosting environments.

## Pre-Deployment Checklist

### Hosting Requirements Verification

Before beginning the deployment process, ensure your hosting provider meets the following minimum requirements:

**Server Specifications:**
- PHP version 7.4 or higher (PHP 8.0+ recommended)
- MySQL version 5.7 or higher (MySQL 8.0+ recommended)
- Minimum 256MB PHP memory limit (512MB recommended)
- At least 100MB available disk space
- SSL certificate support (highly recommended)

**PHP Extensions Required:**
- PDO MySQL extension
- JSON extension
- Session extension
- GD extension (for barcode generation)
- cURL extension (for external API calls)
- mbstring extension (for multi-byte string handling)

**cPanel Features Needed:**
- File Manager access
- MySQL Database management
- Subdomain/Domain management
- Cron Jobs (optional, for automated tasks)
- Email accounts (for system notifications)

### Domain and SSL Setup

Ensure your domain is properly configured and pointed to your hosting account. If you plan to use a subdomain for the inventory system, create it before deployment. Setting up an SSL certificate is strongly recommended for security, especially when handling sensitive business data.

## Step-by-Step Deployment Process

### Step 1: Database Preparation

#### Creating the MySQL Database

1. **Access cPanel MySQL Databases**
   - Log into your cPanel account
   - Navigate to the "Databases" section
   - Click on "MySQL Databases"

2. **Create New Database**
   - In the "Create New Database" section
   - Enter database name: `thai_link_inventory`
   - Click "Create Database"
   - Note the full database name (usually prefixed with your username)

3. **Create Database User**
   - Scroll to "MySQL Users" section
   - Enter username: `inventory_user`
   - Generate a strong password (save this securely)
   - Click "Create User"

4. **Assign User to Database**
   - In "Add User to Database" section
   - Select the user and database you just created
   - Grant "ALL PRIVILEGES"
   - Click "Make Changes"

#### Database Configuration Notes

Record the following information for later use:
- Database Host: Usually `localhost`
- Database Name: Your full database name (e.g., `username_thai_link_inventory`)
- Database Username: Your full username (e.g., `username_inventory_user`)
- Database Password: The password you created

### Step 2: File Upload and Extraction

#### Preparing the Application Files

1. **Access File Manager**
   - In cPanel, navigate to "Files" section
   - Click "File Manager"
   - Navigate to your domain's document root (usually `public_html`)

2. **Upload Application Archive**
   - Click "Upload" in the File Manager toolbar
   - Select the `thai-link-inventory.zip` file
   - Wait for upload completion
   - Return to File Manager

3. **Extract Files**
   - Right-click on the uploaded ZIP file
   - Select "Extract"
   - Choose extraction destination (current directory)
   - Click "Extract Files"
   - Delete the ZIP file after extraction

#### File Structure Verification

After extraction, verify the following directory structure exists:

```
public_html/
├── api/
├── config/
├── database/
├── includes/
├── modules/
├── uploads/
├── .htaccess
├── .env.example
├── index.php
├── install.php
└── README.md
```

### Step 3: File Permissions Configuration

#### Setting Proper Permissions

Using File Manager, set the following permissions:

1. **Directory Permissions (755)**
   - `uploads/` directory
   - `backups/` directory (if exists)
   - `config/` directory

2. **File Permissions (644)**
   - `.htaccess` file
   - All PHP files
   - Configuration files

#### Permission Setting Process

1. Right-click on each directory/file
2. Select "Change Permissions"
3. Set appropriate numeric permissions
4. Apply changes

### Step 4: Environment Configuration

#### Creating Environment File

1. **Copy Environment Template**
   - In File Manager, right-click `.env.example`
   - Select "Copy"
   - Rename copy to `.env`

2. **Edit Environment Configuration**
   - Right-click `.env` file
   - Select "Edit"
   - Update the following variables with your database information:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_full_database_name
DB_USER=your_full_database_username
DB_PASS=your_database_password

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Company Information
COMPANY_NAME="Thai Link BD"
COMPANY_ADDRESS="Your Complete Business Address"
COMPANY_PHONE="+880-2-123456789"
COMPANY_EMAIL="info@thailinkbd.com"
COMPANY_WEBSITE="https://thailinkbd.com"

# Security Settings
SESSION_LIFETIME=3600
PASSWORD_MIN_LENGTH=6

# Tax and Currency Settings
DEFAULT_TAX_RATE=15.0
TAX_NUMBER="Your_Tax_Registration_Number"
CURRENCY_CODE=BDT
CURRENCY_SYMBOL="৳"
DECIMAL_PLACES=2

# File Upload Settings
MAX_FILE_SIZE=5242880
UPLOAD_PATH=uploads/

# Notification Settings
LOW_STOCK_ALERT=true
EMAIL_NOTIFICATIONS=true
```

3. **Save Configuration**
   - Click "Save Changes"
   - Ensure file permissions remain 644

### Step 5: Installation Wizard Execution

#### Running the Installation

1. **Access Installation Wizard**
   - Open your web browser
   - Navigate to `https://yourdomain.com/install.php`
   - The installation wizard should load

2. **Complete Installation Steps**

   **Step 1: Welcome Screen**
   - Review system requirements
   - Click "Start Installation"

   **Step 2: Database Configuration**
   - Enter your database credentials
   - Host: `localhost`
   - Database Name: Your full database name
   - Username: Your full database username
   - Password: Your database password
   - Click "Test Connection & Continue"

   **Step 3: Database Setup**
   - Click "Create Database Tables"
   - Wait for table creation and seed data import
   - Verify success message

   **Step 4: Admin User Setup**
   - Full Name: Enter administrator's full name
   - Username: Choose admin username (avoid 'admin' for security)
   - Email: Enter valid administrator email
   - Password: Create strong password (minimum 6 characters)
   - Click "Create Admin User"

   **Step 5: Installation Complete**
   - Review completion message
   - Note any additional security recommendations
   - Click "Access Your System"

#### Post-Installation Security

1. **Delete Installation File**
   - Return to File Manager
   - Delete `install.php` file immediately
   - This prevents unauthorized reinstallation

2. **Verify Installation Lock**
   - Check that `config/installed.lock` file exists
   - This file prevents accidental reinstallation

### Step 6: SSL Certificate Configuration

#### Enabling HTTPS (Recommended)

1. **SSL Certificate Setup**
   - In cPanel, navigate to "Security" section
   - Click "SSL/TLS"
   - Install SSL certificate (Let's Encrypt recommended)

2. **Force HTTPS Redirect**
   - Edit `.htaccess` file
   - Uncomment the HTTPS redirect lines:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Update Environment Configuration**
   - Edit `.env` file
   - Update `APP_URL` to use `https://`

### Step 7: Email Configuration (Optional)

#### Setting Up Email Notifications

1. **Create Email Account**
   - In cPanel, go to "Email" section
   - Click "Email Accounts"
   - Create account: `noreply@yourdomain.com`

2. **Configure SMTP Settings**
   - Edit `.env` file
   - Add email configuration:
   ```env
   MAIL_HOST=mail.yourdomain.com
   MAIL_PORT=587
   MAIL_USERNAME=noreply@yourdomain.com
   MAIL_PASSWORD=your_email_password
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="Thai Link BD Inventory"
   ```

### Step 8: Backup Configuration

#### Setting Up Automated Backups

1. **Create Backup Directory**
   - In File Manager, create `backups/` directory
   - Set permissions to 755

2. **Configure Cron Jobs (Optional)**
   - In cPanel, navigate to "Advanced" section
   - Click "Cron Jobs"
   - Add daily backup job:
   ```bash
   0 2 * * * /usr/bin/php /home/username/public_html/scripts/backup.php
   ```

## Post-Deployment Configuration

### Initial System Setup

#### Company Information Configuration

1. **Login to System**
   - Navigate to your domain
   - Login with admin credentials created during installation

2. **Update Company Settings**
   - Go to Settings → Company Information
   - Update all company details
   - Upload company logo
   - Configure tax settings

#### User Management Setup

1. **Create Additional Users**
   - Navigate to Settings → User Management
   - Add staff members with appropriate roles:
     - **Manager**: Full access except user management
     - **Staff**: POS and basic inventory access
     - **Viewer**: Read-only access to reports

2. **Configure User Permissions**
   - Review and adjust role permissions
   - Set up user-specific restrictions if needed

#### Product Catalog Setup

1. **Create Categories**
   - Go to Products → Categories
   - Add main product categories (e.g., Skincare, Makeup, Haircare)
   - Create subcategories as needed

2. **Add Brands**
   - Navigate to Products → Brands
   - Add cosmetic brands you carry
   - Include brand logos and descriptions

3. **Setup Suppliers**
   - Go to Inventory → Suppliers
   - Add supplier information
   - Include contact details and terms

#### Customer Management Setup

1. **Import Existing Customers**
   - Use the bulk import feature if available
   - Or manually add key wholesale customers

2. **Configure Customer Types**
   - Set up retail vs wholesale pricing
   - Configure customer-specific discounts

### Performance Optimization

#### Database Optimization

1. **Index Optimization**
   - The system includes optimized indexes
   - Monitor query performance through cPanel MySQL tools

2. **Regular Maintenance**
   - Set up weekly database optimization
   - Monitor database size and growth

#### Caching Configuration

1. **Browser Caching**
   - The `.htaccess` file includes cache headers
   - Verify caching is working properly

2. **PHP Optimization**
   - In cPanel, check PHP version and settings
   - Ensure OPcache is enabled if available

### Security Hardening

#### File Security

1. **Verify File Permissions**
   - Ensure sensitive files are not publicly accessible
   - Check that `.env` file returns 403 error when accessed directly

2. **Regular Updates**
   - Monitor for system updates
   - Keep PHP and MySQL versions current

#### Access Security

1. **Strong Password Policy**
   - Enforce strong passwords for all users
   - Regular password updates

2. **Login Monitoring**
   - Monitor failed login attempts
   - Set up IP blocking for suspicious activity

### Monitoring and Maintenance

#### System Monitoring

1. **Error Log Monitoring**
   - Check cPanel error logs regularly
   - Monitor for PHP errors or warnings

2. **Performance Monitoring**
   - Monitor page load times
   - Check database query performance

#### Regular Maintenance Tasks

1. **Weekly Tasks**
   - Database backup verification
   - Error log review
   - Performance check

2. **Monthly Tasks**
   - Security audit
   - User access review
   - System update check

## Troubleshooting Common Issues

### Database Connection Issues

**Problem**: "Database connection failed" error
**Solutions**:
1. Verify database credentials in `.env` file
2. Check database user permissions
3. Ensure database server is accessible
4. Contact hosting provider if issues persist

### File Permission Issues

**Problem**: "Permission denied" errors
**Solutions**:
1. Check directory permissions (755 for directories)
2. Check file permissions (644 for files)
3. Verify ownership settings
4. Use cPanel File Manager to reset permissions

### SSL Certificate Issues

**Problem**: Mixed content warnings or SSL errors
**Solutions**:
1. Ensure all resources load via HTTPS
2. Update `.env` file with HTTPS URL
3. Clear browser cache
4. Verify SSL certificate installation

### Performance Issues

**Problem**: Slow page loading
**Solutions**:
1. Enable PHP OPcache if available
2. Optimize database queries
3. Check hosting resource usage
4. Consider upgrading hosting plan

### Email Notification Issues

**Problem**: System emails not sending
**Solutions**:
1. Verify SMTP settings in `.env`
2. Check email account credentials
3. Test email functionality in cPanel
4. Review hosting provider email policies

## Support and Resources

### Documentation Resources

- **User Manual**: Complete system usage guide
- **API Documentation**: Developer reference
- **Video Tutorials**: Step-by-step visual guides

### Technical Support

- **Email Support**: support@thailinkbd.com
- **Phone Support**: +880-2-123456789
- **Business Hours**: 9 AM - 6 PM (GMT+6)

### Emergency Contacts

- **Critical Issues**: emergency@thailinkbd.com
- **After Hours**: +880-1XXXXXXXXX
- **Hosting Provider**: Your hosting support contact

## Conclusion

Following this deployment guide ensures a secure, optimized installation of the Thai Link BD Inventory Management System on cPanel hosting. Regular maintenance and monitoring will keep your system running smoothly and securely.

Remember to:
- Keep regular backups
- Monitor system performance
- Update passwords regularly
- Stay informed about security updates
- Document any customizations made

For additional assistance or custom modifications, contact the development team at the provided support channels.

---

*This deployment guide is part of the Thai Link BD Inventory Management System documentation package.*

