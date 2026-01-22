<?php
/**
 * Email Service Class
 * Handles all email sending operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/constants.php';

class EmailService {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = getEmailConfig();
        $this->db = getDBConnection();
    }
    
    /**
     * Send gate open alert email
     */
    public function sendGateOpenAlert($timestamp) {
        $template = getEmailTemplate('gate_alert', [
            'timestamp' => date(DATETIME_FORMAT, strtotime($timestamp))
        ]);
        
        // Get all admin emails
        $admins = $this->getAdminEmails();
        
        foreach ($admins as $admin) {
            $this->queueEmail(
                $admin['email'],
                $template['subject'],
                $template['body'],
                'alert'
            );
        }
        
        return $this->processQueue();
    }
    
    /**
     * Send hourly report email
     */
    public function sendHourlyReport() {
        // Get statistics for the last hour
        $stats = $this->getHourlyStats();
        
        // Build activity table HTML
        $activity_html = '';
        foreach ($stats['activities'] as $activity) {
            $activity_html .= '<tr>';
            $activity_html .= '<td>' . date('H:i', strtotime($activity['timestamp'])) . '</td>';
            $activity_html .= '<td>' . ucfirst($activity['action']) . '</td>';
            $activity_html .= '<td>' . htmlspecialchars($activity['user']) . '</td>';
            $activity_html .= '</tr>';
        }
        
        $template = getEmailTemplate('hourly_report', [
            'total_open' => $stats['total_open'],
            'total_close' => $stats['total_close'],
            'activities' => $activity_html ?: '<tr><td colspan="3">Tidak ada aktivitas</td></tr>'
        ]);
        
        // Get all admin emails
        $admins = $this->getAdminEmails();
        
        foreach ($admins as $admin) {
            $this->queueEmail(
                $admin['email'],
                $template['subject'],
                $template['body'],
                'report'
            );
        }
        
        return $this->processQueue();
    }
    
    /**
     * Queue an email for sending
     */
    private function queueEmail($to, $subject, $body, $type = 'other') {
        $stmt = $this->db->prepare(
            "INSERT INTO email_queue (recipient_email, subject, body, email_type) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $to, $subject, $body, $type);
        return $stmt->execute();
    }
    
    /**
     * Process email queue
     */
    public function processQueue() {
        // Check if PHPMailer is available
        if (!file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
            error_log("PHPMailer not found. Please install via Composer: composer require phpmailer/phpmailer");
            return false;
        }
        
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        
        // Get pending emails
        $result = $this->db->query(
            "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10"
        );
        
        $sent_count = 0;
        
        while ($email = $result->fetch_assoc()) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPSecure = $this->config['encryption'];
                $mail->Port = $this->config['port'];
                $mail->CharSet = 'UTF-8';
                
                if ($this->config['debug']) {
                    $mail->SMTPDebug = 2;
                }
                
                // Recipients
                $mail->setFrom($this->config['from_email'], $this->config['from_name']);
                $mail->addAddress($email['recipient_email']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $email['subject'];
                $mail->Body = $email['body'];
                
                // Send
                $mail->send();
                
                // Mark as sent
                $stmt = $this->db->prepare(
                    "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE queue_id = ?"
                );
                $stmt->bind_param("i", $email['queue_id']);
                $stmt->execute();
                
                $sent_count++;
                
            } catch (Exception $e) {
                // Mark as failed and increment attempts
                $error_msg = $mail->ErrorInfo;
                $stmt = $this->db->prepare(
                    "UPDATE email_queue SET status = 'failed', attempts = attempts + 1, error_message = ? WHERE queue_id = ?"
                );
                $stmt->bind_param("si", $error_msg, $email['queue_id']);
                $stmt->execute();
                
                error_log("Email sending failed: " . $error_msg);
            }
        }
        
        return $sent_count;
    }
    
    /**
     * Get admin emails
     */
    private function getAdminEmails() {
        $result = $this->db->query(
            "SELECT email, full_name FROM users WHERE role = 'admin' AND is_active = 1"
        );
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        return $admins;
    }
    
    /**
     * Get hourly statistics
     */
    private function getHourlyStats() {
        // Count opens and closes in the last hour
        $result = $this->db->query(
            "SELECT 
                SUM(CASE WHEN action = 'open' THEN 1 ELSE 0 END) as total_open,
                SUM(CASE WHEN action = 'close' THEN 1 ELSE 0 END) as total_close
            FROM gate_logs 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        $stats = $result->fetch_assoc();
        
        // Get recent activities
        $result = $this->db->query(
            "SELECT gl.action, gl.timestamp, u.full_name as user
            FROM gate_logs gl
            LEFT JOIN users u ON gl.action_by = u.user_id
            WHERE gl.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY gl.timestamp DESC
            LIMIT 10"
        );
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        $stats['activities'] = $activities;
        
        return $stats;
    }
}
?>
