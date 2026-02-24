# Thai Link BD Inventory Management System

A comprehensive web-based inventory management system designed specifically for Thai Link BD, a wholesale and retail cosmetics business. This system provides complete inventory tracking, point-of-sale functionality, invoicing, reporting, and analytics capabilities.

## 🚀 Features

### Core Functionality
- **Product Management**: Complete product catalog with variants, categories, brands, and suppliers
- **Inventory Tracking**: Real-time stock levels, low stock alerts, and inventory transactions
- **Barcode System**: Barcode generation and scanning for efficient product identification
- **Point of Sale (POS)**: Full-featured POS system with customer management and payment processing
- **Invoicing**: Professional invoice generation with PDF export capabilities
- **Reporting & Analytics**: Comprehensive sales reports, inventory analysis, and business insights

### Advanced Features
- **Multi-variant Products**: Support for product variations (size, color, type, etc.)
- **Customer Management**: Retail and wholesale customer tracking with purchase history
- **User Management**: Role-based access control (Admin, Manager, Staff)
- **Payment Processing**: Multiple payment methods (Cash, Card, Mobile, Credit)
- **Tax Management**: Configurable tax rates and calculations
- **Audit Trail**: Complete activity logging for all system operations

### Technical Features
- **Responsive Design**: Mobile-friendly interface using Tailwind CSS
- **RESTful API**: Clean API architecture for all operations
- **Security**: Input validation, SQL injection prevention, and secure authentication
- **cPanel Compatible**: Designed for easy deployment on shared hosting
- **Database Optimization**: Efficient MySQL schema with proper indexing

## 📋 System Requirements

### Server Requirements
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Storage**: Minimum 100MB free space
- **Memory**: 256MB RAM minimum (512MB recommended)

### PHP Extensions
- PDO MySQL
- JSON
- Session
- GD (for barcode generation)
- cURL (for external integrations)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🛠️ Installation Guide

### Method 1: Automatic Installation (Recommended)

1. **Upload Files**
   ```bash
   # Extract the application files to your web directory
   unzip thai-link-inventory.zip
   cd thai-link-inventory
   ```

2. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 backups/
   chmod 644 .htaccess
   ```

3. **Run Installation Wizard**
   - Navigate to `http://yourdomain.com/install.php`
   - Follow the step-by-step installation wizard
   - Configure database connection
   - Create admin user account
   - Complete installation

4. **Security Setup**
   ```bash
   # Delete installation file after completion
   rm install.php
   ```

### Method 2: Manual Installation

1. **Database Setup**
   ```sql
   CREATE DATABASE thai_link_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Database Schema**
   ```bash
   mysql -u username -p thai_link_inventory < database/schema.sql
   mysql -u username -p thai_link_inventory < database/seed.sql
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Set File Permissions**
   ```bash
   chmod 755 uploads/ backups/
   chmod 644 .env .htaccess
   ```

## ⚙️ Configuration

### Environment Variables

Edit the `.env` file to configure your system:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=thai_link_inventory
DB_USER=your_username
DB_PASS=your_password

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Company Information
COMPANY_NAME="Thai Link BD"
COMPANY_ADDRESS="Your Business Address"
COMPANY_PHONE="+880-2-123456789"
COMPANY_EMAIL="info@thailinkbd.com"

# Tax Settings
DEFAULT_TAX_RATE=15.0
CURRENCY_SYMBOL="৳"
```

### cPanel Deployment

1. **File Manager Upload**
   - Login to cPanel
   - Open File Manager
   - Navigate to public_html
   - Upload and extract the application files

2. **Database Creation**
   - Go to MySQL Databases
   - Create new database: `thai_link_inventory`
   - Create database user and assign privileges

3. **Domain Configuration**
   - Ensure your domain points to the application directory
   - Configure SSL certificate (recommended)

## 👥 User Guide

### Getting Started

1. **Login**
   - Access the system at your domain URL
   - Use the admin credentials created during installation
   - Default admin username: `admin`

2. **Initial Setup**
   - Configure company information in Settings
   - Set up product categories and brands
   - Add suppliers and customers
   - Configure tax rates and payment methods

### Product Management

#### Adding Products
1. Navigate to Products → Add New Product
2. Fill in product details:
   - Name, SKU, and barcode
   - Category and brand
   - Cost and selling prices
   - Stock levels and reorder points
3. Add product variants if applicable
4. Upload product images
5. Save the product

#### Managing Inventory
1. Go to Inventory → Stock Management
2. View current stock levels
3. Add stock adjustments
4. Monitor low stock alerts
5. Generate inventory reports

### Point of Sale (POS)

#### Processing Sales
1. Access POS → New Sale
2. Search and add products to cart
3. Select customer (optional)
4. Apply discounts if needed
5. Choose payment method
6. Process the sale
7. Print receipt

#### Barcode Scanning
- Use the barcode input field in POS
- Scan products using camera (if supported)
- Products are automatically added to cart

### Invoicing

#### Creating Invoices
1. Navigate to Invoices → Create New
2. Select customer
3. Add products and quantities
4. Set payment terms
5. Generate and send invoice
6. Track payment status

### Reporting

#### Available Reports
- **Sales Summary**: Daily, weekly, monthly sales analysis
- **Top Products**: Best-selling products by quantity and revenue
- **Inventory Report**: Current stock levels and valuations
- **Customer Analysis**: Customer purchase patterns and loyalty
- **Profit & Loss**: Financial performance analysis

## 🔧 API Documentation

### Authentication

All API endpoints require authentication via session cookies.

```javascript
// Login
POST /api/auth.php
{
  "action": "login",
  "username": "admin",
  "password": "password"
}
```

### Products API

```javascript
// Get products
GET /api/products.php?page=1&search=cosmetics

