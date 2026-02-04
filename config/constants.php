<?php
/**
 * Application Constants
 */

// Application settings
define('APP_NAME', 'Online Student Registration System');
define('APP_VERSION', '1.0.0');

// Base URL - automatically detects the base path
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get the script name (e.g., /online_student_registeration/auth/login.php or /online_student_registeration/index.php)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    
    // Normalize slashes
    $scriptName = str_replace('\\', '/', $scriptName);
    
    // Get directory of script
    $scriptDir = dirname($scriptName);
    
    // Known subdirectories that need to go up one level
    $subdirs = ['auth', 'admin', 'student', 'config', 'includes', 'public'];
    $lastDir = basename($scriptDir);
    
    // If we're in a known subdirectory, go up one level; otherwise use current directory
    if (in_array($lastDir, $subdirs)) {
        $basePath = dirname($scriptDir);
    } else {
        $basePath = $scriptDir;
    }
    
    // Normalize: if it's '/' or '\', it means we're at web root, so use empty string
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    
    // Remove trailing slash
    $basePath = rtrim($basePath, '/');
    
    define('BASE_URL', $protocol . "://" . $host . $basePath);
}

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'STUDENT_REG_SESSION');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']);

// Email settings
define('EMAIL_FROM', 'noreply@studentregistration.edu');
define('EMAIL_FROM_NAME', 'Student Registration System');

// Pagination
define('ITEMS_PER_PAGE', 20);

// User roles
define('ROLE_STUDENT', 'student');
define('ROLE_ADMIN', 'admin');
define('ROLE_REGISTRAR', 'registrar');

// Status constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('ENROLLMENT_ENROLLED', 'enrolled');
define('ENROLLMENT_COMPLETED', 'completed');
define('ENROLLMENT_DROPPED', 'dropped');

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
?>

