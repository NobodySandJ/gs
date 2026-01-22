<?php
/**
 * User Registration
 * Admin only feature - create new users
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Only admin can access
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'user';
    
    // Validation
    if (empty($username) || empty($password) || empty($email) || empty($full_name)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        $db = getDBConnection();
        
        // Check if username exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $db->prepare(
                "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $role);
            
            if ($stmt->execute()) {
                $success = 'User berhasil ditambahkan!';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Gagal menambahkan user: ' . $stmt->error;
            }
        }
    }
}

$page_title = 'Register User - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2>Register User Baru</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary">Kembali</a>
    </div>
    
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
    
    <div class="card">
        <form method="POST" action="" class="form">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    class="form-control" 
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="full_name">Nama Lengkap</label>
                <input 
                    type="text" 
                    name="full_name" 
                    id="full_name" 
                    class="form-control" 
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-control" 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="user" <?= (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-control" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirm_password" 
                    class="form-control" 
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary">Tambah User</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
