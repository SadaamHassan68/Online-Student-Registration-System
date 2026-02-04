<?php
/**
 * Validation Functions
 * Server-side validation for forms and inputs
 */

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic international format)
 */
function validatePhone($phone) {
    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    // Check if it's between 10 and 15 digits (international format)
    return preg_match('/^\+?[1-9]\d{1,14}$/', $cleaned);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Validate date of birth (minimum age requirement)
 */
function validateDateOfBirth($dob, $minAge = 16) {
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < $minAge) {
        return "You must be at least $minAge years old to register";
    }
    
    if ($age > 100) {
        return "Invalid date of birth";
    }
    
    return true;
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes, $maxSize = MAX_FILE_SIZE) {
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = "No file uploaded";
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size of " . ($maxSize / 1024 / 1024) . "MB";
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedTypes);
    }
    
    // Additional check using extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = "File extension not allowed";
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Secure file upload
 */
function secureFileUpload($file, $uploadPath, $prefix = '') {
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadPath . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return false;
}

/**
 * Validate course code format
 */
function validateCourseCode($code) {
    return preg_match('/^[A-Z]{2,4}[0-9]{3,4}$/', $code);
}

/**
 * Check if email domain is valid
 */
function validateEmailDomain($email) {
    $domain = substr(strrchr($email, "@"), 1);
    return checkdnsrr($domain, 'MX');
}
?>

