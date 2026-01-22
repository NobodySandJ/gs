<?php
/**
 * Logout Handler
 */

session_name('garasi_smart_session');
session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
require_once __DIR__ . '/../config/constants.php';
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
?>
