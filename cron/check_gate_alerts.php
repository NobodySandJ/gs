<?php
/**
 * Cron Job: Check Gate Alerts
 * Run every minute to check if gate has been open >3 minutes
 * 
 * Crontab entry:
 * * * * * * php /path/to/gs/cron/check_gate_alerts.php
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailService.php';

$db = getDBConnection();

// Get current gate status
$status = $db->query("SELECT * FROM gate_status WHERE status_id = 1")->fetch_assoc();

// Only check if gate is open and alert not yet sent
if ($status['current_status'] === 'open' && $status['alert_sent'] == 0) {
    // Get last open timestamp
    $last_open = $db->query(
        "SELECT timestamp FROM gate_logs WHERE action = 'open' ORDER BY timestamp DESC LIMIT 1"
    )->fetch_assoc();
    
    if ($last_open) {
        $time_open = time() - strtotime($last_open['timestamp']);
        
        // Send alert if > 3 minutes
        if ($time_open > GATE_ALERT_TIMEOUT) {
            $emailService = new EmailService();
            $result = $emailService->sendGateOpenAlert($last_open['timestamp']);
            
            if ($result > 0) {
                // Mark alert as sent
                $db->query("UPDATE gate_status SET alert_sent = 1, alert_sent_at = NOW() WHERE status_id = 1");
                error_log("Gate alert email sent successfully");
            } else {
                error_log("Failed to send gate alert email");
            }
        }
    }
}
?>
