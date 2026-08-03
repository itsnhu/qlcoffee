<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireEmployee();
redirect('/employee/tables/index.php');
exit;

try {
    $totalProducts = fetchOne($pdo, "SELECT COUNT(*) as total FROM products")['total'] ?? 0;

    $myTodayInvoices = fetchOne($pdo, "
        SELECT COUNT(*) as total
        FROM orders
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myTodayRevenue = fetchOne($pdo, "
        SELECT SUM(total_amount) as total
        FROM orders
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myMonthInvoices = fetchOne($pdo, "
        SELECT COUNT(*) as total
        FROM orders
        WHERE user_id = ?
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myMonthRevenue = fetchOne($pdo, "
        SELECT SUM(total_amount) as total
        FROM orders
        WHERE user_id = ?
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $lowStockCount = fetchOne($pdo, "SELECT COUNT(*) as total FROM products WHERE quantity < 10")['total'] ?? 0;

    $myRecentInvoices = fetchAll($pdo, "
        SELECT *
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ", [$_SESSION['user_id']]);

    $myTopProducts = fetchAll($pdo, "
        SELECT m.name, SUM(id.quantity) as total_sold
        FROM order_details id
        JOIN products m ON id.product_id = m.id
        JOIN orders i ON id.order_id = i.id
        WHERE i.user_id = ?
        AND MONTH(i.created_at) = MONTH(CURDATE())
        AND YEAR(i.created_at) = YEAR(CURDATE())
        GROUP BY m.id, m.name
        ORDER BY total_sold DESC
        LIMIT 5
    ", [$_SESSION['user_id']]);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalProducts = $myTodayInvoices = $myTodayRevenue = $myMonthInvoices = $myMonthRevenue = $lowStockCount = 0;
    $myRecentInvoices = $myTopProducts = [];
}

$pageTitle = 'Dashboard Nhân viên';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Welcome & Stats Section -->
<div class="row g-4 mb-4">
    <!-- Orders Today -->
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-primary">
                    <i class="bi bi-cart text-primary"></i>
                </div>
                <div class="dash-card-trend text-success">
                    <i class="bi bi-clock"></i> Hôm nay
                </div>
            </div>
            <div class="dash-card-title">Đơn hàng hôm nay</div>
            <div class="dash-card-value"><?= number_format($myTodayInvoices) ?></div>
            <div class="small text-muted">Tổng đơn của bạn</div>
        </div>
    </div>

    <!-- Revenue Today -->
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-success">
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
                <div class="dash-card-trend text-success">
                    <i class="bi bi-graph-up"></i> Doanh số
                </div>
            </div>
            <div class="dash-card-title">Doanh thu hôm nay</div>
            <div class="dash-card-value"><?= formatCurrency($myTodayRevenue) ?></div>
            <div class="small text-muted">Thực nhận giao dịch</div>
        </div>
    </div>

    <!-- Month Orders -->
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-info">
                    <i class="bi bi-calendar-event text-info"></i>
                </div>
                <div class="dash-card-trend text-info">
                    <i class="bi bi-person"></i> Tháng này
                </div>
            </div>
            <div class="dash-card-title">Đơn hàng trong tháng</div>
            <div class="dash-card-value"><?= number_format($myMonthInvoices) ?></div>
            <div class="small text-muted">Hiệu suất cá nhân</div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-warning">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                </div>
                <div class="dash-card-trend text-danger">
                    <i class="bi bi-warning"></i> Cảnh báo
                </div>
            </div>
            <div class="dash-card-title">Món sắp hết</div>
            <div class="dash-card-value"><?= number_format($lowStockCount) ?></div>
            <div class="small text-muted">Số lượng dưới 10</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent My Orders -->
    <div class="col-xl-7">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Đơn hàng gần nhất của bạn</h5>
                <a href="<?= BASE_URL ?>/admin/sales/index.php" class="small text-primary text-decoration-none">Xem thêm</a>
            </div>
            
            <?php if (empty($myRecentInvoices)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Bạn chưa có đơn hàng nào hôm nay</p>
                    <a href="<?= BASE_URL ?>/admin/sales/create.php" class="btn btn-primary rounded-pill mt-2">Bán hàng ngay</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Tổng</th>
                                <th>Thời gian</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRecentInvoices as $order): ?>
                                <tr>
                                    <td><span class="fw-bold text-dark">#<?= htmlspecialchars($order['order_code']) ?></span></td>
                                    <td><?= htmlspecialchars($order['customer_name'] ?? 'Khách lẻ') ?></td>
                                    <td><span class="text-success fw-bold"><?= formatCurrency($order['total_amount']) ?></span></td>
                                    <td><small class="text-muted"><?= date('H:i d/m', strtotime($order['created_at'])) ?></small></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/sales/view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-icon btn-light-primary rounded-circle">
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

    <!-- Top Products by Me -->
    <div class="col-xl-5">
        <div class="dash-card">
            <h5 class="fw-bold mb-4">Top món bạn bán chạy</h5>
            <?php if (empty($myTopProducts)): ?>
                <p class="text-center text-muted py-5">Chưa có dữ liệu bán hàng tháng này.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php $rank = 1; foreach ($myTopProducts as $product): ?>
                        <div class="list-group-item px-0 border-0 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="rank-circle bg-primary text-white me-3">
                                    <?= $rank++ ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($product['name']) ?></div>
                                    <small class="text-muted">Đã bán <?= number_format($product['total_sold']) ?> món</small>
                                </div>
                                <div class="bg-light p-2 rounded text-center" style="min-width: 60px;">
                                    <div class="small fw-bold text-primary">TOP</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .bg-light-primary { background-color: rgba(111, 78, 55, 0.1) !important; }
    .bg-light-info { background-color: rgba(59, 130, 246, 0.1) !important; }
    .bg-light-success { background-color: rgba(34, 197, 94, 0.1) !important; }
    .bg-light-warning { background-color: rgba(234, 179, 8, 0.1) !important; }
    
    .rank-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.85rem;
    }
    
    .btn-light-primary {
        background-color: rgba(111, 78, 55, 0.1);
        color: var(--primary-600);
        border: none;
    }
</style>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
