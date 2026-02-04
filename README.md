# Online Student Registration System

A comprehensive, secure, and user-friendly online student registration system built with PHP and MySQL. This system handles student registrations, course enrollment, and profile management with proper validation and data integrity.

## Features

### Student Portal
- **Registration System**: Multi-step registration with email verification
- **Student Dashboard**: Overview of enrollments and quick actions
- **Profile Management**: Edit profile information and change password
- **Course Catalog**: Browse, search, and filter available courses
- **Enrollment Management**: Enroll in courses and manage enrollments
- **Secure Authentication**: Login/logout with session management

### Administrative Portal
- **Admin Dashboard**: System statistics and overview
- **Student Management**: View, edit, and manage student accounts
- **Course Management**: Create, edit, and manage course offerings
- **Enrollment Management**: Approve, reject, and manage enrollments
- **Reports & Analytics**: Enrollment statistics and demographic reports
- **Grade Management**: Update student grades

### Security Features
- SQL Injection Prevention (PDO Prepared Statements)
- XSS Protection (HTML Entity Encoding)
- CSRF Protection (Token-based Validation)
- Password Security (bcrypt Hashing)
- Session Security (Regenerate ID on Login)
- File Upload Security (Type and Size Validation)
- Input Validation (Server-side)

## Requirements

- PHP 8.0 or higher
- MySQL 8.0+ or MariaDB
- Apache/Nginx web server with mod_rewrite enabled
- Composer (optional, for dependencies)

## Installation

### 1. Clone or Download the Project

```bash
git clone <repository-url>
cd online_student_registeration
```

### 2. Configure Environment

Edit the `.env` file in the root directory:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=std_register
DB_USER=root
DB_PASS=your_password

# Application
APP_ENV=development
APP_DEBUG=true

# Email Configuration (for production)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
```

### 3. Create Database

1. Create a MySQL database:
```sql
CREATE DATABASE std_register CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p std_register < database/schema.sql
```

Or import it via phpMyAdmin:
- Open phpMyAdmin
- Select the `std_register` database
- Go to the Import tab
- Choose the `database/schema.sql` file
- Click Go

### 4. Set File Permissions

Ensure the uploads directory is writable:

```bash
mkdir -p public/uploads/profile_pictures
chmod 755 public/uploads/profile_pictures
```

On Windows, ensure the directories exist with appropriate permissions.

### 5. Configure Web Server

#### Apache Configuration

Ensure `.htaccess` files are enabled in your Apache configuration:

```apache
<Directory "/path/to/online_student_registeration">
    AllowOverride All
    Require all granted
</Directory>
```

#### Nginx Configuration

Add the following to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 6. Access the Application

- **Home Page**: `http://localhost/online_student_registeration/`
- **Student Registration**: `http://localhost/online_student_registeration/auth/register.php`
- **Login**: `http://localhost/online_student_registeration/auth/login.php`

### 7. Default Admin Credentials

After importing the schema, you can login with:

- **Username**: `admin`
- **Email**: `admin@studentregistration.edu`
- **Password**: `Admin@123`
Username: admin12
Email: admin12@example.com
Password: admin123

**⚠️ IMPORTANT**: Change the default admin password immediately after first login!

## Project Structure

```
online_student_registeration/
├── admin/                 # Admin portal pages
│   ├── dashboard.php
│   ├── students.php
│   ├── courses.php
│   ├── enrollments.php
│   └── reports.php
├── auth/                  # Authentication pages
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/                # Configuration files
│   ├── database.php
│   ├── constants.php
│   └── environment.php
├── database/              # Database files
│   └── schema.sql
├── includes/              # Shared includes
│   ├── header.php
│   ├── footer.php
│   ├── auth_functions.php
│   └── validation_functions.php
├── public/                # Public assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── uploads/           # User uploads (outside web root ideally)
├── student/               # Student portal pages
│   ├── dashboard.php
│   ├── profile.php
│   ├── courses.php
│   ├── enroll.php
│   └── enrollments.php
├── .env                   # Environment configuration
├── .htaccess              # Apache configuration
├── index.php              # Home page
└── README.md              # This file
```

## Database Schema

### Tables

1. **students**: Student information and accounts
2. **courses**: Course offerings and details
3. **enrollments**: Student course enrollments
4. **users**: Admin and registrar accounts
5. **audit_logs**: System audit trail (optional)

## Usage

### For Students

1. **Register**: Visit the registration page and fill in your details
2. **Login**: Use your email and password to login
3. **Browse Courses**: Explore available courses in the catalog
4. **Enroll**: Select courses and submit enrollment requests
5. **Manage Profile**: Update your profile information and password

### For Administrators

1. **Login**: Use admin credentials to access the admin portal
2. **Manage Students**: View, activate/deactivate student accounts
3. **Manage Courses**: Add, edit, or deactivate courses
4. **Approve Enrollments**: Review and approve/reject enrollment requests
5. **View Reports**: Access enrollment statistics and analytics

## Security Best Practices

1. **Change Default Passwords**: Always change default admin passwords
2. **Use HTTPS**: Enable SSL/TLS in production
3. **Regular Updates**: Keep PHP and MySQL updated
4. **Backup Database**: Regular database backups are essential
5. **File Permissions**: Restrict file upload permissions
6. **Environment Variables**: Never commit `.env` file with real credentials
7. **Error Reporting**: Disable error display in production (set `APP_DEBUG=false`)

## Customization

### Changing Application Name

Edit `config/constants.php`:
```php
define('APP_NAME', 'Your Institution Name');
```

### Styling

Modify `public/css/style.css` to match your institution's branding.

### Email Configuration

Update email settings in `.env` and implement email sending in the registration process.

## Troubleshooting

### Database Connection Error

- Check `.env` file database credentials
- Ensure MySQL service is running
- Verify database name exists

### Permission Denied Errors

- Check file/directory permissions
- Ensure `public/uploads` directory is writable

### Session Issues

- Check PHP session configuration
- Ensure session directory is writable
- Clear browser cookies if needed

## Future Enhancements

Potential features for future versions:

- Email verification system
- Password reset functionality
- Waitlist system for full courses
- Prerequisite checking
- Payment integration
- REST API for mobile access
- Real-time notifications
- Multi-language support
- Advanced reporting features

## License

This project is open source and available for educational purposes.

## Support

For issues, questions, or contributions, please contact the development team.

## Version

Current Version: 1.0.0

---

**Note**: This is a basic implementation. For production use, consider implementing additional security measures, error handling, and testing.

