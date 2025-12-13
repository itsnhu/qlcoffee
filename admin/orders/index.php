<?php
$pageTitle = 'Quản lý đơn hàng Online';
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? '');

    if ($orderId && in_array($newStatus, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
        try {
            executeQuery($pdo, "UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
            setMessage('success', 'Cập nhật trạng thái đơn hàng thành công!');
        } catch (Exception $e) {
            setMessage('danger', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Filters
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

// Build query
$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR o.customer_email LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($dateFrom) {
    $where[] = 'DATE(o.created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = 'DATE(o.created_at) <= ?';
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

// Get orders
$orders = fetchAll($pdo, "
    SELECT o.*, c.email as account_email,
           (SELECT COUNT(*) FROM order_details WHERE order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE {$whereClause}
    ORDER BY o.created_at DESC
", $params);

// Get stats
$stats = [
    'pending' => fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'confirmed' => fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'confirmed'")['count'],
    'shipping' => fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'shipping'")['count'],
    'completed' => fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'],
    'cancelled' => fetchOne($pdo, "SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count'],
];

$statusLabels = [
    'pending' => ['Chờ xử lý', 'warning', 'clock'],
    'confirmed' => ['Đã xác nhận', 'info', 'check-circle'],
    'shipping' => ['Đang giao', 'primary', 'truck'],
    'completed' => ['Hoàn thành', 'success', 'check-circle-fill'],
    'cancelled' => ['Đã hủy', 'danger', 'x-circle'],
];

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">
            <i class="bi bi-bag-check-fill text-primary me-2"></i>Đơn hàng Online
        </h1>
        <p class="text-muted mb-0">Quản lý đơn hàng từ khách hàng đặt qua website</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <a href="?status=pending" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $status === 'pending' ? 'border-warning border-2' : '' ?>">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-1 text-warning"><?= $stats['pending'] ?></div>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i>Chờ xử lý</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="?status=confirmed" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $status === 'confirmed' ? 'border-info border-2' : '' ?>">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-1 text-info"><?= $stats['confirmed'] ?></div>
                    <small class="text-muted"><i class="bi bi-check-circle me-1"></i>Đã xác nhận</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="?status=shipping" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $status === 'shipping' ? 'border-primary border-2' : '' ?>">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-1 text-primary"><?= $stats['shipping'] ?></div>
                    <small class="text-muted"><i class="bi bi-truck me-1"></i>Đang giao</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="?status=completed" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $status === 'completed' ? 'border-success border-2' : '' ?>">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-1 text-success"><?= $stats['completed'] ?></div>
                    <small class="text-muted"><i class="bi bi-check-circle-fill me-1"></i>Hoàn thành</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md">
        <a href="?status=cancelled" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= $status === 'cancelled' ? 'border-danger border-2' : '' ?>">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-1 text-danger"><?= $stats['cancelled'] ?></div>
                    <small class="text-muted"><i class="bi bi-x-circle me-1"></i>Đã hủy</small>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Tìm mã đơn, tên, SĐT, email..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Trạng thái --</option>
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" placeholder="Từ ngày" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" placeholder="Đến ngày" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
            </div>
        </form>
        <?php if ($status || $search || $dateFrom || $dateTo): ?>
            <div class="mt-2">
                <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Xóa bộ lọc
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th class="text-center">SP</th>
                        <th class="text-end">Tổng tiền</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th class="text-center pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">Không có đơn hàng nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php $statusInfo = $statusLabels[$order['status']] ?? ['Unknown', 'secondary', 'question']; ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $order['id'] ?>" class="fw-bold text-decoration-none">
                                        <?= htmlspecialchars($order['order_code']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($order['shipping_address'], 0, 40, '...')) ?></small>
                                </td>
                                <td>
                                    <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($order['customer_phone']) ?></div>
                                    <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['customer_email']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark"><?= $order['item_count'] ?></span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    <?= formatCurrency($order['total_amount']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $statusInfo[1] ?>">
                                        <i class="bi bi-<?= $statusInfo[2] ?> me-1"></i><?= $statusInfo[0] ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Đổi trạng thái">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><h6 class="dropdown-header">Cập nhật trạng thái</h6></li>
                                            <?php foreach ($statusLabels as $key => $label): ?>
                                                <?php if ($key !== $order['status']): ?>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
