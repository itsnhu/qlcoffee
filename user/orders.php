<?php
$pageTitle = 'Đơn hàng của tôi';
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/orders.php';
    setMessage('warning', 'Vui lòng đăng nhập để xem đơn hàng');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Get orders
$orders = fetchAll($pdo, "
    SELECT o.*, COUNT(od.id) as item_count
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
", [$customerId]);

$statusLabels = [
    'pending' => ['text' => 'Chờ xác nhận', 'class' => 'warning'],
    'confirmed' => ['text' => 'Đã xác nhận', 'class' => 'info'],
    'shipping' => ['text' => 'Đang giao hàng', 'class' => 'primary'],
    'completed' => ['text' => 'Đã hoàn thành', 'class' => 'success'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'danger']
];
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-box-seam me-2"></i>Đơn hàng của tôi</h2>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 5rem;"></i>
            <h4 class="mt-3">Bạn chưa có đơn hàng nào</h4>
            <p class="text-muted">Hãy mua sắm để tạo đơn hàng đầu tiên</p>
            <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-primary">
                <i class="bi bi-cart me-2"></i>Mua sắm ngay
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-primary"><?= htmlspecialchars($order['order_code']) ?></strong>
                                <span class="text-muted ms-3">
                                    <i class="bi bi-calendar me-1"></i><?= formatDate($order['created_at'], DATETIME_FORMAT) ?>
                                </span>
                            </div>
                            <span class="badge bg-<?= $statusLabels[$order['status']]['class'] ?>">
                                <?= $statusLabels[$order['status']]['text'] ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="small text-muted mb-1">Người nhận</div>
                                    <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="small text-muted mb-1">Địa chỉ giao hàng</div>
                                    <div class="small"><?= htmlspecialchars($order['shipping_address']) ?></div>
                                </div>
                                <div class="col-md-2 mb-3 mb-md-0 text-center">
                                    <div class="small text-muted mb-1"><?= $order['item_count'] ?> sản phẩm</div>
                                    <div class="h5 text-danger mb-0"><?= formatCurrency($order['total_amount']) ?></div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="<?= BASE_URL ?>/user/order-detail.php?id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
