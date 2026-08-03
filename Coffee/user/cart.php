<?php
ob_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/cart.php';
    setMessage('warning', 'Vui lòng đăng nhập để xem giỏ hàng');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

// Get cart items from database
$cartItems = fetchAll($pdo, "
    SELECT c.quantity as cart_qty, m.id, m.name, m.price, m.image, m.quantity as stock, m.unit
    FROM cart c
    JOIN products m ON c.product_id = m.id
    WHERE c.customer_id = ?
", [$customerId]);

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['cart_qty']), 0);

// Get order stats
$orderStats = fetchOne($pdo, "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_spent,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'served' OR status = 'paid' THEN 1 ELSE 0 END) as completed_orders
    FROM orders WHERE customer_id = ?
", [$customerId]);

$pageTitle = 'Giỏ hàng của tôi';
require_once dirname(__DIR__) . '/includes/customer_header.php';
?>

<style>
    :root {
        --profile-primary: #6F4E37;
        --profile-secondary: #A67B5B;
        --profile-accent: #ECB176;
        --profile-bg: #FDF7E4;
        --profile-glass: rgba(255, 255, 255, 0.9);
    }

    .profile-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: calc(100vh - 100px);
        padding: 40px 0;
    }

    .glass-card {
        background: var(--profile-glass);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .profile-sidebar {
        height: 100%;
    }

    .user-avatar-wrapper {
        position: relative;
        display: inline-block;
        padding: 5px;
        background: linear-gradient(45deg, var(--profile-primary), var(--profile-accent));
        border-radius: 50%;
        margin-bottom: 20px;
    }

    .user-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: var(--profile-primary);
        border: 4px solid white;
    }

    .nav-pills-custom .nav-link {
        color: #555;
        border-radius: 12px;
        padding: 12px 20px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        font-weight: 500;
        border-left: 4px solid transparent;
    }

    .nav-pills-custom .nav-link i {
        font-size: 1.2rem;
        margin-right: 12px;
        width: 24px;
        text-align: center;
    }

    .nav-pills-custom .nav-link:hover {
        background: rgba(111, 78, 55, 0.05);
        color: var(--profile-primary);
    }

    .nav-pills-custom .nav-link.active {
        background: var(--profile-primary);
        color: white;
        box-shadow: 0 4px 12px rgba(111, 78, 55, 0.2);
    }

    .stat-badge {
        padding: 20px;
        border-radius: 20px;
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--profile-primary);
        display: block;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .badge-points {
        background: linear-gradient(45deg, #ffd700, #ffa500);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 25px;
        font-weight: 700;
        color: var(--profile-primary);
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 4px;
        background: var(--profile-accent);
        border-radius: 2px;
    }

    .cart-item {
        padding: 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        background: rgba(111, 78, 55, 0.02);
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .quantity-control {
        display: inline-flex;
        align-items: center;
        background: white;
        border: 2px solid #eee;
        border-radius: 25px;
        overflow: hidden;
    }

    .quantity-control button {
        background: transparent;
        border: none;
        width: 35px;
        height: 35px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--profile-primary);
    }

    .quantity-control button:hover {
        background: var(--profile-primary);
        color: white;
    }

    .quantity-control input {
        border: none;
        width: 50px;
        text-align: center;
        font-weight: 600;
        background: transparent;
    }

    .cart-summary {
        position: sticky;
        top: 100px;
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .btn-checkout {
        background: linear-gradient(45deg, var(--profile-primary), var(--profile-secondary));
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 28px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(111, 78, 55, 0.3);
        color: white;
    }

    .empty-cart {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-cart i {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 20px;
    }
</style>

<div class="profile-container">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-lg-4">
                <div class="glass-card profile-sidebar p-4">
                    <div class="text-center mb-4">
                        <div class="user-avatar-wrapper">
                            <div class="user-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <h4 class="mb-1"><?= htmlspecialchars($customer['full_name']) ?></h4>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($customer['email']) ?></p>
                        <span class="badge-points"><i class="bi bi-star-fill me-1"></i> <?= $customer['points'] ?? 0 ?> điểm tích lũy</span>
                    </div>

                    <div class="nav flex-column nav-pills nav-pills-custom" role="tablist">
                        <a href="<?= BASE_URL ?>/user/profile.php" class="nav-link">
                            <i class="bi bi-person-badge"></i> Thông tin cá nhân
                        </a>
                        <a href="<?= BASE_URL ?>/user/orders.php" class="nav-link">
                            <i class="bi bi-receipt"></i> Lịch sử đơn hàng
                        </a>
                        <a href="<?= BASE_URL ?>/user/cart.php" class="nav-link active">
                            <i class="bi bi-cart3"></i> Giỏ hàng của tôi
                        </a>
                        <hr class="text-muted">
                        <a href="<?= BASE_URL ?>/user/logout.php" class="nav-link text-danger">
                            <i class="bi bi-box-arrow-right"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-8">
                <!-- Stats Overview -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-badge shadow-sm">
                            <span class="stat-value"><?= $orderStats['total_orders'] ?></span>
                            <span class="stat-label">Tổng đơn</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-badge shadow-sm">
                            <span class="stat-value"><?= $orderStats['completed_orders'] ?></span>
                            <span class="stat-label">Hoàn thành</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-badge shadow-sm">
                            <span class="stat-value text-warning"><?= $orderStats['pending_orders'] ?></span>
                            <span class="stat-label">Đang đợi</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-badge shadow-sm">
                            <span class="stat-value text-success"><?= number_format($orderStats['total_spent'], 0, ',', '.') ?>đ</span>
                            <span class="stat-label">Đã chi</span>
                        </div>
                    </div>
                </div>

                <!-- Cart Content -->
                <div class="glass-card">
                    <div class="p-4">
                        <h4 class="section-title"><i class="bi bi-cart3 me-2"></i>Giỏ hàng của tôi</h4>
                        
                        <?php if (empty($cartItems)): ?>
                            <div class="empty-cart">
                                <i class="bi bi-cart-x"></i>
                                <h5 class="mb-3">Giỏ hàng trống</h5>
                                <p class="text-muted mb-4">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                                <a href="<?= BASE_URL ?>/menu.php" class="btn btn-checkout" style="max-width: 300px; margin: 0 auto;">
                                    <i class="bi bi-cup-hot me-2"></i>Khám phá thực đơn
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-lg-7">
                                    <?php foreach ($cartItems as $item): ?>
                                        <div class="cart-item" id="cart-item-<?= $item['id'] ?>">
                                            <div class="d-flex gap-3">
                                                <img src="<?= !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/80?text=Coffee' ?>" 
                                                     class="cart-item-image" alt="<?= htmlspecialchars($item['name']) ?>">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($item['name']) ?></h6>
                                                    <p class="text-muted small mb-2"><?= htmlspecialchars($item['unit'] ?? '') ?></p>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="quantity-control">
                                                            <button onclick="updateCart(<?= $item['id'] ?>, -1)">
                                                                <i class="bi bi-dash"></i>
                                                            </button>
                                                            <input type="number" id="qty-<?= $item['id'] ?>" value="<?= $item['cart_qty'] ?>" readonly>
                                                            <button onclick="updateCart(<?= $item['id'] ?>, 1)">
                                                                <i class="bi bi-plus"></i>
                                                            </button>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold" style="color: var(--profile-primary);" id="subtotal-<?= $item['id'] ?>">
                                                                <?= number_format($item['price'] * $item['cart_qty'], 0, ',', '.') ?>đ
                                                            </div>
                                                            <small class="text-muted"><?= number_format($item['price'], 0, ',', '.') ?>đ / món</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button onclick="removeFromCart(<?= $item['id'] ?>)" class="btn btn-link text-danger p-0" style="height: fit-content;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="col-lg-5">
                                    <div class="cart-summary">
                                        <h5 class="fw-bold mb-4">Tổng quan đơn hàng</h5>
                                        
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Tạm tính</span>
                                            <span class="fw-bold" id="cart-subtotal"><?= number_format($cartTotal, 0, ',', '.') ?>đ</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Phí vận chuyển</span>
                                            <span class="text-success fw-bold">Miễn phí</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Giảm giá</span>
                                            <span class="text-success fw-bold">0đ</span>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="d-flex justify-content-between mb-4">
                                            <span class="h5 fw-bold mb-0">Tổng cộng</span>
                                            <span class="h4 fw-bold mb-0" style="color: var(--profile-accent);" id="cart-total">
                                                <?= number_format($cartTotal, 0, ',', '.') ?>đ
                                            </span>
                                        </div>

                                        <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-checkout mb-3">
                                            <i class="bi bi-credit-card me-2"></i>Thanh toán ngay
                                        </a>
                                        
                                        <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline-secondary w-100" style="border-radius: 12px;">
                                            <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateCart(productId, delta) {
    const qtyInput = document.getElementById('qty-' + productId);
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + delta;
    
    if (newQty < 1) return;

    fetch('<?= BASE_URL ?>/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: newQty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Lỗi cập nhật');
        }
    });
}

function removeFromCart(productId) {
    if(!confirm('Xóa món này khỏi giỏ hàng?')) return;

    fetch('<?= BASE_URL ?>/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/customer_footer.php'; ?>
