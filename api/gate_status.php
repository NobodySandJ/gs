<?php
/**
 * Gate Status API
 * Returns current gate status
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

session_name(SESSION_NAME);
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();

// Get current status
$result = $db->query("SELECT * FROM gate_status WHERE status_id = 1");
$status = $result->fetch_assoc();

// Get last activity
$last_activity = $db->query(
    "SELECT gl.*, u.full_name 
    FROM gate_logs gl 
    LEFT JOIN users u ON gl.action_by = u.user_id 
    ORDER BY gl.timestamp DESC 
    LIMIT 1"
)->fetch_assoc();

// Check if alert should be shown (gate open > 3 minutes)
$show_alert = false;
$time_open_seconds = 0;

if ($status['current_status'] === 'open') {
    $last_open = $db->query(
        "SELECT timestamp FROM gate_logs WHERE action = 'open' ORDER BY timestamp DESC LIMIT 1"
    )->fetch_assoc();
    
    if ($last_open) {
        $time_open_seconds = time() - strtotime($last_open['timestamp']);
        $show_alert = ($time_open_seconds > GATE_ALERT_TIMEOUT);
    }
}

echo json_encode([
    'status' => 'success',
    'gate_status' => $status['current_status'],
    'is_open' => ($status['current_status'] === 'open'),
    'last_updated' => $status['last_updated'],
    'show_alert' => $show_alert,
    'time_open_seconds' => $time_open_seconds,
    'last_activity' => $last_activity ? [
        'action' => $last_activity['action'],
        'timestamp' => $last_activity['timestamp'],
        'by' => $last_activity['full_name']
    ] : null
]);
?>
