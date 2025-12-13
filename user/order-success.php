<?php
$pageTitle = 'Đặt hàng thành công';
require_once 'includes/header.php';

$orderCode = sanitize($_GET['code'] ?? '');

if (!$orderCode || !isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . '/user/');
    exit;
}

$order = fetchOne($pdo, "SELECT * FROM orders WHERE order_code = ? AND customer_id = ?", [$orderCode, $_SESSION['customer_id']]);

if (!$order) {
    header('Location: ' . BASE_URL . '/user/');
    exit;
}

$orderDetails = fetchAll($pdo, "
    SELECT od.*, m.name as medicine_name, m.unit
    FROM order_details od
    JOIN medicines m ON od.medicine_id = m.id
    WHERE od.order_id = ?
", [$order['id']]);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                    <i class="bi bi-check-lg" style="font-size: 3rem;"></i>
                </div>
                <h2 class="text-success">Đặt hàng thành công!</h2>
                <p class="lead text-muted">Cảm ơn bạn đã mua hàng tại PharmaManager</p>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-4">
                    <p class="mb-1">Mã đơn hàng của bạn</p>
                    <h3 class="text-primary mb-0"><?= htmlspecialchars($order['order_code']) ?></h3>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Thông tin đơn hàng
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Người nhận:</strong><br>
                            <?= htmlspecialchars($order['customer_name']) ?><br>
                            <?= htmlspecialchars($order['customer_phone']) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Địa chỉ giao hàng:</strong><br>
                            <?= htmlspecialchars($order['shipping_address']) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Phương thức thanh toán:</strong><br>
                            Thanh toán khi nhận hàng (COD)
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Trạng thái:</strong><br>
                            <span class="badge bg-warning">Chờ xác nhận</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-box-seam me-2"></i>Chi tiết đơn hàng
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">SL</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderDetails as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                                    <td class="text-center"><?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end"><?= formatCurrency($item['price']) ?></td>
                                    <td class="text-end"><?= formatCurrency($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Tổng cộng:</th>
                                <th class="text-end text-danger"><?= formatCurrency($order['total_amount']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-envelope me-2"></i>
                Email xác nhận đơn hàng đã được gửi đến <strong><?= htmlspecialchars($order['customer_email']) ?></strong>
            </div>

            <div class="text-center">
                <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-primary me-2">
                    <i class="bi bi-box-seam me-2"></i>Xem đơn hàng
                </a>
                <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
