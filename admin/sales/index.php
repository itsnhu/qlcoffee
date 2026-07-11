<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$filter_date = $_GET['date'] ?? '';
$filter_customer = $_GET['customer'] ?? '';
$filter_staff = $_GET['staff'] ?? '';


$sql = "SELECT i.id, i.order_code, i.created_at, i.customer_name, i.customer_phone, i.total_amount, i.user_id, u.full_name as staff_name
        FROM orders i
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

if (!empty($filter_staff)) {
    $sql .= " AND i.user_id = ?";
    $params[] = $filter_staff;
}

$sql .= " ORDER BY i.created_at DESC";

try {
    $invoices = fetchAll($pdo, $sql, $params);
    $all_staff = fetchAll($pdo, "SELECT id, full_name FROM users WHERE role = 'employee' ORDER BY full_name");
} catch (PDOException $e) {
    error_log("Load Orders Error: " . $e->getMessage());
    $invoices = [];
}

$pageTitle = 'Danh sách hóa đơn';
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

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</div>

<!-- Statistics Section (for Admin) -->
<?php if ($_SESSION['role'] === 'admin'): ?>
<?php
$today = date('Y-m-d');
$stats_sql = "
    SELECT
        COUNT(*) as total_invoices,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as today_invoices,
        COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END), 0) as today_revenue
    FROM orders
";
$stats = fetchOne($pdo, $stats_sql, [$today, $today]) ?? [];
?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-primary">
                    <i class="bi bi-receipt text-primary"></i>
                </div>
                <div class="dash-card-trend text-primary"><i class="bi bi-info-circle"></i></div>
            </div>
            <div class="dash-card-title">Tổng hóa đơn</div>
            <div class="dash-card-value"><?= number_format($stats['total_invoices']) ?></div>
            <div class="small text-muted">Tất cả thời gian</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-success">
                    <i class="bi bi-currency-dollar text-success"></i>
                </div>
                <div class="dash-card-trend text-success"><i class="bi bi-graph-up"></i></div>
            </div>
            <div class="dash-card-title">Tổng doanh thu</div>
            <div class="dash-card-value"><?= formatCurrency($stats['total_revenue']) ?></div>
            <div class="small text-muted">Tổng tích lũy</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-info">
                    <i class="bi bi-calendar-check text-info"></i>
                </div>
                <div class="dash-card-trend text-info"><i class="bi bi-clock"></i></div>
            </div>
            <div class="dash-card-title">Hóa đơn hôm nay</div>
            <div class="dash-card-value"><?= number_format($stats['today_invoices']) ?></div>
            <div class="small text-muted">Tính đến hiện tại</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-warning">
                    <i class="bi bi-lightning-charge text-warning"></i>
                </div>
                <div class="dash-card-trend text-warning"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <div class="dash-card-title">Doanh thu hôm nay</div>
            <div class="dash-card-value"><?= formatCurrency($stats['today_revenue']) ?></div>
            <div class="small text-muted">Thực nhận trong ngày</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter & List Section -->
<div class="dash-card p-0 overflow-hidden">
    <!-- Filter Header -->
    <div class="p-4 border-bottom bg-light bg-opacity-10">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Ngày tạo</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Tên khách hàng</label>
                <input type="text" name="customer" class="form-control" placeholder="Tên khách..." value="<?= htmlspecialchars($filter_customer) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Nhân viên</label>
                <select name="staff" class="form-select">
                    <option value="">Tất cả nhân viên</option>
                    <?php foreach ($all_staff as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_staff == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-3 rounded-3 h-100 flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Lọc
                </button>
                <a href="<?= BASE_URL ?>/admin/sales/index.php" class="btn btn-outline-secondary px-4 rounded-3 h-100">
                    <i class="bi bi-arrow-clockwise me-2"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Body -->
    <div class="p-0">
        <?php if (empty($invoices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Không tìm thấy hóa đơn nào khớp với bộ lọc.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-4">Mã hóa đơn</th>
                            <th>Ngày tạo</th>
                            <th>Khách hàng</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Nhân viên</th>
                            <?php endif; ?>
                            <th>Tổng thanh toán</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">#<?= htmlspecialchars($invoice['order_code']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-dark small fw-bold"><?= date('d/m/Y', strtotime($invoice['created_at'])) ?></span>
                                    <span class="text-muted small"><?= date('H:i', strtotime($invoice['created_at'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light-primary text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 12px; font-weight: bold;">
                                        <?= strtoupper(substr($invoice['customer_name'] ?? 'K', 0, 1)) ?>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-dark fw-bold small"><?= htmlspecialchars($invoice['customer_name'] ?? 'Khách lẻ') ?></span>
                                        <span class="text-muted small"><?= htmlspecialchars($invoice['customer_phone'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <span class="badge bg-light text-primary border border-primary border-opacity-10 fw-medium">
                                    <?= htmlspecialchars($invoice['staff_name'] ?? 'Khách tự đặt (Online)') ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <span class="text-success fw-bold"><?= formatCurrency($invoice['total_amount']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= BASE_URL ?>/admin/sales/view.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-icon btn-light-primary rounded-pill" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/sales/print.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn btn-sm btn-icon btn-light-info rounded-pill" title="In">
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

<style>
    .bg-light-primary { background-color: rgba(111, 78, 55, 0.1) !important; }
    .bg-light-info { background-color: rgba(59, 130, 246, 0.1) !important; }
    .bg-light-success { background-color: rgba(34, 197, 94, 0.1) !important; }
    .bg-light-warning { background-color: rgba(234, 179, 8, 0.1) !important; }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    
    .btn-light-primary { background-color: #f0fdfa; color: #14b8a6; border: none; }
    .btn-light-primary:hover { background-color: #14b8a6; color: white; }
    
    .btn-light-info { background-color: #eff6ff; color: #3b82f6; border: none; }
    .btn-light-info:hover { background-color: #3b82f6; color: white; }
    
    #invoicesTable thead th {
        font-weight: 700;
        border-top: none;
    }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#invoicesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json' },
            order: [[1, 'desc']],
            pageLength: 25,
            responsive: true,
            dom: '<"p-3 d-flex justify-content-between align-items-center"lf>t<"p-3 d-flex justify-content-between align-items-center"ip>'
        });
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
