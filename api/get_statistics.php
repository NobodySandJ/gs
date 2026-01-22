<?php
/**
 * Get Statistics API
 * Returns usage statistics for charts
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
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$days = min($days, 30); // Max 30 days

// Get daily statistics
$result = $db->query(
    "SELECT 
        DATE_FORMAT(timestamp, '%a') as day,
        DATE(timestamp) as date,
        SUM(CASE WHEN action = 'open' THEN 1 ELSE 0 END) as opens,
        SUM(CASE WHEN action = 'close' THEN 1 ELSE 0 END) as closes
    FROM gate_logs 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL $days DAY)
    GROUP BY DATE(timestamp)
    ORDER BY DATE(timestamp) ASC"
);

$stats = [
    'labels' => [],
    'opens' => [],
    'closes' => []
];

while ($row = $result->fetch_assoc()) {
    $stats['labels'][] = $row['day'];
    $stats['opens'][] = (int)$row['opens'];
    $stats['closes'][] = (int)$row['closes'];
}

echo json_encode([
    'status' => 'success',
    'stats' => $stats
]);
?>
