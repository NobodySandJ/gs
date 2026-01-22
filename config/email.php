<?php
/**
 * Email Configuration
 * Smart Garage System
 * 
 * IMPORTANT: Update these settings with your SMTP credentials
 */

require_once __DIR__ . '/database.php';

// Get SMTP settings from database
function getEmailConfig() {
    return [
        'host' => getSetting('smtp_host', 'smtp.gmail.com'),
        'port' => (int)getSetting('smtp_port', 587),
        'username' => getSetting('smtp_username', ''),
        'password' => getSetting('smtp_password', ''),
        'from_email' => getSetting('smtp_from_email', 'noreply@garasismart.local'),
        'from_name' => getSetting('smtp_from_name', 'Garasi Smart System'),
        'encryption' => 'tls', // or 'ssl'
        'debug' => EMAIL_DEBUG
    ];
}

// Email templates
function getEmailTemplate($type, $data = []) {
    $templates = [
        'gate_alert' => [
            'subject' => '⚠️ PERINGATAN! Pagar Terbuka Lebih dari 3 Menit',
            'body' => '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                        .alert-box { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin: 20px 0; }
                        .btn { display: inline-block; background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        .footer { text-align: center; color: #666; margin-top: 30px; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🚨 PERINGATAN GARASI!</h1>
                        </div>
                        <div class="alert-box">
                            <h2>⚠️ Pagar Terbuka Lebih dari 3 Menit!</h2>
                            <p><strong>Waktu Kejadian:</strong> ' . ($data['timestamp'] ?? date('d/m/Y H:i:s')) . '</p>
                            <p><strong>Status:</strong> Pagar masih dalam keadaan TERBUKA</p>
                            <p>Silakan segera tutup pagar untuk keamanan rumah Anda.</p>
                        </div>
                        <p style="text-align: center;">
                            <a href="' . BASE_URL . '/admin/dashboard.php" class="btn">Tutup Pagar Sekarang</a>
                        </p>
                        <div class="footer">
                            <p>Email otomatis dari Garasi Smart System</p>
                            <p>Jangan balas email ini</p>
                        </div>
                    </div>
                </body>
                </html>
            '
        ],
        'hourly_report' => [
            'subject' => '📊 Laporan Aktivitas Pagar - ' . date('d/m/Y H:i'),
            'body' => '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
                        .stat-box { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; flex: 1; margin: 0 5px; }
                        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f8f9fa; font-weight: bold; }
                        .footer { text-align: center; color: #666; margin-top: 30px; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>📊 Laporan Aktivitas Pagar</h1>
                            <p>' . date('d F Y, H:i') . '</p>
                        </div>
                        <div class="stats">
                            <div class="stat-box">
                                <div class="stat-number">' . ($data['total_open'] ?? 0) . '</div>
                                <div>Pagar Dibuka</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number">' . ($data['total_close'] ?? 0) . '</div>
                                <div>Pagar Ditutup</div>
                            </div>
                        </div>
                        <h3>Aktivitas Terakhir:</h3>
                        <table>
                            <tr>
                                <th>Waktu</th>
                                <th>Aksi</th>
                                <th>Oleh</th>
                            </tr>
                            ' . ($data['activities'] ?? '<tr><td colspan="3">Tidak ada aktivitas</td></tr>') . '
                        </table>
                        <div class="footer">
                            <p>Email otomatis dari Garasi Smart System</p>
                            <p>Laporan ini dikirim setiap 1 jam sekali</p>
                        </div>
                    </div>
                </body>
                </html>
            '
        ]
    ];
    
    return $templates[$type] ?? null;
}

/*
 * SETUP INSTRUCTIONS:
 * 
 * For Gmail:
 * 1. Enable 2-Step Verification in your Google Account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Use the 16-character app password as smtp_password
 * 
 * Example settings in database:
 * - smtp_host: smtp.gmail.com
 * - smtp_port: 587
 * - smtp_username: your-email@gmail.com
 * - smtp_password: your-app-password (16 chars)
 * - smtp_from_email: your-email@gmail.com
 * - smtp_from_name: Garasi Smart
 */
?>
