<?php
ob_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/index.php';
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

// Get order stats using inclusive definitions
$orderStats = fetchOne($pdo, "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'paid', 'served') THEN total_amount ELSE 0 END), 0) as total_spent,
        COALESCE(SUM(CASE WHEN status IN ('pending', 'preparing', 'confirmed', 'shipping') THEN 1 ELSE 0 END), 0) as pending_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'paid', 'served') THEN 1 ELSE 0 END), 0) as completed_orders
    FROM orders WHERE customer_id = ?
", [$customerId]);

$pageTitle = 'Bảng điều khiển khách hàng';
require_once dirname(__DIR__) . '/includes/customer_header.php';
?>

<style>
    :root {
        --dash-primary: #6F4E37;
        --dash-bg: #f8fafc;
        --dash-accent: #ECB176;
    }
    body { background-color: #f1f3f6; }
    .dash-container { padding: 40px 0; }
    .stat-card-gold {
        background: white;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #f0f0f0;
    }
    .stat-card-gold:hover { transform: translateY(-5px); border-color: var(--dash-accent); }
    .stat-icon { font-size: 2.5rem; margin-bottom: 15px; color: var(--dash-accent); }
    .stat-val { font-size: 2.2rem; font-weight: 800; color: var(--dash-primary); display: block; margin-bottom: 5px; }
    .stat-label { font-size: 0.9rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    
    .sidebar-card { 
        background: white; 
        border-radius: 24px; 
        padding: 32px 20px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.04); 
        border: 1px solid rgba(0,0,0,0.03);
    }
    .profile-nav .nav-link { 
        color: #555; 
        font-weight: 600; 
        padding: 13px 18px; 
        border-radius: 14px;
        display: flex; 
        align-items: center; 
        gap: 14px; 
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 4px;
    }
    .profile-nav .nav-link i { font-size: 1.1rem; color: #999; transition: all 0.3s ease; }
    .profile-nav .nav-link:hover { background: #f8f9fa; color: #6F4E37; }
    .profile-nav .nav-link:hover i { color: #6F4E37; }
    .profile-nav .nav-link.active { 
        background: #6F4E37; 
        color: white; 
        box-shadow: 0 4px 15px rgba(111,78,55,0.25); 
    }
    .profile-nav .nav-link.active i { color: white; }
    .avatar-circle {
        width: 95px; height: 95px;
        border: 4px solid #ECB176;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #fdf8f3;
        margin: 0 auto 15px;
        box-shadow: 0 5px 15px rgba(236,177,118,0.2);
    }
    .avatar-circle i { font-size: 2.8rem; color: #6F4E37; opacity: .7; }
</style>

<div class="dash-container">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="sidebar-card">
                    <div class="text-center mb-4">
                        <div style="width:80px; height:80px; background:#f8f9fa; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-person-fill" style="font-size:2.5rem; color:#ddd;"></i>
                        </div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($customer['full_name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($customer['email']) ?></p>
                    </div>
                    <nav class="profile-nav">
                        <a href="<?= BASE_URL ?>/user/index.php" class="nav-link active">
                            <i class="bi bi-grid-fill"></i><span>Bảng điều khiển</span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="nav-link">
                            <i class="bi bi-person-badge-fill"></i><span>Thông tin cá nhân</span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php#password" class="nav-link">
                            <i class="bi bi-shield-lock"></i><span>Bảo mật và mật khẩu</span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/orders.php" class="nav-link">
                            <i class="bi bi-receipt"></i><span>Lịch sử đơn hàng</span>
                        </a>
                        <a href="<?= BASE_URL ?>/cart.php" class="nav-link">
                            <i class="bi bi-cart3"></i><span>Giỏ hàng của tôi</span>
                        </a>
                        <hr class="my-3 opacity-10">
                        <a href="<?= BASE_URL ?>/user/logout.php" class="nav-link text-danger mt-2">
                            <i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span>
                        </a>
                    </nav>
                </div>
            </div>
            <div class="col-lg-9">
                <h3 class="fw-bold mb-4" style="color: var(--dash-primary);">Chào sớm, <?= explode(' ', $customer['full_name'])[0] ?>!</h3>
                <div class="row g-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="stat-card-gold">
                            <i class="bi bi-bag-check stat-icon"></i>
                            <span class="stat-val"><?= $orderStats['total_orders'] ?></span>
                            <span class="stat-label">Tổng đơn</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="stat-card-gold">
                            <i class="bi bi-check2-circle stat-icon text-success"></i>
                            <span class="stat-val"><?= $orderStats['completed_orders'] ?></span>
                            <span class="stat-label">Hoàn thành</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="stat-card-gold">
                            <i class="bi bi-clock-history stat-icon text-warning"></i>
                            <span class="stat-val"><?= $orderStats['pending_orders'] ?></span>
                            <span class="stat-label">Đang đợi</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="stat-card-gold">
                            <i class="bi bi-currency-dollar stat-icon text-primary"></i>
                            <span class="stat-val"><?= number_format($orderStats['total_spent'], 0, ',', '.') ?>đ</span>
                            <span class="stat-label">Đã chi</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 p-5 text-center bg-white rounded-4 shadow-sm">
                    <i class="bi bi-cup-hot fs-1 text-coffee mb-3 opacity-25"></i>
                    <h4 class="fw-bold">Hôm nay bạn muốn uống gì?</h4>
                    <p class="text-muted">Đặt hàng ngay để tích điểm và nhận ưu đãi!</p>
                    <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary rounded-pill px-5 py-2 mt-2">Đến thực đơn</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/customer_footer.php'; ?>