// Create product
POST /api/products.php
{
  "action": "create",
  "name": "Lipstick Red",
  "sku": "LIP001",
  "category_id": 1,
  "cost_price": 50.00,
  "selling_price": 75.00
}
```

### POS API

```javascript
// Search products for POS
GET /api/pos.php?action=search_products&term=lipstick

// Process sale
POST /api/pos.php
{
  "action": "process_sale",
  "customer_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "unit_price": 75.00,
      "total_price": 150.00
    }
  ],
  "total_amount": 150.00,
  "payment_method": "cash"
}
```

## 🔒 Security Features

### Data Protection
- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- CSRF protection for form submissions
- Secure password hashing using PHP's password_hash()

### Access Control
- Role-based permissions (Admin, Manager, Staff)
- Session management with timeout
- Activity logging for audit trails
- File upload restrictions and validation

### Server Security
- .htaccess configuration for security headers
- Hidden sensitive files and directories
- HTTPS enforcement (configurable)
- Input validation and sanitization

## 📊 Database Schema

### Core Tables

#### Products
- `id`: Primary key
- `name`: Product name
- `sku`: Stock keeping unit
- `barcode`: Product barcode
- `category_id`: Foreign key to categories
- `brand_id`: Foreign key to brands
- `cost_price`: Purchase cost
- `selling_price`: Retail price
- `wholesale_price`: Wholesale price
- `track_inventory`: Boolean for inventory tracking
- `has_variants`: Boolean for product variants

#### Inventory
- `id`: Primary key
- `product_id`: Foreign key to products
- `variant_id`: Foreign key to product variants (nullable)
- `quantity`: Current stock quantity
- `reserved_quantity`: Reserved stock
- `location`: Storage location
- `last_updated`: Timestamp

#### Sales
- `id`: Primary key
- `sale_number`: Unique sale identifier
- `customer_id`: Foreign key to customers
- `user_id`: Foreign key to users (cashier)
- `subtotal`: Sale subtotal
- `tax_amount`: Tax amount
- `discount_amount`: Discount applied
- `total_amount`: Final total
- `payment_method`: Payment method used
- `payment_status`: Payment status

## 🚀 Performance Optimization

### Database Optimization
- Proper indexing on frequently queried columns
- Optimized queries with appropriate JOINs
- Database connection pooling
- Regular maintenance and cleanup

### Frontend Optimization
- CDN usage for CSS and JavaScript libraries
- Image optimization and lazy loading
- Minified CSS and JavaScript
- Browser caching configuration

### Server Optimization
- GZIP compression enabled
- Proper cache headers
- Optimized PHP configuration
- Regular log rotation

## 🔄 Backup and Maintenance

### Automated Backups
- Daily database backups
- File system backups
- Configurable retention periods
- Email notifications for backup status

### Maintenance Tasks
- Regular database optimization
- Log file cleanup
- Security updates
- Performance monitoring

## 🆘 Troubleshooting

### Common Issues

#### Database Connection Errors
```
Error: SQLSTATE[HY000] [2002] Connection refused
```
**Solution**: Check database credentials in .env file and ensure MySQL service is running.

#### Permission Errors
```
Error: Permission denied for uploads directory
```
**Solution**: Set proper file permissions:
```bash
chmod 755 uploads/
chmod 755 backups/
```

#### Session Issues
```
Error: Session expired or invalid
```
**Solution**: Clear browser cookies and cache, then login again.

### Debug Mode
Enable debug mode in .env for detailed error messages:
```env
APP_DEBUG=true
```

## 📞 Support

### Documentation
- User Manual: `/docs/user-manual.pdf`
- API Documentation: `/docs/api-reference.html`
- Video Tutorials: Available on request

### Technical Support
- Email: support@thailinkbd.com
- Phone: +880-2-123456789
- Business Hours: 9 AM - 6 PM (GMT+6)

### Community
- GitHub Issues: Report bugs and feature requests
- User Forum: Community discussions and tips
- Knowledge Base: Frequently asked questions

## 📄 License

This software is proprietary and licensed for use by Thai Link BD. Unauthorized distribution or modification is prohibited.

## 🏆 Credits

**Developed by**: Manus AI  
**Version**: 1.0.0  
**Release Date**: 2024  
**Framework**: Pure PHP with MySQL  
**Frontend**: HTML5, Tailwind CSS, JavaScript  

---

*Thai Link BD Inventory Management System - Empowering your cosmetics business with intelligent inventory solutions.*

