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
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>PharmaManager</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💊</text></svg>">

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

    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-pharma">
        <div class="container-fluid px-4">
            <!-- Brand -->
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">
                <i class="bi bi-capsule-pill"></i>
                PharmaManager
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($isLoggedIn): ?>
                    <ul class="navbar-nav me-auto">
                        <?php if ($userRole === 'admin'): ?>
                            <!-- Admin Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-database-fill"></i> Quản lý
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users/index.php">
                                            <i class="bi bi-people-fill me-2 text-primary"></i> Người dùng
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/medicines/index.php">
                                            <i class="bi bi-capsule me-2 text-success"></i> Thuốc
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/categories/index.php">
                                            <i class="bi bi-tags-fill me-2 text-warning"></i> Loại thuốc
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/suppliers/index.php">
                                            <i class="bi bi-truck me-2 text-info"></i> Nhà cung cấp
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/imports/index.php">
                                    <i class="bi bi-box-arrow-in-down-right"></i> Nhập hàng
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/sales/index.php">
                                    <i class="bi bi-cart-check-fill"></i> Bán hàng
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/orders/index.php">
                                    <i class="bi bi-bag-check"></i> Đơn hàng Online
                                    <?php
                                    $pendingOrders = fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0;
                                    if ($pendingOrders > 0):
                                    ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $pendingOrders; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-bar-chart-fill"></i> Báo cáo
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reports/inventory.php">
                                            <i class="bi bi-box-seam me-2 text-primary"></i> Tồn kho
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reports/sales.php">
                                            <i class="bi bi-graph-up-arrow me-2 text-success"></i> Doanh thu
                                        </a>
                                    </li>
                                </ul>
                            </li>

                        <?php else: ?>
                            <!-- Employee Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/dashboard.php">
                                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/medicines/index.php">
                                    <i class="bi bi-capsule"></i> Tra cứu thuốc
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/sales/create.php">
                                    <i class="bi bi-cart-check-fill"></i> Bán hàng
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/sales/index.php">
                                    <i class="bi bi-clock-history"></i> Lịch sử
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- User Section -->
                    <div class="d-flex align-items-center">
                        <div class="user-badge">
                            <div class="user-avatar"><?php echo $userInitial; ?></div>
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                                <span class="user-role">
                                    <?php echo $userRole === 'admin' ? 'Quản trị viên' : 'Nhân viên'; ?>
                                </span>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn-logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-md-inline">Đăng xuất</span>
                        </a>
                    </div>

                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid px-4">
