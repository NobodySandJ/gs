<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth_check.php';

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? APP_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <button class="menu-toggle" id="menuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1><span class="brand-name">Garasi Smart</span> <span class="page-subtitle">Dashboard</span></h1>
            </div>
            <div class="nav-user">
                <div class="user-dropdown">
                    <button class="user-btn" id="userDropdownBtn">
                        <div class="user-avatar">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 10c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <span><?= htmlspecialchars($current_user['role'] == ROLE_ADMIN ? 'Admin' : $current_user['username']) ?></span>
                        <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M6 9L1 4h10L6 9z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <div class="dropdown-header">
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($current_user['full_name']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($current_user['email']) ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php if ($current_user['role'] == ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 2v12M2 8h12"/>
                            </svg>
                            Dashboard Admin
                        </a>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/user/dashboard.php" class="dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 2v12M2 8h12"/>
                            </svg>
                            Dashboard User
                        </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item text-danger">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M6 14H3a1 1 0 01-1-1V3a1 1 0 011-1h3M11 11l3-3-3-3M14 8H6"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
