<?php
$pageTitle = 'Chi tiết đơn hàng';
require_once dirname(__DIR__) . '/includes/customer_header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customerId = $_SESSION['customer_id'];

$order = fetchOne($pdo, "SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$orderId, $customerId]);

if (!$order) {
    setMessage('danger', 'Đơn hàng không tồn tại');
    header('Location: ' . BASE_URL . '/user/orders.php');
    exit;
}

$orderDetails = fetchAll($pdo, "
    SELECT od.*, p.name as product_name, p.code, p.unit, p.image
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
", [$orderId]);

$statusLabels = [
    'pending' => ['text' => 'Chờ xác nhận', 'class' => 'warning', 'icon' => 'clock'],
    'confirmed' => ['text' => 'Đã xác nhận', 'class' => 'info', 'icon' => 'check-circle'],
    'shipping' => ['text' => 'Đang giao hàng', 'class' => 'primary', 'icon' => 'truck'],
    'completed' => ['text' => 'Đã hoàn thành', 'class' => 'success', 'icon' => 'check2-circle'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'danger', 'icon' => 'x-circle']
];
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/orders.php">Đơn hàng</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_code']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-<?= $statusLabels[$order['status']]['class'] ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-<?= $statusLabels[$order['status']]['icon'] ?> fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1"><?= $statusLabels[$order['status']]['text'] ?></h4>
                            <p class="text-muted mb-0">Mã đơn hàng: <strong><?= htmlspecialchars($order['order_code']) ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-box-seam me-2"></i>Sản phẩm đã đặt
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-center">Đơn giá</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderDetails as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (isset($item['image']) && !empty($item['image'])): ?>
                                                    <img src="<?= htmlspecialchars($item['image']) ?>"
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                         class="rounded me-3"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="bi bi-cup-hot text-secondary fs-5"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-medium"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($item['code']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle"><?= formatCurrency($item['price']) ?></td>
                                        <td class="text-center align-middle"><?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                                        <td class="text-end align-middle fw-bold"><?= formatCurrency($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                    <td class="text-end text-success">Miễn phí</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end text-danger fs-5"><strong><?= formatCurrency($order['total_amount']) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Shipping Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-geo-alt me-2"></i>Thông tin giao hàng
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
                    <p class="mb-1"><?= htmlspecialchars($order['customer_phone']) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($order['customer_email']) ?></p>
                    <p class="mb-0 text-muted"><?= htmlspecialchars($order['shipping_address']) ?></p>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-wallet2 me-2"></i>Phương thức thanh toán
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-cash-coin text-success fs-4 me-3"></i>
                        <div>
                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                            <div class="small text-muted">Thanh toán bằng tiền mặt khi nhận hàng</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Lịch sử đơn hàng
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="d-flex mb-3">
                            <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                <i class="bi bi-cart-check small"></i>
                            </div>
                            <div>
                                <strong>Đặt hàng</strong>
                                <div class="small text-muted"><?= formatDate($order['created_at'], DATETIME_FORMAT) ?></div>
                            </div>
                        </div>
                        <?php if ($order['status'] !== 'pending'): ?>
                            <div class="d-flex mb-3">
                                <div class="bg-info rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <i class="bi bi-check small"></i>
                                </div>
                                <div>
                                    <strong>Xác nhận</strong>
                                    <div class="small text-muted">Đơn hàng đã được xác nhận</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array($order['status'], ['shipping', 'completed'])): ?>
                            <div class="d-flex mb-3">
                                <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <i class="bi bi-truck small"></i>
                                </div>
                                <div>
                                    <strong>Đang giao hàng</strong>
                                    <div class="small text-muted">Đơn hàng đang được vận chuyển</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'completed'): ?>
                            <div class="d-flex">
                                <div class="bg-success rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <i class="bi bi-check2-circle small"></i>
                                </div>
                                <div>
                                    <strong>Hoàn thành</strong>
                                    <div class="small text-muted">Đơn hàng đã được giao thành công</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'cancelled'): ?>
                            <div class="d-flex">
                                <div class="bg-danger rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <i class="bi bi-x small"></i>
                                </div>
                                <div>
                                    <strong>Đã hủy</strong>
                                    <div class="small text-muted">Đơn hàng đã bị hủy</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['note'])): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header">
                        <i class="bi bi-sticky me-2"></i>Ghi chú
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Quay lại danh sách đơn hàng
        </a>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/customer_footer.php'; ?>
