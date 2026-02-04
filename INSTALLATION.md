# Installation Guide

Complete step-by-step installation guide for the Online Student Registration System.

## Prerequisites

Before installing, ensure you have:

- PHP 8.0 or higher
- MySQL 8.0+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- PHP extensions: PDO, PDO_MySQL, mbstring, fileinfo

### Check PHP Version

```bash
php -v
```

### Check Required Extensions

```bash
php -m | grep -i pdo
php -m | grep -i mbstring
php -m | grep -i fileinfo
```

## Step-by-Step Installation

### Step 1: Download/Clone the Project

Download the project files or clone the repository to your web server directory.

**For XAMPP (Windows)**:
```
C:\xampp\htdocs\online_student_registeration
```

**For Apache (Linux/Mac)**:
```
/var/www/html/online_student_registeration
```

**For Laragon (Windows)**:
```
C:\laragon\www\online_student_registeration
```

### Step 2: Set Up the Database

#### Option A: Using phpMyAdmin

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click on "New" to create a new database
3. Enter database name: `std_register`
4. Select collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Select the database
7. Go to "Import" tab
8. Choose the file: `database/schema.sql`
9. Click "Go"

#### Option B: Using MySQL Command Line

```bash
mysql -u root -p
```

```sql
CREATE DATABASE std_register CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE std_register;
SOURCE /path/to/database/schema.sql;
EXIT;
```

### Step 3: Configure Environment Variables

1. Locate the `.env` file in the root directory
2. Open it with a text editor
3. Update database credentials:

```env
DB_HOST=localhost
DB_NAME=std_register
DB_USER=root
DB_PASS=your_mysql_password
```

4. Save the file

**Important**: Never commit the `.env` file with real credentials to version control!

### Step 4: Create Upload Directories

Create necessary directories for file uploads:

**Windows (Command Prompt)**:
```cmd
mkdir public\uploads\profile_pictures
```

**Linux/Mac (Terminal)**:
```bash
mkdir -p public/uploads/profile_pictures
chmod 755 public/uploads/profile_pictures
```

### Step 5: Set File Permissions (Linux/Mac)

Set appropriate permissions:

```bash
# Make directories writable
chmod 755 public/uploads
chmod 755 public/uploads/profile_pictures

# Ensure .env is readable
chmod 644 .env
```

### Step 6: Configure Web Server

#### Apache Configuration

1. Ensure `mod_rewrite` is enabled
2. Edit Apache configuration or create `.htaccess` (already included)
3. Make sure `.htaccess` files are allowed:

In `httpd.conf` or virtual host:
```apache
<Directory "/path/to/online_student_registeration">
    AllowOverride All
    Require all granted
</Directory>
```

#### Virtual Host Setup (Optional)

Create a virtual host for easier access:

```apache
<VirtualHost *:80>
    ServerName student-reg.local
    DocumentRoot "C:/xampp/htdocs/online_student_registeration"
    
    <Directory "C:/xampp/htdocs/online_student_registeration">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `hosts` file (Windows: `C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1 student-reg.local
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name student-reg.local;
    root /var/www/html/online_student_registeration;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Step 7: Test the Installation

1. Start your web server and MySQL
2. Open your browser
3. Navigate to:
   - `http://localhost/online_student_registeration/`
   - Or your configured virtual host URL

4. You should see the home page

### Step 8: Verify Database Connection

1. Try to register a new student account
2. If successful, the database connection is working
3. Check the database for the new record

### Step 9: Login as Admin

Use the default admin credentials (change immediately after first login):

- **Username/Email**: `admin` or `admin@studentregistration.edu`
- **Password**: `Admin@123`

Navigate to: `http://localhost/online_student_registeration/auth/login.php`

## Post-Installation Steps

### 1. Change Admin Password

1. Login as admin
2. Go to admin dashboard
3. Change password (you may need to implement a password change feature for admin, or update directly in database)

To update in database:
```sql
UPDATE users 
SET password_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5VvXKdF3QzJz6' 
WHERE username = 'admin';
```

Generate new hash using PHP:
```php
<?php
echo password_hash('YourNewPassword', PASSWORD_BCRYPT);
?>
```

### 2. Configure Email Settings (Optional)

For email verification and notifications, update `.env`:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

Note: Email sending functionality needs to be implemented in the code.

### 3. Set Production Environment

When deploying to production:

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Enable HTTPS (update `.htaccess` or server config)
4. Use secure database credentials
5. Set proper file permissions
6. Enable error logging

## Troubleshooting

### Error: "Database connection failed"

**Solution**:
- Check `.env` file database credentials
- Ensure MySQL service is running
- Verify database exists
- Check user permissions

### Error: "Cannot write to uploads directory"

**Solution**:
- Check directory permissions
- Ensure directory exists
- On Windows, ensure directory is not read-only

### Error: "Class 'Database' not found"

**Solution**:
- Check that `config/environment.php` is being loaded
- Verify file paths are correct
- Check for PHP syntax errors

### Error: "Session start failed"

**Solution**:
- Check PHP session directory permissions
- Verify session.save_path in php.ini
- Clear old session files

### Pages showing blank/white screen

**Solution**:
- Enable error display: Set `APP_DEBUG=true` in `.env`
- Check PHP error logs
- Verify PHP version compatibility
- Check for syntax errors

### CSS/JavaScript not loading

**Solution**:
- Check file paths (ensure they start with `/public/...`)
- Verify `.htaccess` is working
- Check browser console for 404 errors
- Ensure files exist in `public/` directory

## Testing the Installation

1. **Test Student Registration**:
   - Go to registration page
   - Fill in all required fields
   - Submit the form
   - Check database for new student record

2. **Test Student Login**:
   - Use registered email and password
   - Should redirect to student dashboard

3. **Test Admin Login**:
   - Use default admin credentials
   - Should redirect to admin dashboard

4. **Test Course Management**:
   - Login as admin
   - Add a new course
   - Verify course appears in catalog

5. **Test Enrollment**:
   - Login as student
   - Browse courses
   - Enroll in a course
   - Check enrollment in admin panel

## Security Checklist

- [ ] Changed default admin password
- [ ] Set secure database password
- [ ] Updated `.env` with production credentials
- [ ] Enabled HTTPS (for production)
- [ ] Set proper file permissions
- [ ] Disabled error display in production
- [ ] Restricted database user permissions
- [ ] Set up regular backups
- [ ] Configured firewall rules
- [ ] Implemented email verification

## Support

If you encounter issues during installation:

1. Check error logs (PHP and MySQL)
2. Verify all requirements are met
3. Review configuration files
4. Test database connection separately
5. Check file permissions

For additional help, refer to the main README.md file or contact support.

---

**Last Updated**: 2024
**Version**: 1.0.0

