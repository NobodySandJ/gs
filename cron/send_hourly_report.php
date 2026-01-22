<?php
/**
 * Cron Job: Send Hourly Report
 * Run every hour to send activity report to admins
 * 
 * Crontab entry:
 * 0 * * * * php /path/to/gs/cron/send_hourly_report.php
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailService.php';

$emailService = new EmailService();
$result = $emailService->sendHourlyReport();

if ($result > 0) {
    error_log("Hourly report sent to $result recipient(s)");
} else {
    error_log("No hourly reports sent (check email configuration)");
}
?>
