<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_functions.php';

logoutUser();

header('Location: ' . BASE_URL . '/auth/login.php?logged_out=1');
exit;
?>

