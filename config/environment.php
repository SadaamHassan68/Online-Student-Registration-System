<?php
/**
 * Environment Configuration
 * Loads environment variables from .env file
 */

function loadEnvironment($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }

    if (!file_exists($path)) {
        // Create default .env file if it doesn't exist
        createDefaultEnv($path);
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function createDefaultEnv($path) {
    $defaultEnv = <<<'ENV'
# Database Configuration
DB_HOST=localhost
DB_NAME=std_register
DB_USER=root
DB_PASS=

# Application
APP_ENV=development
APP_DEBUG=true

# Email Configuration (for production)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
ENV;

    file_put_contents($path, $defaultEnv);
}

// Load environment variables
loadEnvironment();
?>

