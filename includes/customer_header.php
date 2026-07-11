<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);

// Initialize cart count
$cartCount = 0;

if ($isCustomerLoggedIn) {
    // Database cart for logged in users
    $customerId = $_SESSION['customer_id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $cartCount = (int)$stmt->fetchColumn();
} else {
    // Session cart for guests
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cartCount += $item['quantity'];
        }
    }
}
?>

<?php
// isCustomerLoggedIn is defined at the top
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/globals.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/style.css">
    
    <style>
        /* Dropdown on hover */
        @media (min-width: 992px) {
            .navbar-nav .dropdown:hover > .dropdown-menu {
                display: block;
                margin-top: 0;
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            .navbar-nav .dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: all 0.3s ease;
            }
        }
        
        .blog-dropdown-item {
            padding: 10px 20px;
            font-weight: 500;
            transition: 0.2s;
            border-left: 3px solid transparent;
        }
        .blog-dropdown-item:hover {
            background-color: var(--color-gold-light, #FFF9E1);
            color: var(--color-gold, #e67e22) !important;
            border-left: 3px solid var(--color-gold);
            padding-left: 25px;
        }
        .blog-dropdown-item i {
            width: 25px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Cart Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 10002;">
      <div id="cartToast" class="toast align-items-center text-white bg-dark border-0 rounded-4 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex p-2">
          <div class="toast-body d-flex align-items-center gap-3">
            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-check-lg text-white fs-5"></i>
            </div>
            <span class="fw-bold">Đã thêm vào giỏ hàng!</span>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>

    <style>
        .cart-anim {
            animation: cartPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes cartPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.6); }
            100% { transform: scale(1); }
        }
    </style>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-custom" href="<?php echo BASE_URL; ?>">
                <div class="logo-circle">
                    <div class="logo-inner">
                        <i class="bi bi-cup-hot-fill"></i>
                    </div>
                </div>
                TNT Coffee
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="<?php echo BASE_URL; ?>">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="<?php echo BASE_URL; ?>/menu.php">Thực đơn</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle" href="<?php echo BASE_URL; ?>/blog.php" id="blogDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Blog
                        </a>
                        <ul class="dropdown-menu border-0 shadow-lg mt-0 py-2" aria-labelledby="blogDropdown" style="border-radius: 15px; min-width: 220px;">
                            <li><a class="dropdown-item blog-dropdown-item" href="<?php echo BASE_URL; ?>/blog.php">✨ Tất cả bài viết</a></li>
                            <li><hr class="dropdown-divider opacity-10"></li>
                            <li><a class="dropdown-item blog-dropdown-item" href="<?php echo BASE_URL; ?>/blog.php?category=Mẹo pha chế">☕ Mẹo pha chế</a></li>
                            <li><a class="dropdown-item blog-dropdown-item" href="<?php echo BASE_URL; ?>/blog.php?category=Check-in">📸 Check-in quán</a></li>
                            <li><a class="dropdown-item blog-dropdown-item" href="<?php echo BASE_URL; ?>/blog.php?category=Kiến thức">🌿 Kiến thức hạt</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="<?php echo BASE_URL; ?>/about.php">Về chúng tôi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="<?php echo BASE_URL; ?>/contact.php">Liên hệ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="<?php echo BASE_URL; ?>/booking.php">Đặt bàn</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo BASE_URL . '/cart.php'; ?>" class="btn btn-premium-outline position-relative border-0" id="headerCartBtn" style="padding: 10px;">
                        <i class="bi bi-bag fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge <?php echo $cartCount > 0 ? '' : 'd-none'; ?>">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                    
                    <?php if ($isCustomerLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" style="background: transparent; border: 1px solid var(--color-coffee-light);">
                                <i class="bi bi-person-circle fs-5" style="color: var(--color-coffee-dark);"></i>
                                <span style="font-weight: 600; color: var(--color-coffee-dark);"><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Khách hàng'); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/profile.php"><i class="bi bi-person-badge me-2"></i>Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/orders.php"><i class="bi bi-receipt me-2"></i>Lịch sử đơn hàng</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger py-2" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/login.php?type=customer" class="btn btn-premium" style="padding: 8px 25px; font-size: 0.9rem;">Đăng nhập</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="page-wrapper">
    <main>
