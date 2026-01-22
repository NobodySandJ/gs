<?php
/**
 * Admin Dashboard - Garasi Smart
 * Exact match with design screenshot
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Require admin access
requireAdmin();

$db = getDBConnection();

// Get current gate status
$status_result = $db->query("SELECT * FROM gate_status ORDER BY status_id DESC LIMIT 1");
$gate_status = $status_result->fetch_assoc();
$is_open = ($gate_status['current_status'] === 'open');

// Check if gate has been open >3 minutes
$gate_open_time = null;
$show_alert = false;
if ($is_open) {
    $last_open = $db->query("SELECT timestamp FROM gate_logs WHERE action = 'open' ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();
    if ($last_open) {
        $gate_open_time = strtotime($last_open['timestamp']);
        $time_diff = time() - $gate_open_time;
        $show_alert = ($time_diff > GATE_ALERT_TIMEOUT);
    }
}

// Get recent activity logs
$activity_logs = $db->query(
    "SELECT gl.*, u.full_name 
    FROM gate_logs gl 
    LEFT JOIN users u ON gl.action_by = u.user_id 
    ORDER BY gl.timestamp DESC 
    LIMIT 10"
);

// Get daily statistics for the last 7 days
$stats_result = $db->query(
    "SELECT 
        DATE_FORMAT(timestamp, '%a') as day,
        SUM(CASE WHEN action = 'open' THEN 1 ELSE 0 END) as opens,
        SUM(CASE WHEN action = 'close' THEN 1 ELSE 0 END) as closes
    FROM gate_logs 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(timestamp)
    ORDER BY DATE(timestamp) ASC"
);

$chart_data = [
    'labels' => [],
    'opens' => [],
    'closes' => []
];

while ($row = $stats_result->fetch_assoc()) {
    $chart_data['labels'][] = $row['day'];
    $chart_data['opens'][] = (int)$row['opens'];
    $chart_data['closes'][] = (int)$row['closes'];
}

// Get settings
$notification_time = getSetting('alert_timeout_seconds', GATE_ALERT_TIMEOUT) / 60; // in minutes

$page_title = 'Dashboard Admin - ' . APP_NAME;
$include_dashboard_js = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-grid">
        <!-- Kontrol Pagar -->
        <div class="card">
            <div class="card-header">
                <h3>Kontrol Pagar</h3>
            </div>
            <div class="card-body control-buttons">
                <button class="btn btn-success btn-gate btn-open" id="btnOpenGate" <?= $is_open ? 'disabled' : '' ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="18" />
                        <rect x="14" y="3" width="7" height="18" />
                        <path d="M10 12h4M10 8h4M10 16h4" />
                    </svg>
                    Buka Gate
                </button>
                <button class="btn btn-danger btn-gate btn-close" id="btnCloseGate" <?= !$is_open ? 'disabled' : '' ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="18" />
                        <rect x="14" y="3" width="7" height="18" />
                        <path d="M10 12h4" />
                    </svg>
                    Tutup Gate
                </button>
            </div>
        </div>

        <!-- Status Pagar -->
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

        <!-- Notifikasi -->
        <div class="card">
            <div class="card-header">
                <h3>Notifikasi</h3>
            </div>
            <div class="card-body">
                <div class="notification-box <?= $show_alert ? 'alert-warning' : 'alert-info' ?>" id="notificationBox">
                    <?php if ($show_alert): ?>
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <h4>Peringatan!</h4>
                        <p><strong>Pagar Terbuka Lebih dari <?= round($notification_time) ?> Menit!</strong></p>
                        <p>Silakan Tutup Sekarang!</p>
                    </div>
                    <button class="btn btn-sm btn-warning" onclick="document.getElementById('btnCloseGate').click()">
                        Kirim Notifikasi
                    </button>
                    <?php else: ?>
                    <div class="alert-icon">✓</div>
                    <div class="alert-content">
                        <h4>Semua Normal</h4>
                        <p>Tidak ada notifikasi penting</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Laporan Aktivitas -->
        <div class="card">
            <div class="card-header">
                <h3>Laporan Aktivitas</h3>
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
                        <span class="activity-time"><?= date('H:i A', strtotime($log['timestamp'])) ?></span>
                        <span class="activity-action <?= $log['action'] == 'open' ? 'text-success' : 'text-danger' ?>">
                            Pagar <?= $log['action'] == 'open' ? 'Dibuka' : 'Ditutup' ?>
                        </span>
                        <span class="activity-user text-muted">
                            <?= $log['full_name'] ?: 'IoT Device' ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Statistik Penggunaan -->
        <div class="card">
            <div class="card-header">
                <h3>Statistik Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="stats-label">Aktivitas Harian</div>
                <canvas id="usageChart" width="400" height="200"></canvas>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color" style="background: #fbbf24;"></span>
                        <span>Dibuka</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background: #3b82f6;"></span>
                        <span>Ditutup</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengaturan -->
        <div class="card">
            <div class="card-header">
                <h3>Pengaturan</h3>
                <button class="btn-icon" onclick="toggleSettings()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M9 16l-1-3h-1L6 16H4l1-3C3.8 12.4 3 11.3 3 10c0-1.7 1.3-3 3-3h4c1.7 0 3 1.3 3 3 0 1.3-.8 2.4-2 2.9l1 3h-2zM6 4a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/>
                    </svg>
                </button>
            </div>
            <div class="card-body">
                <div class="setting-item">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a6 6 0 0 1 6 6v3.586l.707.707A1 1 0 0 1 16 14H4a1 1 0 0 1-.707-1.707L4 11.586V8a6 6 0 0 1 6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z"/>
                    </svg>
                    <div class="setting-content">
                        <div class="setting-label">Waktu Notifikasi</div>
                        <div class="setting-value"><?= round($notification_time) ?> Menit</div>
                    </div>
                    <svg class="chevron-right" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6 3l5 5-5 5V3z"/>
                    </svg>
                </div>
                <div class="setting-item" onclick="location.href='<?= BASE_URL ?>/admin/settings.php'">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm1 11H9V9h2v4zm0-6H9V5h2v2z"/>
                    </svg>
                    <div class="setting-content">
                        <div class="setting-label">Pengaturan Sistem</div>
                    </div>
                    <svg class="chevron-right" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6 3l5 5-5 5V3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass chart data to JavaScript
const chartData = <?= json_encode($chart_data) ?>;
const gateCurrentStatus = <?= json_encode($is_open) ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
