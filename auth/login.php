<?php
/**
 * Login Page - Garasi Smart
 * Matches exact design from screenshot
 */

session_name('garasi_smart_session');
session_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] == 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php';
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

$error = '';
$success = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'admin';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT user_id, username, password, email, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Check if account is active
            if (!$user['is_active']) {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            }
            // Verify password
            elseif (password_verify($password, $user['password'])) {
                // Check if role matches selected login type
                if ($user['role'] !== $login_type) {
                    $error = 'Silakan pilih tipe login yang sesuai dengan role Anda.';
                } else {
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $stmt->bind_param("i", $user['user_id']);
                    $stmt->execute();
                    
                    // Redirect based on role
                    $redirect = $user['role'] == 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php';
                    header('Location: ' . BASE_URL . $redirect);
                    exit;
                }
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Sesi Anda telah berakhir. Silakan login kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Garasi Smart</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1 class="login-title">Login Garasi Smart</h1>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <select name="login_type" id="login_type" class="form-control" required>
                        <option value="">Pilih Login</option>
                        <option value="admin" <?= (isset($_POST['login_type']) && $_POST['login_type'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= (isset($_POST['login_type']) && $_POST['login_type'] == 'user') ? 'selected' : '' ?>>User</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-control" 
                        placeholder="Username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control" 
                        placeholder="Password"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p class="text-muted">Default login: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
