# Quick Start Guide

Get up and running with the Online Student Registration System in minutes!

## Prerequisites Check

- ✅ PHP 8.0+ installed
- ✅ MySQL/MariaDB running
- ✅ Web server (Apache/Nginx) running
- ✅ phpMyAdmin or MySQL command line access

## 5-Minute Setup

### Step 1: Database Setup (2 minutes)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `std_register`
3. Import `database/schema.sql`

### Step 2: Configure Environment (1 minute)

Edit `.env` file:
```env
DB_HOST=localhost
DB_NAME=std_register
DB_USER=root
DB_PASS=your_password
```

### Step 3: Access Application (1 minute)

Open browser:
- Home: `http://localhost/online_student_registeration/`
- Login: `http://localhost/online_student_registeration/auth/login.php`

### Step 4: Test Login (1 minute)

**Admin Login:**
- Username: `admin`
- Password: `Admin@123`

**Student Registration:**
- Go to registration page
- Fill in details and create account

## Default Credentials

⚠️ **Change these immediately after first login!**

- **Admin Username**: `admin`
- **Admin Email**: `admin@studentregistration.edu`
- **Admin Password**: `Admin@123`

## Common Issues & Quick Fixes

### Issue: CSS/JS not loading
**Fix**: Ensure paths are correct. If project is in subdirectory, update BASE_URL in `config/constants.php`

### Issue: Database connection error
**Fix**: Check `.env` file credentials match your MySQL setup

### Issue: Permission denied
**Fix**: Ensure `public/uploads/profile_pictures` directory exists and is writable

### Issue: Pages showing blank
**Fix**: Enable error display by setting `APP_DEBUG=true` in `.env`, check PHP error logs

## Next Steps

1. ✅ Change admin password
2. ✅ Add sample courses via admin panel
3. ✅ Test student registration
4. ✅ Test enrollment process
5. ✅ Review security settings

## Getting Help

- Check `README.md` for detailed documentation
- See `INSTALLATION.md` for comprehensive installation guide
- Review error logs for troubleshooting

---

**Ready to go!** Start by logging in as admin and exploring the system.

