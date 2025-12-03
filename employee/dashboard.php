<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireEmployee();

try {
    $totalMedicines = fetchOne($pdo, "SELECT COUNT(*) as total FROM medicines")['total'] ?? 0;

    $myTodayInvoices = fetchOne($pdo, "
        SELECT COUNT(*) as total
        FROM invoices
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myTodayRevenue = fetchOne($pdo, "
        SELECT SUM(total_amount) as total
        FROM invoices
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myMonthInvoices = fetchOne($pdo, "
        SELECT COUNT(*) as total
        FROM invoices
        WHERE user_id = ?
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $myMonthRevenue = fetchOne($pdo, "
        SELECT SUM(total_amount) as total
        FROM invoices
        WHERE user_id = ?
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ", [$_SESSION['user_id']])['total'] ?? 0;

    $lowStockCount = fetchOne($pdo, "SELECT COUNT(*) as total FROM medicines WHERE quantity < 10")['total'] ?? 0;

    $myRecentInvoices = fetchAll($pdo, "
        SELECT *
        FROM invoices
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ", [$_SESSION['user_id']]);

    $myTopMedicines = fetchAll($pdo, "
        SELECT m.name, SUM(id.quantity) as total_sold
        FROM invoice_details id
        JOIN medicines m ON id.medicine_id = m.id
        JOIN invoices i ON id.invoice_id = i.id
        WHERE i.user_id = ?
        AND MONTH(i.created_at) = MONTH(CURDATE())
        AND YEAR(i.created_at) = YEAR(CURDATE())
        GROUP BY m.id, m.name
        ORDER BY total_sold DESC
        LIMIT 5
    ", [$_SESSION['user_id']]);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalMedicines = $myTodayInvoices = $myTodayRevenue = $myMonthInvoices = $myMonthRevenue = $lowStockCount = 0;
    $myRecentInvoices = $myTopMedicines = [];
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

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <h2 class="mb-2">
                    <i class="bi bi-person-circle"></i>
                    Chào mừng, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
                </h2>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-2"></i>
                    <?php echo date('l, d/m/Y - H:i'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <!-- Hóa đơn hôm nay -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-primary fw-bold small mb-1">HĐ hôm nay</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format($myTodayInvoices); ?></div>
                        <small class="text-muted">hóa đơn</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-receipt fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doanh thu hôm nay -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-success fw-bold small mb-1">DT hôm nay</div>
                        <div class="h3 mb-0 fw-bold"><?php echo formatCurrency($myTodayRevenue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hóa đơn tháng này -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-info border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-info fw-bold small mb-1">HĐ tháng này</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format($myMonthInvoices); ?></div>
                        <small class="text-success fw-bold"><?php echo formatCurrency($myMonthRevenue); ?></small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thuốc sắp hết -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-warning border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-warning fw-bold small mb-1">Thuốc sắp hết</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format($lowStockCount); ?></div>
                        <small class="text-muted">Số lượng < 10</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning-fill me-2"></i>
                <strong>Thao tác nhanh</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-plus-circle fs-4 d-block mb-2"></i>
                            <strong>Tạo hóa đơn mới</strong>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?php echo BASE_URL; ?>/employee/medicines/index.php" class="btn btn-outline-info w-100 py-3">
                            <i class="bi bi-search fs-4 d-block mb-2"></i>
                            <strong>Tra cứu thuốc</strong>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-outline-success w-100 py-3">
                            <i class="bi bi-clock-history fs-4 d-block mb-2"></i>
                            <strong>Lịch sử bán hàng</strong>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Hóa đơn gần nhất của tôi -->
    <div class="col-xl-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-receipt me-2"></i>
                <strong>Hóa đơn gần nhất của tôi</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($myRecentInvoices)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Bạn chưa tạo hóa đơn nào</p>
                        <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle me-2"></i>
                            Tạo hóa đơn ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Thời gian</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myRecentInvoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($invoice['invoice_code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($invoice['customer_name'] ?? 'Khách lẻ'); ?></td>
                                        <td class="text-success fw-bold">
                                            <?php echo formatCurrency($invoice['total_amount']); ?>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($invoice['created_at'], DATETIME_FORMAT); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/sales/view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
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
            <?php if (!empty($myRecentInvoices)): ?>
                <div class="card-footer bg-light text-center">
                    <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="text-decoration-none">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top thuốc bán chạy của tôi -->
    <div class="col-xl-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-trophy me-2"></i>
                <strong>Top thuốc tôi bán tháng này</strong>
            </div>
            <div class="card-body">
                <?php if (empty($myTopMedicines)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu bán hàng</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php $rank = 1; foreach ($myTopMedicines as $medicine): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-success rounded-circle" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                            <?php echo $rank++; ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold"><?php echo htmlspecialchars($medicine['name']); ?></div>
                                        <small class="text-muted">
                                            Đã bán: <?php echo number_format($medicine['total_sold']); ?> sản phẩm
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tips Section -->
<div class="row">
    <div class="col-12">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="bi bi-lightbulb text-info fs-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="text-info mb-2">
                            <i class="bi bi-info-circle me-2"></i>
                            Mẹo sử dụng
                        </h5>
                        <ul class="mb-0">
                            <li>Sử dụng chức năng <strong>Tra cứu thuốc</strong> để tìm kiếm nhanh thông tin thuốc khi khách hàng hỏi</li>
                            <li>Kiểm tra số lượng tồn kho trước khi tạo hóa đơn để tránh thiếu hàng</li>
                            <li>Nhập đầy đủ thông tin khách hàng để dễ dàng tra cứu lịch sử mua hàng sau này</li>
                            <li>Thường xuyên xem phần <strong>Thuốc sắp hết</strong> để thông báo cho quản lý kịp thời</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
