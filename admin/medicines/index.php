<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $sql = "SELECT m.id, m.name, m.code, m.price, m.quantity, m.unit, m.expiry_date,
                   c.name as category_name,
                   s.name as supplier_name
            FROM medicines m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            ORDER BY m.name ASC";
    $medicines = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Medicine List Error: " . $e->getMessage());
    $medicines = [];
    setMessage('danger', 'Có lỗi khi tải danh sách thuốc.');
}

$pageTitle = 'Quản lý thuốc';
$additionalCSS = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .low-stock {
        background-color: #fff3cd !important;
    }
    .expiring-soon {
        background-color: #f8d7da !important;
    }
    .expired {
        background-color: #dc3545 !important;
        color: white !important;
    }
    .badge-low-stock {
        background-color: #ffc107;
        color: #000;
    }
    .badge-expiring {
        background-color: #fd7e14;
        color: white;
    }
    .badge-expired {
        background-color: #dc3545;
        color: white;
    }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-capsule-pill text-primary"></i>
                    Quản lý thuốc
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý thông tin thuốc trong hệ thống
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/medicines/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm thuốc mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Thông tin cảnh báo -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <span class="badge badge-low-stock me-2">
                            <i class="bi bi-exclamation-triangle"></i>
                        </span>
                        <small>Thuốc sắp hết (số lượng &lt; 10)</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-expiring me-2">
                            <i class="bi bi-calendar-x"></i>
                        </span>
                        <small>Thuốc sắp hết hạn (còn &lt; 30 ngày)</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-expired me-2">
                            <i class="bi bi-x-circle"></i>
                        </span>
                        <small>Thuốc đã hết hạn</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Medicines Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách thuốc</strong>
        <span class="badge bg-light text-dark ms-2"><?php echo count($medicines); ?> thuốc</span>
    </div>
    <div class="card-body">
        <?php if (empty($medicines)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có thuốc nào trong hệ thống</p>
                <a href="<?php echo BASE_URL; ?>/admin/medicines/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm thuốc đầu tiên
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="medicinesTable" class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th width="8%">Mã thuốc</th>
                            <th width="20%">Tên thuốc</th>
                            <th width="12%">Loại thuốc</th>
                            <th width="15%">Nhà cung cấp</th>
                            <th width="10%" class="text-end">Giá bán</th>
                            <th width="8%" class="text-center">Số lượng</th>
                            <th width="8%">Đơn vị</th>
                            <th width="10%">Hạn sử dụng</th>
                            <th width="9%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = date('Y-m-d');
                        $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));

                        foreach ($medicines as $medicine):
                            $rowClass = '';
                            $statusBadges = [];

                            
                            if ($medicine['quantity'] < 10) {
                                $rowClass = 'low-stock';
                                $statusBadges[] = '<span class="badge badge-low-stock"><i class="bi bi-exclamation-triangle"></i> Sắp hết</span>';
                            }

                            
                            if ($medicine['expiry_date']) {
                                if ($medicine['expiry_date'] < $today) {
                                    $rowClass = 'expired';
                                    $statusBadges[] = '<span class="badge badge-expired"><i class="bi bi-x-circle"></i> Hết hạn</span>';
                                } elseif ($medicine['expiry_date'] <= $thirtyDaysLater) {
                                    if ($rowClass !== 'expired') {
                                        $rowClass = 'expiring-soon';
                                    }
                                    $statusBadges[] = '<span class="badge badge-expiring"><i class="bi bi-calendar-x"></i> Sắp hết hạn</span>';
                                }
                            }
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><strong><?php echo htmlspecialchars($medicine['code']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($medicine['name']); ?>
                                <?php if (!empty($statusBadges)): ?>
                                    <br><small><?php echo implode(' ', $statusBadges); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($medicine['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="bi bi-shop"></i>
                                    <?php echo htmlspecialchars($medicine['supplier_name']); ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <strong><?php echo number_format($medicine['price'], 0, ',', '.'); ?>đ</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $medicine['quantity'] < 10 ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                    <?php echo number_format($medicine['quantity']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($medicine['unit']); ?></td>
                            <td>
                                <?php if ($medicine['expiry_date']): ?>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo BASE_URL; ?>/admin/medicines/edit.php?id=<?php echo $medicine['id']; ?>"
                                       class="btn btn-outline-primary"
                                       title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/admin/medicines/delete.php?id=<?php echo $medicine['id']; ?>"
                                       class="btn btn-outline-danger"
                                       title="Xóa"
                                       onclick="return confirm('Bạn có chắc muốn xóa thuốc này?');">
                                        <i class="bi bi-trash"></i>
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

<!-- DataTable JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#medicinesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
        },
        "pageLength": 25,
        "order": [[1, "asc"]], // Sắp xếp theo tên thuốc
        "columnDefs": [
            { "orderable": false, "targets": 8 } // Không cho sắp xếp cột thao tác
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
