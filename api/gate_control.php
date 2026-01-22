<?php
/**
 * Gate Control API
 * Endpoint for opening/closing the gate
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if (!in_array($action, ['open', 'close'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Admin-only check
if ($_SESSION['role'] !== ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admin only.']);
    exit;
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

try {
    // Update gate status
    $stmt = $db->prepare("UPDATE gate_status SET current_status = ?, updated_by = ?, last_updated = NOW() WHERE status_id = 1");
    $stmt->bind_param("si", $action == 'open' ? 'open' : 'closed', $user_id);
    $stmt->execute();
    
    // Log the action
    $stmt = $db->prepare("INSERT INTO gate_logs (action, action_by, action_source) VALUES (?, ?, 'dashboard')");
    $stmt->bind_param("si", $action, $user_id);
    $stmt->execute();
    
    // If opening, reset alert status
    if ($action == 'open') {
        $db->query("UPDATE gate_status SET alert_sent = 0, alert_sent_at = NULL WHERE status_id = 1");
    }
    
    // Create IoT command
    $stmt = $db->prepare("INSERT INTO iot_commands (command, status) VALUES (?, 'pending')");
    $stmt->bind_param("s", $action);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Gate ' . ($action == 'open' ? 'dibuka' : 'ditutup') . ' berhasil',
        'new_status' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
