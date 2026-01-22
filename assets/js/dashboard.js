/**
 * Dashboard JavaScript
 * Real-time updates, gate control, and charts
 */

// Configuration
const POLL_INTERVAL = 5000; // 5 seconds
let pollTimer = null;
let currentChart = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function () {
    initGateControls();
    initChart();
    startPolling();
});

// Gate control buttons
function initGateControls() {
    const btnOpen = document.getElementById('btnOpenGate');
    const btnClose = document.getElementById('btnCloseGate');

    if (btnOpen) {
        btnOpen.addEventListener('click', () => controlGate('open'));
    }

    if (btnClose) {
        btnClose.addEventListener('click', () => controlGate('close'));
    }
}

// Control gate (open/close)
async function controlGate(action) {
    const btnOpen = document.getElementById('btnOpenGate');
    const btnClose = document.getElementById('btnCloseGate');

    // Disable buttons
    if (btnOpen) btnOpen.disabled = true;
    if (btnClose) btnClose.disabled = true;

    try {
        const response = await fetch('/gs/api/gate_control.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action })
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Update UI immediately
            updateGateStatus(data.new_status === 'open');

            // Refresh activities
            refreshActivities();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error controlling gate:', error);
        alert('Gagal menghubungi server');
    } finally {
        // Re-enable appropriate button
        setTimeout(() => {
            updateGateStatus(gateCurrentStatus);
        }, 500);
    }
}

// Update gate status UI
function updateGateStatus(isOpen) {
    const statusText = document.getElementById('statusText');
    const gateIcon = document.querySelector('.gate-icon');
    const btnOpen = document.getElementById('btnOpenGate');
    const btnClose = document.getElementById('btnCloseGate');
    const lastUpdate = document.getElementById('lastUpdate');

    if (statusText) {
        statusText.textContent = isOpen ? 'Pagar Terbuka' : 'Pagar Tertutup';
        statusText.className = isOpen ? 'status-text text-success' : 'status-text text-muted';
    }

    if (gateIcon) {
        gateIcon.className = isOpen ? 'gate-icon gate-open' : 'gate-icon gate-closed';

        // Update icon SVG
        const openSvg = `
            <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                <rect x="10" y="20" width="25" height="80" rx="3" fill="#4ade80" opacity="0.3"/>
                <rect x="85" y="20" width="25" height="80" rx="3" fill="#4ade80" opacity="0.3"/>
                <rect x="15" y="25" width="4" height="70" fill="#22c55e"/>
                <rect x="23" y="25" width="4" height="70" fill="#22c55e"/>
                <rect x="91" y="25" width="4" height="70" fill="#22c55e"/>
                <rect x="99" y="25" width="4" height="70" fill="#22c55e"/>
            </svg>
        `;

        const closedSvg = `
            <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                <rect x="25" y="20" width="25" height="80" rx="3" fill="#94a3b8" opacity="0.3"/>
                <rect x="70" y="20" width="25" height="80" rx="3" fill="#94a3b8" opacity="0.3"/>
                <rect x="30" y="25" width="4" height="70" fill="#64748b"/>
                <rect x="38" y="25" width="4" height="70" fill="#64748b"/>
                <rect x="75" y="25" width="4" height="70" fill="#64748b"/>
                <rect x="83" y="25" width="4" height="70" fill="#64748b"/>
                <rect x="42" y="55" width="36" height="10" rx="2" fill="#64748b"/>
            </svg>
        `;

        gateIcon.innerHTML = isOpen ? openSvg : closedSvg;
    }

    if (btnOpen) btnOpen.disabled = isOpen;
    if (btnClose) btnClose.disabled = !isOpen;

    if (lastUpdate) {
        const now = new Date();
        lastUpdate.textContent = now.toLocaleTimeString('id-ID');
    }

    // Update global status
    window.gateCurrentStatus = isOpen;
}

// Polling for real-time updates
function startPolling() {
    if (typeof isUserDashboard !== 'undefined' && isUserDashboard) {
        // User dashboard only polls status
        pollTimer = setInterval(pollGateStatus, POLL_INTERVAL);
    } else {
        // Admin dashboard polls status and refreshes activities
        pollTimer = setInterval(() => {
            pollGateStatus();
            refreshActivities();
        }, POLL_INTERVAL);
    }
}

// Poll gate status
async function pollGateStatus() {
    try {
        const response = await fetch('/gs/api/gate_status.php');
        const data = await response.json();

        if (data.status === 'success') {
            updateGateStatus(data.is_open);

            // Update notification if needed
            const notificationBox = document.getElementById('notificationBox');
            if (notificationBox && data.show_alert) {
                updateNotification(true);
            } else if (notificationBox && !data.show_alert) {
                updateNotification(false);
            }
        }
    } catch (error) {
        console.error('Error polling status:', error);
    }
}

// Refresh activities
async function refreshActivities() {
    try {
        const response = await fetch('/gs/api/get_activities.php?limit=10');
        const data = await response.json();

        if (data.status === 'success') {
            const activityList = document.getElementById('activityList');
            if (activityList) {
                activityList.innerHTML = data.activities.map(activity => `
                    <div class="activity-item">
                        <span class="activity-time">${activity.formatted_time}</span>
                        <span class="activity-action ${activity.action === 'open' ? 'text-success' : 'text-danger'}">
                            Pagar ${activity.action === 'open' ? 'Dibuka' : 'Ditutup'}
                        </span>
                        <span class="activity-user text-muted">${activity.user}</span>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error refreshing activities:', error);
    }
}

// Update notification UI
function updateNotification(showAlert) {
    const notificationBox = document.getElementById('notificationBox');
    if (!notificationBox) return;

    if (showAlert) {
        notificationBox.className = 'notification-box alert-warning';
        notificationBox.innerHTML = `
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <h4>Peringatan!</h4>
                <p><strong>Pagar Terbuka Lebih dari 3 Menit!</strong></p>
                <p>Silakan Tutup Sekarang!</p>
            </div>
            <button class="btn btn-sm btn-warning" onclick="document.getElementById('btnCloseGate').click()">
                Kirim Notifikasi
            </button>
        `;
    } else {
        notificationBox.className = 'notification-box alert-info';
        notificationBox.innerHTML = `
            <div class="alert-icon">✓</div>
            <div class="alert-content">
                <h4>Semua Normal</h4>
                <p>Tidak ada notifikasi penting</p>
            </div>
        `;
    }
}

// Initialize chart
function initChart() {
    if (typeof chartData === 'undefined' || !chartData || !chartData.labels) {
        return;
    }

    const canvas = document.getElementById('usageChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Dibuka',
                    data: chartData.opens,
                    backgroundColor: '#fbbf24',
                    borderRadius: 6
                },
                {
                    label: 'Ditutup',
                    data: chartData.closes,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });
}

// Toggle settings (placeholder)
function toggleSettings() {
    alert('Pengaturan akan segera ditambahkan');
}

// Clean up on page unload
window.addEventListener('beforeunload', function () {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
