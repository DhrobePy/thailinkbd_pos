# Thai Link BD Inventory Management System
## Installation Guide

### 🚀 Quick Installation

1. **Upload Files**
   - Extract the ZIP file to your cPanel public_html directory
   - Ensure all files are uploaded correctly

2. **Database Setup**
   - Create a MySQL database in cPanel
   - Import `database/schema.sql` first
   - Then import `database/seed.sql` for sample data

3. **Configuration**
   - Copy `.env.example` to `.env`
   - Update database credentials in `.env`:
     ```
     DB_HOST=localhost
     DB_NAME=your_database_name
     DB_USER=your_db_username
     DB_PASS=your_db_password
     ```

4. **Permissions**
   - Set `uploads/` directory to 755 permissions
   - Ensure web server can write to uploads folder

5. **Access System**
   - Visit your domain
   - Login with: admin / password
   - Change default password immediately

### 📋 Default Login
- **Username:** admin
- **Password:** password

### 🔧 System Requirements
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)
- 1GB RAM minimum
- 10GB storage space

### 📞 Support
For issues, check the README.md file or contact support.

