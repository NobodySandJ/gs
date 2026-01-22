<?php
/**
 * Get Activity Logs API
 * Returns recent gate activities
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
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = min($limit, 50); // Max 50

$result = $db->query(
    "SELECT gl.*, u.full_name 
    FROM gate_logs gl 
    LEFT JOIN users u ON gl.action_by = u.user_id 
    ORDER BY gl.timestamp DESC 
    LIMIT $limit"
);

$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = [
        'log_id' => $row['log_id'],
        'action' => $row['action'],
        'timestamp' => $row['timestamp'],
        'formatted_time' => date('H:i A', strtotime($row['timestamp'])),
        'formatted_date' => date('d/m/Y', strtotime($row['timestamp'])),
        'user' => $row['full_name'] ?: 'IoT Device',
        'source' => $row['action_source']
    ];
}

echo json_encode([
    'status' => 'success',
    'activities' => $activities,
    'count' => count($activities)
]);
?>
