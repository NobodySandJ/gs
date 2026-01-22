<?php
/**
 * System Constants
 * Smart Garage System
 */

// Application info
define('APP_NAME', 'Garasi Smart');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// URLs (adjust based on your setup)
define('BASE_URL', 'http://localhost/gs');
define('ASSETS_URL', BASE_URL . '/assets');

// Session configuration
define('SESSION_NAME', 'garasi_smart_session');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Gate timing (from database settings, but fallback here)
define('GATE_ALERT_TIMEOUT', 180); // 3 minutes in seconds
define('REPORT_INTERVAL', 3600); // 1 hour in seconds

// Polling intervals (JavaScript)
define('STATUS_POLL_INTERVAL', 5000); // 5 seconds
define('ACTIVITY_POLL_INTERVAL', 10000); // 10 seconds

// Email configuration
define('EMAIL_ENABLED', true);
define('EMAIL_DEBUG', false);

// Security
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// IoT Configuration
define('IOT_API_TIMEOUT', 30); // seconds
define('IOT_COMMAND_EXPIRY', 300); // 5 minutes

// Date/Time formats
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Response codes
define('RESPONSE_SUCCESS', 'success');
define('RESPONSE_ERROR', 'error');
define('RESPONSE_UNAUTHORIZED', 'unauthorized');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
?>
