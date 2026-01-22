<?php
/**
 * User Dashboard - Simplified view-only version
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Require login
requireLogin();

$db = getDBConnection();

// Get current gate status
$status_result = $db->query("SELECT * FROM gate_status ORDER BY status_id DESC LIMIT 1");
$gate_status = $status_result->fetch_assoc();
$is_open = ($gate_status['current_status'] === 'open');

// Get recent activity logs
$activity_logs = $db->query(
    "SELECT gl.*, u.full_name 
    FROM gate_logs gl 
    LEFT JOIN users u ON gl.action_by = u.user_id 
    ORDER BY gl.timestamp DESC 
    LIMIT 15"
);

// Get user's personal statistics
$user_id = $_SESSION['user_id'];
$user_stats = $db->query(
    "SELECT 
        COUNT(*) as total_actions,
        SUM(CASE WHEN action = 'open' THEN 1 ELSE 0 END) as total_opens,
        SUM(CASE WHEN action = 'close' THEN 1 ELSE 0 END) as total_closes
    FROM gate_logs 
    WHERE action_by = $user_id"
)->fetch_assoc();

$page_title = 'Dashboard User - ' . APP_NAME;
$include_dashboard_js = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-grid">
        <!-- Status Pagar (Read-only) -->
        <div class="card">
            <div class="card-header">
                <h3>Status Pagar</h3>
            </div>
            <div class="card-body status-display" id="gateStatusDisplay">
                <div class="gate-icon <?= $is_open ? 'gate-open' : 'gate-closed' ?>">
                    <?php if ($is_open): ?>
                    <!-- Open gate icon -->
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <rect x="10" y="20" width="25" height="80" rx="3" fill="#4ade80" opacity="0.3"/>
                        <rect x="85" y="20" width="25" height="80" rx="3" fill="#4ade80" opacity="0.3"/>
                        <rect x="15" y="25" width="4" height="70" fill="#22c55e"/>
                        <rect x="23" y="25" width="4" height="70" fill="#22c55e"/>
                        <rect x="91" y="25" width="4" height="70" fill="#22c55e"/>
                        <rect x="99" y="25" width="4" height="70" fill="#22c55e"/>
                    </svg>
                    <?php else: ?>
                    <!-- Closed gate icon -->
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <rect x="25" y="20" width="25" height="80" rx="3" fill="#94a3b8" opacity="0.3"/>
                        <rect x="70" y="20" width="25" height="80" rx="3" fill="#94a3b8" opacity="0.3"/>
                        <rect x="30" y="25" width="4" height="70" fill="#64748b"/>
                        <rect x="38" y="25" width="4" height="70" fill="#64748b"/>
                        <rect x="75" y="25" width="4" height="70" fill="#64748b"/>
                        <rect x="83" y="25" width="4" height="70" fill="#64748b"/>
                        <rect x="42" y="55" width="36" height="10" rx="2" fill="#64748b"/>
                    </svg>
                    <?php endif; ?>
                </div>
                <h2 class="status-text <?= $is_open ? 'text-success' : 'text-muted' ?>" id="statusText">
                    <?= $is_open ? 'Pagar Terbuka' : 'Pagar Tertutup' ?>
                </h2>
                <p class="text-muted">Terakhir diperbarui: <span id="lastUpdate"><?= date('H:i:s') ?></span></p>
            </div>
        </div>

        <!-- Statistik Pribadi -->
        <div class="card">
            <div class="card-header">
                <h3>Statistik Saya</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value text-primary"><?= $user_stats['total_actions'] ?></div>
                        <div class="stat-label">Total Aktivitas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-success"><?= $user_stats['total_opens'] ?></div>
                        <div class="stat-label">Pagar Dibuka</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-danger"><?= $user_stats['total_closes'] ?></div>
                        <div class="stat-label">Pagar Ditutup</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Aktivitas -->
        <div class="card card-full-width">
            <div class="card-header">
                <h3>Riwayat Aktivitas</h3>
                <button class="btn-icon" onclick="location.reload()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 3a5 5 0 1 0 5 5h-2a3 3 0 1 1-3-3V3z"/>
                    </svg>
                </button>
            </div>
            <div class="card-body">
                <div class="activity-list" id="activityList">
                    <?php while ($log = $activity_logs->fetch_assoc()): ?>
                    <div class="activity-item">
                        <span class="activity-time"><?= date('d/m/Y H:i', strtotime($log['timestamp'])) ?></span>
                        <span class="activity-action <?= $log['action'] == 'open' ? 'text-success' : 'text-danger' ?>">
                            Pagar <?= $log['action'] == 'open' ? 'Dibuka' : 'Ditutup' ?>
                        </span>
                        <span class="activity-user text-muted">
                            oleh <?= $log['full_name'] ?: 'IoT Device' ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-header">
                <h3>Informasi</h3>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <p class="text-muted">Anda login sebagai <strong>User</strong>.</p>
                    <p class="text-muted">Untuk mengontrol pagar, hubungi Administrator.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const chartData = null; // No chart for user
const gateCurrentStatus = <?= json_encode($is_open) ?>;
const isUserDashboard = true;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
