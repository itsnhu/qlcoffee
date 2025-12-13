<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';

// Get cart count
$cartCount = 0;
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $result = $stmt->fetch();
    $cartCount = $result['count'] ?? 0;
}

// Get categories for menu
$categories = fetchAll($pdo, "SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'PharmaManager' ?> - Nhà thuốc trực tuyến</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💊</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* User Store Specific Styles */
        .navbar-store {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-200);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .navbar-store .navbar-brand {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-700) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-store .navbar-brand i {
            font-size: 1.75rem;
            color: var(--primary-500);
        }

        .search-box {
            max-width: 480px;
            flex: 1;
        }

        .search-box .form-control {
            border-radius: 25px 0 0 25px;
            padding: 0.625rem 1.25rem;
            border: 2px solid var(--gray-200);
            background: var(--gray-50);
            font-family: var(--font-body);
            border-right: none;
        }

        .search-box .form-control:focus {
            border-color: var(--primary-400);
            box-shadow: none;
            background: white;
        }

        .search-box .btn {
            border-radius: 0 25px 25px 0;
            padding: 0.625rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: 2px solid var(--primary-500);
            color: white;
        }

        .search-box .btn:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            border-color: var(--primary-600);
        }

        .nav-icon-link {
            position: relative;
            color: var(--gray-600);
            padding: 0.5rem 0.75rem;
            transition: all 0.2s;
        }

        .nav-icon-link:hover {
            color: var(--primary-600);
        }

        .nav-icon-link i {
            font-size: 1.35rem;
        }

        .cart-badge {
            position: absolute;
            top: 0;
            right: 2px;
            font-size: 0.65rem;
            padding: 2px 6px;
            background: var(--danger);
            color: white;
            border-radius: 50px;
            font-weight: 600;
        }

        .user-menu .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-700);
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            text-decoration: none;
        }

        .user-menu .dropdown-toggle:hover {
            color: var(--primary-600);
        }

        .user-menu .dropdown-toggle::after {
            margin-left: 0.25rem;
        }

        .category-bar {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 0.5rem 0;
        }

        .category-bar .nav-link {
            font-family: var(--font-display);
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--gray-600);
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            transition: all 0.2s;
        }

        .category-bar .nav-link:hover {
            color: var(--primary-600);
            background: var(--primary-50);
        }

        .product-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-200);
        }

        .product-card .card-img-top {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gray-50), var(--primary-50));
        }

        .product-card .card-body {
            padding: 1rem;
        }

        .product-price {
            color: var(--danger);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: var(--radius-full);
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            color: white;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            transform: translateY(-2px);
            color: white;
        }

        .footer-store {
            background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }

        .footer-store h6 {
            font-family: var(--font-display);
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .footer-store a {
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .footer-store a:hover {
            color: var(--primary-400);
        }

        .footer-store .footer-brand {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-store .footer-brand i {
            color: var(--primary-400);
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .hero-section h1 {
            font-family: var(--font-display);
            font-weight: 800;
            color: white;
        }

        .section-title {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-500);
        }

        .feature-box {
            text-align: center;
            padding: 1rem;
        }

        .feature-box i {
            font-size: 2rem;
            color: var(--primary-500);
            margin-bottom: 0.5rem;
        }

        .feature-box h6 {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .feature-box p {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin: 0;
        }

        .category-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-300);
            color: inherit;
        }

        .category-card i {
            font-size: 2.5rem;
            color: var(--primary-500);
            margin-bottom: 0.75rem;
        }

        .category-card h6 {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .category-card small {
            color: var(--gray-500);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-store">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/user/">
                <i class="bi bi-capsule-pill"></i>
                PharmaManager
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarStore">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarStore">
                <!-- Search Box -->
                <form class="d-flex mx-lg-4 my-3 my-lg-0 search-box" action="<?= BASE_URL ?>/user/products.php" method="GET">
                    <input class="form-control" type="search" name="search" placeholder="Tìm kiếm thuốc, vitamin..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <!-- User Menu -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-icon-link" href="<?= BASE_URL ?>/user/cart.php">
                            <i class="bi bi-cart3"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item dropdown user-menu">
                            <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($_SESSION['customer_name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php"><i class="bi bi-person me-2"></i>Tài khoản</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/orders.php"><i class="bi bi-box-seam me-2"></i>Đơn hàng</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/user/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary btn-sm ms-2" href="<?= BASE_URL ?>/login.php?type=customer">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-2" href="<?= BASE_URL ?>/register.php">
                                Đăng ký
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Category Navigation -->
    <nav class="category-bar d-none d-lg-block">
        <div class="container">
            <ul class="nav justify-content-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/user/products.php"><i class="bi bi-grid me-1"></i>Tất cả</a>
                </li>
                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/user/products.php?category=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- Flash Message -->
    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show">
                <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
                <?= $message['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <main>
