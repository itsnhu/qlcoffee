<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['full_name'] ?? 'User') : '';
$userRole = $isLoggedIn ? ($_SESSION['role'] ?? 'employee') : '';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>TNT Coffee</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/pos.css">

    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
    
    <style>
        /* Critical Layout Fix */
        .dashboard-container { display: flex; min-height: 100vh; background: #f5f5f9; }
        .sidebar { 
            width: 260px; background: #1e1e2d; color: #a2a3b7; 
            position: fixed; height: 100vh; left: 0; top: 0; z-index: 1000; 
            display: flex; flex-direction: column; 
        }
        .main-content-wrapper { flex-grow: 1; margin-left: 260px; min-width: 0; }
        .dashboard-topbar { 
            height: 70px; background: white; box-shadow: 0 1px 15px rgba(0,0,0,0.05); 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 2rem; position: sticky; top: 0; z-index: 999; 
        }
        .user-profile-mini { display: flex; align-items: center; gap: 1rem; }
        .user-profile-info { text-align: right; line-height: 1.2; }
        .user-profile-name { display: block; font-weight: 700; color: #1e293b; font-size: 0.95rem; }
        .user-profile-role { font-size: 0.75rem; color: #64748b; font-weight: 500; }
        .user-profile-avatar { 
            width: 42px; height: 42px; border-radius: 12px; 
            background: #f0fdfa; color: #14b8a6; 
            display: flex; align-items: center; justify-content: center; 
            font-weight: 800; font-size: 1.1rem; border: 1px solid rgba(20, 184, 166, 0.1);
        }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; }
        .menu-link { 
            display: flex; align-items: center; gap: 1rem; padding: 0.875rem 1.5rem; 
            color: #a2a3b7 !important; text-decoration: none !important; 
        }
        .menu-link:hover, .menu-link.active { background: #1b1b28; color: #ffffff !important; }
        .sidebar-brand { padding: 2rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: white !important; text-decoration: none; font-weight: 800; }
        .content-body { padding: 2rem; }
        .dash-card { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 2px 6px rgba(67, 89, 113, 0.12); height: 100%; border: none; }
        
        .sidebar-section-title {
            list-style: none;
            padding: 1.25rem 1.5rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: #565674;
            text-transform: uppercase;
        }

        .submenu { list-style: none; padding-left: 2.5rem; background: rgba(0,0,0,0.1); }
        .submenu-item { margin-bottom: 2px; }
        .submenu-link {
            display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem;
            color: #a2a3b7 !important; text-decoration: none !important;
            font-size: 0.85rem; transition: all 0.2s; border-radius: 4px;
        }
        .submenu-link:hover, .submenu-link.active { color: white !important; background: rgba(255,255,255,0.05); }
        .submenu-link.active { font-weight: 600; }
        
        .menu-link[data-bs-toggle="collapse"]::after { display: none; }
        .menu-link .transition-icon { transition: transform 0.3s ease; font-size: 0.8rem; }
        .menu-link[aria-expanded="true"] .transition-icon { transform: rotate(90deg); }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="sidebar">
                <a href="<?= BASE_URL ?>/" class="sidebar-brand">
                    <i class="bi bi-cup-hot-fill text-primary"></i>
                    <span>TNT Coffee</span>
                </a>

                <ul class="sidebar-menu">
                    <?php if ($userRole === 'admin'): ?>
                        <li class="sidebar-section-title">QUẢN LÝ</li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="menu-link <?= strpos($_SERVER['PHP_SELF'], 'admin/dashboard.php') !== false ? 'active' : '' ?>">
                                <i class="bi bi-grid-1x2-fill"></i> Trang chủ
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/products/index.php" class="menu-link <?= (strpos($_SERVER['PHP_SELF'], 'admin/products/') !== false) ? 'active' : '' ?>">
                                <i class="bi bi-cup-hot-fill"></i> Quản lý sản phẩm
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/sales/index.php" class="menu-link <?= (strpos($_SERVER['PHP_SELF'], 'admin/sales/') !== false) ? 'active' : '' ?>">
                                <i class="bi bi-clock-history"></i> Lịch sử đơn hàng
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/users/index.php" class="menu-link <?= strpos($_SERVER['PHP_SELF'], 'admin/users/') !== false ? 'active' : '' ?>">
                                <i class="bi bi-people-fill"></i> Quản lý nhân viên
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/tables/index.php" class="menu-link <?= strpos($_SERVER['PHP_SELF'], 'admin/tables/') !== false ? 'active' : '' ?>">
                                <i class="bi bi-grid-3x3-gap-fill"></i> Quản lý bàn
                            </a>
                        </li>

                        <li class="sidebar-section-title">THỐNG KÊ</li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/admin/reports/sales.php" class="menu-link <?= strpos($_SERVER['PHP_SELF'], 'admin/reports/') !== false ? 'active' : '' ?>">
                                <i class="bi bi-bar-chart-fill"></i> Báo cáo doanh thu
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="sidebar-section-title">QUẢN LÝ CHUNG</li>
                        <li class="menu-item">
                            <a href="<?= BASE_URL ?>/employee/tables/index.php" class="menu-link <?= (strpos($_SERVER['PHP_SELF'], 'employee/tables/index.php') !== false) ? 'active' : '' ?>">
                                <i class="bi bi-grid-3x3-gap"></i> Danh sách các bàn
                            </a>
                        </li>

                        <?php 
                            $isMgmtOpen = (strpos($_SERVER['PHP_SELF'], 'employee/orders/') !== false || 
                                           strpos($_SERVER['PHP_SELF'], 'admin/sales/') !== false || 
                                           strpos($_SERVER['PHP_SELF'], 'employee/products/') !== false || 
                                           strpos($_SERVER['PHP_SELF'], 'employee/tables/manage.php') !== false ||
                                           strpos($_SERVER['PHP_SELF'], 'employee/tables/create.php') !== false ||
                                           strpos($_SERVER['PHP_SELF'], 'employee/tables/edit.php') !== false ||
                                           strpos($_SERVER['PHP_SELF'], 'employee/tables/delete.php') !== false ||
                                           strpos($_SERVER['PHP_SELF'], 'employee/bookings/') !== false ||
                                           strpos($_SERVER['PHP_SELF'], 'employee/settings/') !== false);
                        ?>
                        <li class="menu-item">
                            <a href="#mgmtSubmenu" data-bs-toggle="collapse" class="menu-link <?= $isMgmtOpen ? '' : 'collapsed' ?>" aria-expanded="<?= $isMgmtOpen ? 'true' : 'false' ?>">
                                <i class="bi bi-person-gear"></i> Quản lý
                                <i class="bi bi-chevron-right ms-auto transition-icon"></i>
                            </a>
                            <ul class="submenu list-unstyled collapse <?= $isMgmtOpen ? 'show' : '' ?>" id="mgmtSubmenu">
                                <li class="submenu-item">
                                    <a href="<?= BASE_URL ?>/employee/orders/index.php" class="submenu-link <?= (strpos($_SERVER['PHP_SELF'], 'employee/orders/index.php') !== false) ? 'active' : '' ?>">
                                        <i class="bi bi-receipt-cutoff"></i> Hóa đơn
                                    </a>
                                </li>
                                <li class="submenu-item">
                                    <a href="<?= BASE_URL ?>/employee/products/index.php" class="submenu-link <?= (strpos($_SERVER['PHP_SELF'], 'products/') !== false) ? 'active' : '' ?>">
                                        <i class="bi bi-box-seam"></i> Sản phẩm
                                    </a>
                                </li>

                                <li class="submenu-item">
                                    <a href="<?= BASE_URL ?>/employee/tables/manage.php" class="submenu-link <?= strpos($_SERVER['PHP_SELF'], 'tables/manage.php') !== false ? 'active' : '' ?>">
                                        <i class="bi bi-grid-3x3-gap"></i> Bàn
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-section-title">CÀI ĐẶT</li>
                        <?php 
                            $isAccountOpen = (strpos($_SERVER['PHP_SELF'], 'employee/settings/') !== false);
                        ?>
                        <li class="menu-item">
                            <a href="#accountSubmenu" data-bs-toggle="collapse" class="menu-link <?= $isAccountOpen ? '' : 'collapsed' ?>" aria-expanded="<?= $isAccountOpen ? 'true' : 'false' ?>">
                                <i class="bi bi-person"></i> Tài khoản
                                <i class="bi bi-chevron-right ms-auto transition-icon"></i>
                            </a>
                            <ul class="submenu list-unstyled collapse <?= $isAccountOpen ? 'show' : '' ?>" id="accountSubmenu">
                                <li class="submenu-item">
                                    <a href="<?= BASE_URL ?>/employee/settings/password.php" class="submenu-link <?= (strpos($_SERVER['PHP_SELF'], 'password.php') !== false) ? 'active' : '' ?>">
                                        <i class="bi bi-key"></i> Thay đổi mật khẩu
                                    </a>
                                </li>
                                <li class="submenu-item">
                                    <a href="<?= BASE_URL ?>/employee/settings/profile.php" class="submenu-link <?= (strpos($_SERVER['PHP_SELF'], 'profile.php') !== false) ? 'active' : '' ?>">
                                        <i class="bi bi-person-vcard"></i> Thông tin tài khoản
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

            </aside>

            <div class="main-content-wrapper">
                <!-- Topbar -->
                <header class="dashboard-topbar">
                    <div class="topbar-left">
                        <h5 class="mb-0 fw-bold"><?= $pageTitle ?? 'Hệ thống Quản lý' ?></h5>
                    </div>

                    <div class="topbar-right d-flex align-items-center gap-4">
                        <div class="user-profile-mini">
                            <div class="user-profile-info d-none d-md-block">
                                <span class="user-profile-name"><?= htmlspecialchars($userName) ?></span>
                                <span class="user-profile-role"><?= $userRole === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></span>
                            </div>
                            <div class="user-profile-avatar">
                                <?= $userInitial ?>
                            </div>
                        </div>
                        <div class="logout-wrapper border-start ps-4">
                            <a href="<?= BASE_URL ?>/logout.php" class="text-danger text-decoration-none fw-bold small d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right fs-5"></i> 
                                <span class="d-none d-md-inline">Đăng xuất</span>
                            </a>
                        </div>
                    </div>
                </header>

                <div class="content-body">
    <?php else: ?>
        <!-- Guest Navbar -->
        <nav class="navbar navbar-expand-lg navbar-pharma">
            <div class="container">
                <a class="navbar-brand" href="<?= BASE_URL ?>/">
                    <i class="bi bi-cup-hot-fill text-primary"></i> Coffee Manager
                </a>
                <div class="ms-auto">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary rounded-pill px-4">Đăng nhập</a>
                </div>
            </div>
        </nav>
        <main class="py-5">
            <div class="container">
    <?php endif; ?>
