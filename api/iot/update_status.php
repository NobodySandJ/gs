<?php
/**
 * IoT Status Update Endpoint
 * Receives status updates from ESP32/Arduino
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/EmailService.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$api_key = $input['api_key'] ?? '';
$gate_status = $input['status'] ?? '';
$timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

// Validate API key
if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'API key required']);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT device_id, device_name FROM iot_devices WHERE api_key = ? AND is_active = 1");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
    exit;
}

// Validate status
if (!in_array($gate_status, ['open', 'closed'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "open" or "closed"']);
    exit;
}

try {
    // Update gate status
    $status_value = $gate_status;
    $stmt = $db->prepare("UPDATE gate_status SET current_status = ?, last_updated = NOW() WHERE status_id = 1");
    $stmt->bind_param("s", $status_value);
    $stmt->execute();
    
    // Log activity
    $action = $gate_status == 'open' ? 'open' : 'close';
    $stmt = $db->prepare("INSERT INTO gate_logs (action, action_by, action_source) VALUES (?, NULL, 'iot')");
    $stmt->bind_param("s", $action);
    $stmt->execute();
    
    // Update device last seen
    $stmt = $db->prepare("UPDATE iot_devices SET last_seen = NOW() WHERE device_id = ?");
    $stmt->bind_param("i", $device['device_id']);
    $stmt->execute();
    
    // Check if we need to send alert (gate open > 3 minutes)
    if ($gate_status == 'open') {
        $current_status_row = $db->query("SELECT alert_sent FROM gate_status WHERE status_id = 1")->fetch_assoc();
        
        // Check time gate has been open
        $last_open = $db->query("SELECT timestamp FROM gate_logs WHERE action = 'open' ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();
        
        if ($last_open) {
            $time_open = time() - strtotime($last_open['timestamp']);
            
            // Send alert if gate open > 3 minutes and alert not yet sent
            if ($time_open > GATE_ALERT_TIMEOUT && !$current_status_row['alert_sent']) {
                $emailService = new EmailService();
                $emailService->sendGateOpenAlert($last_open['timestamp']);
                
                // Mark alert as sent
                $db->query("UPDATE gate_status SET alert_sent = 1, alert_sent_at = NOW() WHERE status_id = 1");
            }
        }
    } else {
        // If closing, reset alert
        $db->query("UPDATE gate_status SET alert_sent = 0, alert_sent_at = NULL WHERE status_id = 1");
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Status updated successfully',
        'gate_status' => $gate_status,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
