<?php



require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';


requireAdmin();


try {
    
    $totalMedicines = fetchOne($pdo, "SELECT COUNT(*) as total FROM medicines")['total'] ?? 0;

    
    $totalUsers = fetchOne($pdo, "SELECT COUNT(*) as total FROM users WHERE is_active = 1")['total'] ?? 0;

    
    $todayInvoices = fetchOne($pdo, "SELECT COUNT(*) as total FROM invoices WHERE DATE(created_at) = CURDATE()")['total'] ?? 0;

    
    $todayRevenue = fetchOne($pdo, "SELECT SUM(total_amount) as total FROM invoices WHERE DATE(created_at) = CURDATE()")['total'] ?? 0;

    
    $monthRevenue = fetchOne($pdo, "SELECT SUM(total_amount) as total FROM invoices WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['total'] ?? 0;

    
    $lowStockCount = fetchOne($pdo, "SELECT COUNT(*) as total FROM medicines WHERE quantity < 10")['total'] ?? 0;

    
    $recentInvoices = fetchAll($pdo, "
        SELECT i.*, u.full_name as employee_name
        FROM invoices i
        LEFT JOIN users u ON i.user_id = u.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");

    
    $topMedicines = fetchAll($pdo, "
        SELECT m.name, SUM(id.quantity) as total_sold, SUM(id.subtotal) as revenue
        FROM invoice_details id
        JOIN medicines m ON id.medicine_id = m.id
        JOIN invoices i ON id.invoice_id = i.id
        WHERE MONTH(i.created_at) = MONTH(CURDATE()) AND YEAR(i.created_at) = YEAR(CURDATE())
        GROUP BY m.id, m.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalMedicines = $totalUsers = $todayInvoices = $todayRevenue = $monthRevenue = $lowStockCount = 0;
    $recentInvoices = $topMedicines = [];
}

$pageTitle = 'Dashboard Admin';
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
                    <i class="bi bi-speedometer2"></i>
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
    <!-- Tổng thuốc -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-primary fw-bold small mb-1">Tổng số thuốc</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format($totalMedicines); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-capsule fs-1 text-primary opacity-25"></i>
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
                        <div class="text-uppercase text-success fw-bold small mb-1">Doanh thu hôm nay</div>
                        <div class="h3 mb-0 fw-bold"><?php echo formatCurrency($todayRevenue); ?></div>
                        <small class="text-muted"><?php echo $todayInvoices; ?> hóa đơn</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doanh thu tháng này -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 border-start border-info border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-info fw-bold small mb-1">Doanh thu tháng này</div>
                        <div class="h3 mb-0 fw-bold"><?php echo formatCurrency($monthRevenue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up-arrow fs-1 text-info opacity-25"></i>
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
                    <div class="col-md-3">
                        <a href="<?php echo BASE_URL; ?>/admin/medicines.php?action=add" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle me-2"></i>
                            Thêm thuốc mới
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_URL; ?>/admin/imports.php?action=add" class="btn btn-outline-success w-100">
                            <i class="bi bi-box-seam me-2"></i>
                            Nhập hàng
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_URL; ?>/admin/invoices.php?action=add" class="btn btn-outline-info w-100">
                            <i class="bi bi-receipt me-2"></i>
                            Tạo hóa đơn
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-person-plus me-2"></i>
                            Thêm người dùng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Hóa đơn gần nhất -->
    <div class="col-xl-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-receipt me-2"></i>
                <strong>Hóa đơn gần nhất</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentInvoices)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có hóa đơn nào</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Nhân viên</th>
                                    <th>Tổng tiền</th>
                                    <th>Thời gian</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInvoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($invoice['invoice_code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($invoice['employee_name']); ?></td>
                                        <td class="text-success fw-bold">
                                            <?php echo formatCurrency($invoice['total_amount']); ?>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($invoice['created_at'], DATETIME_FORMAT); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/invoices.php?action=view&id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
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
            <?php if (!empty($recentInvoices)): ?>
                <div class="card-footer bg-light text-center">
                    <a href="<?php echo BASE_URL; ?>/admin/invoices.php" class="text-decoration-none">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top thuốc bán chạy -->
    <div class="col-xl-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-trophy me-2"></i>
                <strong>Top thuốc bán chạy tháng này</strong>
            </div>
            <div class="card-body">
                <?php if (empty($topMedicines)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php $rank = 1; foreach ($topMedicines as $medicine): ?>
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
                                    <div class="text-end">
                                        <div class="text-success fw-bold">
                                            <?php echo formatCurrency($medicine['revenue']); ?>
                                        </div>
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
