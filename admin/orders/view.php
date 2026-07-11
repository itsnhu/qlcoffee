<?php
$pageTitle = 'Chi tiết đơn hàng';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    setMessage('danger', 'Đơn hàng không tồn tại');
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $newStatus = sanitize($_POST['status'] ?? '');
        if (in_array($newStatus, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
            try {
                executeQuery($pdo, "UPDATE orders SET status = ?, user_id = ? WHERE id = ?", [$newStatus, $_SESSION['user_id'], $orderId]);
                setMessage('success', 'Cập nhật trạng thái thành công!');
            } catch (Exception $e) {
                setMessage('danger', 'Có lỗi xảy ra: ' . $e->getMessage());
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/orders/view.php?id=' . $orderId);
    exit;
}

// Get order
$order = fetchOne($pdo, "
    SELECT o.*, c.full_name as account_name, c.email as account_email, c.phone as account_phone
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    setMessage('danger', 'Đơn hàng không tồn tại');
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Get order details
$orderDetails = fetchAll($pdo, "
    SELECT od.*, p.name as product_name, p.code as product_code, p.unit
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
", [$orderId]);

$statusLabels = [
    'pending' => ['Chờ xử lý', 'warning', 'clock'],
    'confirmed' => ['Đã xác nhận', 'info', 'check-circle'],
    'shipping' => ['Đang giao hàng', 'primary', 'truck'],
    'completed' => ['Hoàn thành', 'success', 'check-circle-fill'],
    'cancelled' => ['Đã hủy', 'danger', 'x-circle'],
];

$statusInfo = $statusLabels[$order['status']] ?? ['Unknown', 'secondary', 'question'];

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/orders/index.php">Đơn hàng Online</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_code']) ?></li>
            </ol>
        </nav>
        <h1 class="page-title mb-0">
            <i class="bi bi-receipt text-primary me-2"></i>Đơn hàng #<?= htmlspecialchars($order['order_code']) ?>
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>In đơn
        </button>
    </div>
</div>

<div class="row">
    <!-- Order Info -->
    <div class="col-lg-8">
        <!-- Status Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Trạng thái đơn hàng</h5>
                        <span class="badge bg-<?= $statusInfo[1] ?> fs-6">
                            <i class="bi bi-<?= $statusInfo[2] ?> me-1"></i><?= $statusInfo[0] ?>
                        </span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-pencil me-1"></i>Cập nhật trạng thái
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($statusLabels as $key => $label): ?>
                                <?php if ($key !== $order['status']): ?>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="status" value="<?= $key ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-<?= $label[2] ?> me-2 text-<?= $label[1] ?>"></i><?= $label[0] ?>
                                            </button>
                                        </form>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between position-relative">
                        <div class="position-absolute" style="top: 15px; left: 10%; right: 10%; height: 3px; background: var(--bs-gray-200); z-index: 0;"></div>
                        <?php
                        $steps = ['pending', 'confirmed', 'shipping', 'completed'];
                        $currentIndex = array_search($order['status'], $steps);
                        if ($order['status'] === 'cancelled') $currentIndex = -1;
                        ?>
                        <?php foreach ($steps as $index => $step): ?>
                            <?php
                            $stepInfo = $statusLabels[$step];
                            $isActive = $index <= $currentIndex;
                            $isCurrent = $index === $currentIndex;
                            ?>
                            <div class="text-center position-relative" style="z-index: 1; flex: 1;">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2 <?= $isActive ? 'bg-' . $stepInfo[1] . ' text-white' : 'bg-light text-muted' ?>" style="width: 35px; height: 35px;">
                                    <i class="bi bi-<?= $stepInfo[2] ?>"></i>
                                </div>
                                <div class="small <?= $isCurrent ? 'fw-bold' : '' ?>"><?= $stepInfo[0] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Sản phẩm (<?= count($orderDetails) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Sản phẩm</th>
                                <th class="text-center">Đơn giá</th>
                                <th class="text-center">SL</th>
                                <th class="text-end pe-4">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderDetails as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="bi bi-cup-hot text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <small class="text-muted">Mã: <?= htmlspecialchars($item['product_code']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= formatCurrency($item['price']) ?></td>
                                    <td class="text-center"><?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end pe-4 fw-bold"><?= formatCurrency($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="text-end ps-4"><strong>Tổng tiền hàng:</strong></td>
                                <td class="text-end pe-4"><strong><?= formatCurrency($order['total_amount']) ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end ps-4">Phí vận chuyển:</td>
                                <td class="text-end pe-4 text-success">Miễn phí</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end ps-4"><strong class="fs-5">Tổng thanh toán:</strong></td>
                                <td class="text-end pe-4"><strong class="fs-5 text-danger"><?= formatCurrency($order['total_amount']) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Note -->
        <?php if ($order['note']): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Ghi chú từ khách hàng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($order['note'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Customer & Shipping Info -->
    <div class="col-lg-4">
        <!-- Customer Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Thông tin khách hàng</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="small text-muted">Họ tên:</label>
                    <div class="fw-medium"><?= htmlspecialchars($order['customer_name']) ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted">Số điện thoại:</label>
                    <div>
                        <a href="tel:<?= $order['customer_phone'] ?>" class="text-decoration-none">
                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_phone']) ?>
                        </a>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="small text-muted">Email:</label>
                    <div>
                        <a href="mailto:<?= $order['customer_email'] ?>" class="text-decoration-none">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['customer_email']) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Địa chỉ giao hàng</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                        <i class="bi bi-cash-coin text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-medium">Thanh toán khi nhận hàng (COD)</div>
                        <small class="text-muted">Thu <?= formatCurrency($order['total_amount']) ?> khi giao</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Meta -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Mã đơn:</span>
                    <strong><?= htmlspecialchars($order['order_code']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Ngày đặt:</span>
                    <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Cập nhật:</span>
                    <span><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Tài khoản:</span>
                    <span><?= htmlspecialchars($order['account_email'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .dropdown, nav, .navbar, .breadcrumb { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    .col-lg-8, .col-lg-4 { width: 100% !important; max-width: 100% !important; }
}
</style>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
