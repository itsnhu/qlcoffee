<?php
ob_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/orders.php';
    setMessage('warning', 'Vui lòng đăng nhập');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

// Get orders
$orders = fetchAll($pdo, "
    SELECT o.*, COUNT(od.id) as item_count
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
", [$customerId]);

// Stats logic
$orderStats = fetchOne($pdo, "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'paid', 'served') THEN total_amount ELSE 0 END), 0) as total_spent,
        COALESCE(SUM(CASE WHEN status IN ('pending', 'preparing', 'confirmed', 'shipping') THEN 1 ELSE 0 END), 0) as pending_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'paid', 'served') THEN 1 ELSE 0 END), 0) as completed_orders
    FROM orders WHERE customer_id = ?
", [$customerId]);

$statusLabels = [
    'pending' => ['text' => 'Chờ xác nhận', 'class' => 'warning', 'icon' => 'clock'],
    'confirmed' => ['text' => 'Đã xác nhận', 'class' => 'info', 'icon' => 'check-circle'],
    'shipping' => ['text' => 'Đang giao hàng', 'class' => 'primary', 'icon' => 'truck'],
    'completed' => ['text' => 'Đã hoàn thành', 'class' => 'success', 'icon' => 'check-circle-fill'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'danger', 'icon' => 'x-circle']
];

$pageTitle = 'Lịch sử đơn hàng';
require_once dirname(__DIR__) . '/includes/customer_header.php';
?>

<style>
    :root { --profile-primary: #6F4E37; --accent-gold: #ECB176; }
    body { background-color: #f1f3f6; font-family: 'Inter', sans-serif; }
    .profile-container { padding: 40px 0; }
    
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
    .points-badge { 
        background: #FFB300; 
        color: #fff; 
        font-size: 0.75rem; 
        font-weight: 700; 
        padding: 6px 16px; 
        border-radius: 100px; 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        box-shadow: 0 4px 10px rgba(255,179,0,0.3); 
    }

    .stat-bar-horizontal { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
    .stat-item-card { background: white; border-radius: 15px; padding: 20px 10px; text-align: center; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .stat-label-mini { font-size: 0.7rem; font-weight: 700; color: #aaa; text-transform: uppercase; display: block; margin-bottom: 8px; }
    .stat-value-mini { font-size: 1.3rem; font-weight: 800; }
    
    .text-coffee { color: var(--profile-primary); } .text-completed { color: #2ecc71; } .text-pending { color: #f1c40f; } .text-spent { color: #3498db; }

    .main-white-card { background: white; border-radius: 25px; padding: 45px; box-shadow: 0 4px 25px rgba(0,0,0,0.03); min-height: 400px; }
    .card-title-custom { font-weight: 700; color: #2d3436; font-size: 1.4rem; margin-bottom: 35px; }
    
    .order-item-box { border: 1px solid #f0f0f0; border-radius: 15px; padding: 20px; margin-bottom: 15px; }
</style>

<div class="profile-container">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="sidebar-card">
                    <div class="text-center mb-4">
                        <div class="avatar-circle"><i class="bi bi-person-fill fs-2 text-muted opacity-25"></i></div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($customer['full_name']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($customer['email']) ?></p>
                        <div class="points-badge"><i class="bi bi-star-fill"></i><span><?= $customer['points'] ?? 0 ?> điểm</span></div>
                    </div>
                    <nav class="profile-nav">
                        <a href="<?= BASE_URL ?>/user/profile.php" class="nav-link">
                            <i class="bi bi-person-badge-fill"></i><span>Thông tin cá nhân</span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php#password" class="nav-link">
                            <i class="bi bi-shield-lock"></i><span>Bảo mật và mật khẩu</span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/orders.php" class="nav-link active">
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
                <div class="stat-bar-horizontal">
                    <div class="stat-item-card"><span class="stat-label-mini">Tổng đơn</span><span class="stat-value-mini text-coffee"><?= $orderStats['total_orders'] ?></span></div>
                    <div class="stat-item-card"><span class="stat-label-mini">Hoàn thành</span><span class="stat-value-mini text-completed"><?= $orderStats['completed_orders'] ?></span></div>
                    <div class="stat-item-card"><span class="stat-label-mini">Đang đợi</span><span class="stat-value-mini text-pending"><?= $orderStats['pending_orders'] ?></span></div>
                    <div class="stat-item-card"><span class="stat-label-mini">Đã chi</span><span class="stat-value-mini text-spent"><?= number_format($orderStats['total_spent'], 0, ',', '.') ?>đ</span></div>
                </div>
                <div class="main-white-card"><div class="card-title-custom">Lịch sử đơn hàng</div>
                <?php if (empty($orders)): ?><p class="text-center py-5 text-muted">Chưa có đơn hàng nào.</p><?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-item-box">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><div class="fw-bold fs-5">#<?= htmlspecialchars($order['order_code']) ?></div><div class="text-muted small"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div></div>
                                <div class="text-end"><div class="fw-bold fs-4 text-dark"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</div><span class="badge bg-<?= $statusLabels[$order['status']]['class'] ?> rounded-pill px-3"><?= $statusLabels[$order['status']]['text'] ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/customer_footer.php'; ?>
