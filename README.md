# Garasi Smart - Smart Garage IoT System

Sistem monitoring dan kontrol pagar garasi berbasis IoT dengan notifikasi email otomatis dan dashboard real-time.

## 🚀 Features

- ✅ **Dashboard Real-time** - Monitoring status pagar secara langsung
- ✅ **Kontrol Pagar** - Buka/tutup pagar dari dashboard (Admin only)
- ✅ **Notifikasi Email** - Alert otomatis jika pagar terbuka >3 menit
- ✅ **Laporan Berkala** - Email report setiap 1 jam
- ✅ **IoT Integration** - REST API untuk ESP32/Arduino
- ✅ **Multi-User System** - Role Admin dan User
- ✅ **Statistik Penggunaan** - Grafik aktivitas harian
- ✅ **Riwayat Aktivitas** - Log lengkap buka/tutup pagar

## 📋 Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Composer (untuk PHPMailer)
- Akses SMTP untuk email notifications

## 🛠️ Installation

### 1. Clone/Copy Project

```bash
# Copy semua file ke folder web server Anda
# Contoh: c:\xampp\htdocs\gs atau /var/www/html/gs
```

### 2. Install Dependencies

```bash
cd c:\Githab\gs
composer require phpmailer/phpmailer
```

Jika tidak ada Composer, download PHPMailer manual:

- Download dari: https://github.com/PHPMailer/PHPMailer/releases
- Extract ke folder `vendor/phpmailer/phpmailer/`

### 3. Database Setup

```bash
# Import database schema
mysql -u root -p
```

Lalu jalankan SQL:

```sql
source c:\Githab\gs\database\schema.sql
```

Atau import via phpMyAdmin:

- Buka phpMyAdmin
- Create database `garasi_smart`
- Import file `database/schema.sql`

### 4. Configuration

#### Database Configuration

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Sesuaikan
define('DB_PASS', '');              // Sesuaikan
define('DB_NAME', 'garasi_smart');
```

#### Email Configuration (SMTP)

Update settings di database atau via admin panel:

**Untuk Gmail:**

1. Enable 2-Step Verification di Google Account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Update database:

```sql
UPDATE settings SET setting_value = 'smtp.gmail.com' WHERE setting_key = 'smtp_host';
UPDATE settings SET setting_value = '587' WHERE setting_key = 'smtp_port';
UPDATE settings SET setting_value = 'your-email@gmail.com' WHERE setting_key = 'smtp_username';
UPDATE settings SET setting_value = 'your-16-char-app-password' WHERE setting_key = 'smtp_password';
UPDATE settings SET setting_value = 'your-email@gmail.com' WHERE setting_key = 'smtp_from_email';
```

### 5. Setup Cron Jobs

**Windows (Task Scheduler):**

```
Program: C:\xampp\php\php.exe
Arguments: C:\Githab\gs\cron\check_gate_alerts.php
Run: Every 1 minute

Program: C:\xampp\php\php.exe
Arguments: C:\Githab\gs\cron\send_hourly_report.php
Run: Every 1 hour
```

**Linux (Crontab):**

```bash
crontab -e
```

Tambahkan:

```
* * * * * php /path/to/gs/cron/check_gate_alerts.php
0 * * * * php /path/to/gs/cron/send_hourly_report.php
```

## 🔑 Default Login

**Admin:**

- Username: `admin`
- Password: `admin123`

**User:**

- Username: `user1`
- Password: `admin123`

⚠️ **PENTING:** Segera ganti password setelah login pertama!

## 🤖 IoT Device Integration

### API Configuration

**Endpoint untuk update status:**

```
POST http://your-server.com/gs/api/iot/update_status.php
```

**Request Body:**

```json
{
  "api_key": "GS_2026_IoT_SecureKey_12345678901234567890",
  "status": "open",
  "timestamp": "2026-01-22 17:00:00"
}
```

**Endpoint untuk get command:**

```
GET http://your-server.com/gs/api/iot/get_command.php?api_key=YOUR_API_KEY
```

### ESP32 Arduino Example

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "YourWiFiSSID";
const char* password = "YourWiFiPassword";
const char* serverUrl = "http://192.168.1.100/gs/api/iot/update_status.php";
const char* apiKey = "GS_2026_IoT_SecureKey_12345678901234567890";

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Connected!");
}

void updateGateStatus(String status) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc["api_key"] = apiKey;
    doc["status"] = status;
    doc["timestamp"] = "2026-01-22 17:00:00";

    String json;
    serializeJson(doc, json);

    int httpCode = http.POST(json);

    if (httpCode > 0) {
      String response = http.getString();
      Serial.println(response);
    }

    http.end();
  }
}

void loop() {
  // Sensor logic here
  // Example: Send status every 5 seconds
  updateGateStatus("open");
  delay(5000);
  updateGateStatus("closed");
  delay(5000);
}
```

## 📁 Project Structure

```
gs/
├── admin/
│   └── dashboard.php          # Admin dashboard
├── api/
│   ├── gate_control.php       # Control gate API
│   ├── gate_status.php        # Get status API
│   ├── get_activities.php     # Get activities API
│   ├── get_statistics.php     # Get stats API
│   └── iot/
│       ├── update_status.php  # IoT status update
│       └── get_command.php    # IoT get command
├── assets/
│   ├── css/
│   │   ├── style.css          # Main styles
│   │   └── login.css          # Login page styles
│   └── js/
│       ├── main.js            # Common JS
│       └── dashboard.js       # Dashboard JS
├── auth/
│   ├── login.php              # Login page
│   ├── logout.php             # Logout handler
│   └── register.php           # User registration
├── config/
│   ├── constants.php          # System constants
│   ├── database.php           # DB configuration
│   └── email.php              # Email configuration
├── cron/
│   ├── check_gate_alerts.php  # Alert checker
│   └── send_hourly_report.php # Report sender
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   ├── auth_check.php         # Auth middleware
│   ├── EmailService.php       # Email service class
│   ├── header.php             # Shared header
│   └── footer.php             # Shared footer
├── user/
│   └── dashboard.php          # User dashboard
├── .htaccess                  # Apache configuration
└── README.md                  # This file
```

## 🔒 Security Notes

1. Ganti API key default di database (table `iot_devices`)
2. Ganti semua default passwords
3. Aktifkan HTTPS di production
4. Batasi akses API hanya dari IP IoT device
5. Backup database secara berkala

## 📧 Email Notifications

### Gate Open Alert (>3 menit)

- Trigger: Otomatis via cron setiap menit
- Recipients: Semua admin
- Template: `config/email.php` (gate_alert)

### Hourly Report

- Trigger: Setiap 1 jam via cron
- Recipients: Semua admin
- Content: Statistik aktivitas, activity log

## 🐛 Troubleshooting

**Email tidak terkirim:**

- Cek SMTP credentials di database
- Pastikan PHPMailer terinstall
- Cek error log PHP

**Dashboard tidak update:**

- Cek browser console untuk error JavaScript
- Pastikan path API benar di `dashboard.js`

**IoT device tidak terhubung:**

- Ping server dari device
- Cek API key di database
- Cek format JSON request

## 📝 License

MIT License - Free to use for personal and commercial projects

## 👨‍💻 Developer

Built with ❤️ for Smart Home Automation

---

**Version:** 1.0.0  
**Last Updated:** 2026-01-22
