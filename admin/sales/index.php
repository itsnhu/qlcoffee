<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$filter_date = $_GET['date'] ?? '';
$filter_customer = $_GET['customer'] ?? '';


$sql = "SELECT i.*, u.full_name as staff_name
        FROM invoices i
        LEFT JOIN users u ON i.user_id = u.id
        WHERE 1=1";

$params = [];


if ($_SESSION['role'] === 'employee') {
    $sql .= " AND i.user_id = ?";
    $params[] = $_SESSION['user_id'];
}


if (!empty($filter_date)) {
    $sql .= " AND DATE(i.created_at) = ?";
    $params[] = $filter_date;
}


if (!empty($filter_customer)) {
    $sql .= " AND i.customer_name LIKE ?";
    $params[] = '%' . $filter_customer . '%';
}

$sql .= " ORDER BY i.created_at DESC";

try {
    $invoices = fetchAll($pdo, $sql, $params);
} catch (PDOException $e) {
    error_log("Load Invoices Error: " . $e->getMessage());
    $invoices = [];
}

$pageTitle = 'Quản lý bán hàng';
$additionalCSS = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .invoice-card {
        transition: transform 0.2s;
    }
    .invoice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .stat-card {
        border-left: 4px solid;
    }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-receipt text-primary"></i>
                    Quản lý bán hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Danh sách hóa đơn bán hàng
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/sales/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tạo hóa đơn mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards (chỉ hiển thị cho Admin) -->
<?php if ($_SESSION['role'] === 'admin'): ?>
<?php

$today = date('Y-m-d');
$stats_sql = "
    SELECT
        COUNT(*) as total_invoices,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as today_invoices,
        COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END), 0) as today_revenue
    FROM invoices
";
$stats = fetchOne($pdo, $stats_sql, [$today, $today]) ?? [];
?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Tổng hóa đơn</p>
                        <h4 class="mb-0"><?php echo number_format($stats['total_invoices'] ?? 0); ?></h4>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-receipt-cutoff" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Tổng doanh thu</p>
                        <h4 class="mb-0 text-success"><?php echo number_format($stats['total_revenue'] ?? 0); ?>đ</h4>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Hóa đơn hôm nay</p>
                        <h4 class="mb-0 text-info"><?php echo number_format($stats['today_invoices'] ?? 0); ?></h4>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-calendar-check" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Doanh thu hôm nay</p>
                        <h4 class="mb-0 text-warning"><?php echo number_format($stats['today_revenue'] ?? 0); ?>đ</h4>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter Form -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light text-dark">
        <i class="bi bi-funnel me-2"></i>
        <strong>Lọc dữ liệu</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="date" class="form-label">Ngày</label>
                    <input type="date"
                           class="form-control"
                           id="date"
                           name="date"
                           value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="customer" class="form-label">Tên khách hàng</label>
                    <input type="text"
                           class="form-control"
                           id="customer"
                           name="customer"
                           placeholder="Tìm theo tên khách hàng..."
                           value="<?php echo htmlspecialchars($filter_customer); ?>">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Lọc
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách hóa đơn</strong>
    </div>
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Chưa có hóa đơn nào.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Mã HĐ</th>
                            <th>Ngày tạo</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Nhân viên</th>
                            <?php endif; ?>
                            <th>Tổng tiền</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>
                                <strong class="text-primary">
                                    <?php echo htmlspecialchars($invoice['invoice_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('d/m/Y H:i', strtotime($invoice['created_at'])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($invoice['customer_name'] ?? 'Khách lẻ'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($invoice['customer_phone'] ?? '-'); ?>
                            </td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($invoice['staff_name'] ?? '-'); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <strong class="text-success">
                                    <?php echo number_format($invoice['total_amount']); ?>đ
                                </strong>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo BASE_URL; ?>/admin/sales/view.php?id=<?php echo $invoice['id']; ?>"
                                       class="btn btn-info"
                                       title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/admin/sales/print.php?id=<?php echo $invoice['id']; ?>"
                                       class="btn btn-secondary"
                                       target="_blank"
                                       title="In hóa đơn">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#invoicesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[1, 'desc']], // Sắp xếp theo ngày tạo mới nhất
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
