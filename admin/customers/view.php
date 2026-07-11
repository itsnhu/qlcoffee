<?php

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    setMessage('danger', 'ID khách hàng không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

// Get customer details
try {
    $customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$id]);

    if (!$customer) {
        setMessage('danger', 'Không tìm thấy khách hàng.');
        header('Location: ' . BASE_URL . '/admin/customers/index.php');
        exit;
    }

    // Get customer orders
    $orders = fetchAll($pdo, "
        SELECT o.*, COUNT(od.id) as total_items
        FROM orders o
        LEFT JOIN order_details od ON o.id = od.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ", [$id]);

    // Get statistics
    $stats = fetchOne($pdo, "
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_spent,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
        FROM orders
        WHERE customer_id = ?
    ", [$id]);

} catch (PDOException $e) {
    error_log("Load Customer Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin khách hàng.');
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

$pageTitle = 'Chi tiết khách hàng: ' . $customer['full_name'];
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-person-badge text-primary"></i>
                    Chi tiết khách hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Thông tin chi tiết và lịch sử đơn hàng của khách hàng
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning me-2">
                    <i class="bi bi-pencil-square me-2"></i>
                    Chỉnh sửa
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/customers/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Customer Info -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-circle me-2"></i>
                <strong>Thông tin khách hàng</strong>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                         style="width: 100px; height: 100px;">
                        <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mt-3 mb-1"><?php echo htmlspecialchars($customer['full_name']); ?></h5>
                    <span class="badge bg-<?php echo $customer['is_active'] ? 'success' : 'secondary'; ?>">
                        <?php echo $customer['is_active'] ? 'Hoạt động' : 'Vô hiệu hóa'; ?>
                    </span>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope me-2 text-muted"></i>Email</span>
                        <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-telephone me-2 text-muted"></i>Điện thoại</span>
                        <?php if ($customer['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($customer['phone']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Chưa cập nhật</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item">
                        <span><i class="bi bi-geo-alt me-2 text-muted"></i>Địa chỉ</span>
                        <p class="mb-0 mt-2 text-muted small">
                            <?php echo $customer['address'] ? htmlspecialchars($customer['address']) : 'Chưa cập nhật'; ?>
                        </p>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar me-2 text-muted"></i>Ngày đăng ký</span>
                        <span><?php echo formatDate($customer['created_at'], DATETIME_FORMAT); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-bar-chart me-2"></i>
                <strong>Thống kê</strong>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h4 class="mb-1 text-primary"><?php echo $stats['total_orders']; ?></h4>
                        <small class="text-muted">Tổng đơn hàng</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="mb-1 text-success"><?php echo $stats['completed_orders']; ?></h4>
                        <small class="text-muted">Hoàn thành</small>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-1 text-danger"><?php echo $stats['cancelled_orders']; ?></h4>
                        <small class="text-muted">Đã hủy</small>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-1 text-info"><?php echo formatCurrency($stats['total_spent']); ?></h4>
                        <small class="text-muted">Tổng chi tiêu</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order History -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <i class="bi bi-bag-check me-2"></i>
                <strong>Lịch sử đơn hàng</strong>
                <span class="badge bg-light text-dark ms-2"><?php echo count($orders); ?> đơn</span>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">Khách hàng chưa có đơn hàng nào</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th class="text-center">Sản phẩm</th>
                                    <th class="text-end">Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_code']); ?></strong></td>
                                        <td>
                                            <small><?php echo formatDate($order['created_at'], DATETIME_FORMAT); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $order['total_items']; ?> SP</span>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match($order['status']) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'shipping' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            $statusText = match($order['status']) {
                                                'pending' => 'Chờ xử lý',
                                                'confirmed' => 'Đã xác nhận',
                                                'shipping' => 'Đang giao',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Đã hủy',
                                                default => 'Không xác định'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo BASE_URL; ?>/admin/orders/view.php?id=<?php echo $order['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
