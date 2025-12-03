<?php

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireLogin();

$filter_category = $_GET['category'] ?? '';
$filter_search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';

$sql = "SELECT m.*, c.name as category_name, s.name as supplier_name
        FROM medicines m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        WHERE 1=1";

$params = [];

if (!empty($filter_category)) {
    $sql .= " AND m.category_id = ?";
    $params[] = $filter_category;
}

if (!empty($filter_search)) {
    $sql .= " AND (m.name LIKE ? OR m.code LIKE ?)";
    $params[] = '%' . $filter_search . '%';
    $params[] = '%' . $filter_search . '%';
}

$sql .= " ORDER BY m.name ASC";

try {
    $medicines = fetchAll($pdo, $sql, $params);
} catch (PDOException $e) {
    error_log("Medicine List Error: " . $e->getMessage());
    $medicines = [];
}

try {
    $categories = fetchAll($pdo, "SELECT * FROM categories ORDER BY name");
} catch (PDOException $e) {
    $categories = [];
}

$totalMedicines = count($medicines);
$lowStock = 0;
$expiringSoon = 0;
$expired = 0;
$today = date('Y-m-d');
$thirtyDays = date('Y-m-d', strtotime('+30 days'));

foreach ($medicines as $m) {
    if ($m['quantity'] < 10) $lowStock++;
    if ($m['expiry_date']) {
        if ($m['expiry_date'] < $today) $expired++;
        elseif ($m['expiry_date'] <= $thirtyDays) $expiringSoon++;
    }
}

if (!empty($filter_status)) {
    $medicines = array_filter($medicines, function($m) use ($filter_status, $today, $thirtyDays) {
        if ($filter_status === 'low') {
            return $m['quantity'] < 10;
        } elseif ($filter_status === 'expiring') {
            return $m['expiry_date'] && $m['expiry_date'] > $today && $m['expiry_date'] <= $thirtyDays;
        } elseif ($filter_status === 'expired') {
            return $m['expiry_date'] && $m['expiry_date'] < $today;
        }
        return true;
    });
}

$pageTitle = 'Tra cứu thuốc';
$additionalCSS = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .medicine-row:hover {
        background-color: rgba(20, 184, 166, 0.05) !important;
    }
    .badge-stock {
        min-width: 80px;
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
                    <i class="bi bi-capsule text-primary"></i>
                    Tra cứu thuốc
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Xem thông tin thuốc trong kho
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Tổng số thuốc</p>
                        <h4 class="mb-0"><?php echo number_format($totalMedicines); ?></h4>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-capsule" style="font-size: 2rem;"></i>
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
                        <p class="text-muted mb-1">Sắp hết hàng</p>
                        <h4 class="mb-0 text-warning"><?php echo number_format($lowStock); ?></h4>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
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
                        <p class="text-muted mb-1">Sắp hết hạn</p>
                        <h4 class="mb-0 text-info"><?php echo number_format($expiringSoon); ?></h4>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-calendar-event" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted mb-1">Đã hết hạn</p>
                        <h4 class="mb-0 text-danger"><?php echo number_format($expired); ?></h4>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light text-dark">
        <i class="bi bi-funnel me-2"></i>
        <strong>Lọc dữ liệu</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           placeholder="Tên thuốc hoặc mã..."
                           value="<?php echo htmlspecialchars($filter_search); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="category" class="form-label">Loại thuốc</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                    <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">-- Tất cả --</option>
                        <option value="low" <?php echo $filter_status === 'low' ? 'selected' : ''; ?>>Sắp hết hàng</option>
                        <option value="expiring" <?php echo $filter_status === 'expiring' ? 'selected' : ''; ?>>Sắp hết hạn</option>
                        <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>Đã hết hạn</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Lọc
                    </button>
                    <a href="<?php echo BASE_URL; ?>/employee/medicines/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Medicines Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách thuốc</strong>
    </div>
    <div class="card-body">
        <?php if (empty($medicines)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Không tìm thấy thuốc nào.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="medicinesTable" class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Mã thuốc</th>
                            <th>Tên thuốc</th>
                            <th>Loại</th>
                            <th>Nhà cung cấp</th>
                            <th class="text-end">Giá bán</th>
                            <th class="text-center">Tồn kho</th>
                            <th>Hạn dùng</th>
                            <th class="text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $medicine):
                            $isLowStock = $medicine['quantity'] < 10;
                            $isExpired = $medicine['expiry_date'] && $medicine['expiry_date'] < $today;
                            $isExpiring = $medicine['expiry_date'] && !$isExpired && $medicine['expiry_date'] <= $thirtyDays;
                        ?>
                        <tr class="medicine-row">
                            <td>
                                <strong class="text-primary"><?php echo htmlspecialchars($medicine['code']); ?></strong>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($medicine['name']); ?></div>
                                <div class="d-flex gap-1 mt-1">
                                    <?php if ($isLowStock): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Sắp hết</span>
                                    <?php endif; ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Hết hạn</span>
                                    <?php elseif ($isExpiring): ?>
                                        <span class="badge bg-info"><i class="bi bi-clock"></i> Sắp hết hạn</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($medicine['category_name'] ?? '-'); ?></span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($medicine['supplier_name'] ?? '-'); ?></small>
                            </td>
                            <td class="text-end">
                                <strong class="text-success"><?php echo number_format($medicine['price'], 0, ',', '.'); ?>đ</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-stock <?php echo $isLowStock ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                    <?php echo number_format($medicine['quantity']); ?> <?php echo htmlspecialchars($medicine['unit']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($medicine['expiry_date']): ?>
                                    <span class="<?php echo $isExpired ? 'text-danger fw-bold' : ($isExpiring ? 'text-warning' : ''); ?>">
                                        <i class="bi bi-calendar3"></i>
                                        <?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal<?php echo $medicine['id']; ?>"
                                        title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Chi tiết -->
                        <div class="modal fade" id="modal<?php echo $medicine['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-capsule me-2"></i>Chi tiết thuốc
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-4">
                                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-3" style="width:80px;height:80px;">
                                                <i class="bi bi-capsule text-primary" style="font-size: 2rem;"></i>
                                            </div>
                                            <h4 class="mb-1"><?php echo htmlspecialchars($medicine['name']); ?></h4>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($medicine['code']); ?></span>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Loại thuốc</small>
                                                    <strong><?php echo htmlspecialchars($medicine['category_name'] ?? '-'); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Nhà cung cấp</small>
                                                    <strong><?php echo htmlspecialchars($medicine['supplier_name'] ?? '-'); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Giá bán</small>
                                                    <strong class="text-success"><?php echo number_format($medicine['price'], 0, ',', '.'); ?>đ</strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Tồn kho</small>
                                                    <strong class="<?php echo $isLowStock ? 'text-warning' : ''; ?>">
                                                        <?php echo number_format($medicine['quantity']); ?> <?php echo htmlspecialchars($medicine['unit']); ?>
                                                    </strong>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Hạn sử dụng</small>
                                                    <strong class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                                                        <?php echo $medicine['expiry_date'] ? date('d/m/Y', strtotime($medicine['expiry_date'])) : 'Không có'; ?>
                                                    </strong>
                                                </div>
                                            </div>
                                            <?php if ($medicine['description']): ?>
                                            <div class="col-12">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Mô tả</small>
                                                    <span><?php echo htmlspecialchars($medicine['description']); ?></span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg me-1"></i> Đóng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    $('#medicinesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        pageLength: 15,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: 7 }
        ],
        responsive: true
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
