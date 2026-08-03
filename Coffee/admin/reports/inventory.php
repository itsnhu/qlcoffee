<?php



require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';


requireAdmin();


try {
    
    $totalProducts = fetchOne($pdo, "SELECT COUNT(*) as total FROM products")['total'] ?? 0;

    
    $totalInventoryValue = fetchOne($pdo, "
        SELECT SUM(quantity * price) as total
        FROM products
    ")['total'] ?? 0;

    
    $lowStockProducts = fetchAll($pdo, "
        SELECT m.*, c.name as category_name, s.name as supplier_name
        FROM products m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        WHERE m.quantity < 10
        ORDER BY m.quantity ASC, m.name ASC
    ");

    
    $expiringProducts = fetchAll($pdo, "
        SELECT m.*, c.name as category_name, s.name as supplier_name,
               DATEDIFF(m.expiry_date, CURDATE()) as days_until_expiry
        FROM products m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        WHERE m.expiry_date IS NOT NULL
        AND m.expiry_date > CURDATE()
        AND DATEDIFF(m.expiry_date, CURDATE()) <= 30
        ORDER BY m.expiry_date ASC, m.name ASC
    ");

    
    $expiredProducts = fetchAll($pdo, "
        SELECT m.*, c.name as category_name, s.name as supplier_name
        FROM products m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        WHERE m.expiry_date IS NOT NULL
        AND m.expiry_date <= CURDATE()
        ORDER BY m.expiry_date DESC, m.name ASC
    ");

    
    $allProducts = fetchAll($pdo, "
        SELECT m.*, c.name as category_name, s.name as supplier_name
        FROM products m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        ORDER BY m.quantity ASC, m.name ASC
    ");

} catch (PDOException $e) {
    error_log("Inventory Report Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi xảy ra khi tải thống kê tồn kho.');
    $totalProducts = 0;
    $totalInventoryValue = 0;
    $lowStockProducts = [];
    $expiringProducts = [];
    $expiredProducts = [];
    $allProducts = [];
}

$pageTitle = 'Thống kê Tồn kho';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Additional CSS for printing -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    .navbar, .btn {
        display: none !important;
    }
}

.status-badge {
    font-size: 0.85rem;
    padding: 0.4em 0.6em;
}
</style>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show no-print" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-clipboard-data"></i>
                    Thống kê Tồn kho
                </h2>
                <p class="text-muted mb-0">Thống kê tình trạng tồn kho và hạn sử dụng</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> In thống kê
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/reports/export.php?type=inventory" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Print Header (only visible when printing) -->
<div class="d-none d-print-block text-center mb-4">
    <h3>THỐNG KÊ TỒN KHO</h3>
    <p class="mb-0">Ngày thống kê: <?php echo date('d/m/Y H:i'); ?></p>
    <hr>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <!-- Tổng số món -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-primary fw-bold small mb-1">Tổng số món</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format($totalProducts); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box-seam fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng giá trị tồn kho -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-success fw-bold small mb-1">Giá trị tồn kho</div>
                        <div class="h5 mb-0 fw-bold"><?php echo formatCurrency($totalInventoryValue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Món sắp hết -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-warning border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-warning fw-bold small mb-1">Món sắp hết</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format(count($lowStockProducts)); ?></div>
                        <small class="text-muted">Số lượng < 10</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm sắp/đã hết hạn -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-danger border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-danger fw-bold small mb-1">Sắp/Hết hạn</div>
                        <div class="h3 mb-0 fw-bold"><?php echo number_format(count($expiringProducts) + count($expiredProducts)); ?></div>
                        <small class="text-muted"><?php echo count($expiredProducts); ?> đã hết hạn</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-x fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Món đã hết hạn -->
<?php if (!empty($expiredProducts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                <strong>Món đã hết hạn (<?php echo count($expiredProducts); ?>)</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã món</th>
                                <th>Tên món</th>
                                <th>Danh mục</th>
                                <th>Số lượng</th>
                                <th>Hạn sử dụng</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiredProducts as $product): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo number_format($product['quantity']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo formatDate($product['expiry_date']); ?>
                                        </span>
                                    </td>
                                    <td class="text-danger fw-bold">
                                        <?php echo formatCurrency($product['quantity'] * $product['price']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Món sắp hết hạn -->
<?php if (!empty($expiringProducts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Món sắp hết hạn - Dưới 30 ngày (<?php echo count($expiringProducts); ?>)</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã món</th>
                                <th>Tên món</th>
                                <th>Danh mục</th>
                                <th>Số lượng</th>
                                <th>Hạn sử dụng</th>
                                <th>Còn lại</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringProducts as $product): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo number_format($product['quantity']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo formatDate($product['expiry_date']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $product['days_until_expiry']; ?> ngày
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        <?php echo formatCurrency($product['quantity'] * $product['price']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Món sắp hết hàng -->
<?php if (!empty($lowStockProducts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-box-seam me-2"></i>
                <strong>Món sắp hết hàng - Số lượng < 10 (<?php echo count($lowStockProducts); ?>)</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã món</th>
                                <th>Tên món</th>
                                <th>Danh mục</th>
                                <th>Nhà cung cấp</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($product['supplier_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['quantity'] == 0 ? 'danger' : 'warning'; ?> text-dark">
                                            <?php echo number_format($product['quantity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($product['price']); ?></td>
                                    <td class="fw-bold">
                                        <?php echo formatCurrency($product['quantity'] * $product['price']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tất cả món theo tồn kho -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-list-ul me-2"></i>
                <strong>Tất cả món theo tồn kho</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Mã món</th>
                                <th>Tên món</th>
                                <th>Danh mục</th>
                                <th>Số lượng</th>
                                <th>Đơn vị</th>
                                <th>Giá bán</th>
                                <th>Giá trị</th>
                                <th>Hạn dùng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($allProducts as $product):
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($product['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Hết hàng</span>
                                        <?php elseif ($product['quantity'] < 10): ?>
                                            <span class="badge bg-warning text-dark"><?php echo number_format($product['quantity']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo number_format($product['quantity']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                    <td><?php echo formatCurrency($product['price']); ?></td>
                                    <td class="fw-bold">
                                        <?php echo formatCurrency($product['quantity'] * $product['price']); ?>
                                    </td>
                                    <td>
                                        <?php if ($product['expiry_date']): ?>
                                            <?php
                                            $daysUntilExpiry = (strtotime($product['expiry_date']) - time()) / (60 * 60 * 24);
                                            if ($daysUntilExpiry < 0): ?>
                                                <span class="badge bg-danger">Hết hạn</span>
                                            <?php elseif ($daysUntilExpiry <= 30): ?>
                                                <span class="badge bg-warning text-dark"><?php echo formatDate($product['expiry_date']); ?></span>
                                            <?php else: ?>
                                                <?php echo formatDate($product['expiry_date']); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">Tổng giá trị tồn kho:</td>
                                <td class="text-success"><?php echo formatCurrency($totalInventoryValue); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
