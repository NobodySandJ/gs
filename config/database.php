<?php
/**
 * Database Configuration
 * Smart Garage System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'garasi_smart');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }
        
        // Set charset
        $conn->set_charset(DB_CHARSET);
        
        // Set timezone
        $timezone = getSetting('timezone', 'Asia/Jakarta');
        date_default_timezone_set($timezone);
    }
    
    return $conn;
}

// Get setting value from database
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $settings = [];
        $conn = getDBConnection();
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Update setting value
function updateSetting($key, $value) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    return $stmt->execute();
}

// Test connection
$db = getDBConnection();
?>
