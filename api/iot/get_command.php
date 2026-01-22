<?php
/**
 * IoT Get Command Endpoint
 * Returns pending commands for IoT device
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$api_key = $_GET['api_key'] ?? '';

// Validate API key
if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'API key required']);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT device_id FROM iot_devices WHERE api_key = ? AND is_active = 1");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
    exit;
}

// Update device last seen
$stmt = $db->prepare("UPDATE iot_devices SET last_seen = NOW() WHERE device_id = ?");
$stmt->bind_param("i", $device['device_id']);
$stmt->execute();

// Get pending command
$result = $db->query(
    "SELECT * FROM iot_commands WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1"
);

if ($command = $result->fetch_assoc()) {
    // Mark as executed
    $stmt = $db->prepare("UPDATE iot_commands SET status = 'executed', executed_at = NOW() WHERE command_id = ?");
    $stmt->bind_param("i", $command['command_id']);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'command' => $command['command'],
        'command_id' => $command['command_id'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // No pending commands
    echo json_encode([
        'status' => 'success',
        'command' => 'none',
        'message' => 'No pending commands'
    ]);
}
?>
