<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $sql = "SELECT i.id, i.import_code, i.total_amount, i.note, i.created_at,
                   s.name as supplier_name,
                   u.full_name as user_name
            FROM imports i
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN users u ON i.user_id = u.id
            ORDER BY i.created_at DESC";
    $imports = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Import List Error: " . $e->getMessage());
    $imports = [];
    setMessage('danger', 'Có lỗi khi tải danh sách phiếu nhập.');
}

$pageTitle = 'Quản lý nhập hàng';
$additionalCSS = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
                    <i class="bi bi-box-seam text-primary"></i>
                    Quản lý nhập hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý các phiếu nhập hàng vào kho
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/imports/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Tạo phiếu nhập mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75">Tổng phiếu nhập</p>
                        <h3 class="mb-0"><?php echo count($imports); ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75">Tổng giá trị nhập</p>
                        <h3 class="mb-0">
                            <?php
                            $totalValue = array_sum(array_column($imports, 'total_amount'));
                            echo number_format($totalValue, 0, ',', '.');
                            ?>đ
                        </h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75">Nhập trong tháng</p>
                        <h3 class="mb-0">
                            <?php
                            $currentMonth = date('Y-m');
                            $monthlyImports = array_filter($imports, function($import) use ($currentMonth) {
                                return strpos($import['created_at'], $currentMonth) === 0;
                            });
                            echo count($monthlyImports);
                            ?>
                        </h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Imports Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách phiếu nhập</strong>
        <span class="badge bg-light text-dark ms-2"><?php echo count($imports); ?> phiếu</span>
    </div>
    <div class="card-body">
        <?php if (empty($imports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có phiếu nhập nào trong hệ thống</p>
                <a href="<?php echo BASE_URL; ?>/admin/imports/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Tạo phiếu nhập đầu tiên
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="importsTable" class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th width="12%">Mã phiếu nhập</th>
                            <th width="18%">Nhà cung cấp</th>
                            <th width="15%">Người nhập</th>
                            <th width="15%" class="text-end">Tổng tiền</th>
                            <th width="20%">Ghi chú</th>
                            <th width="12%">Ngày nhập</th>
                            <th width="8%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $import): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?php echo htmlspecialchars($import['import_code']); ?></strong>
                            </td>
                            <td>
                                <i class="bi bi-shop text-muted"></i>
                                <?php echo htmlspecialchars($import['supplier_name']); ?>
                            </td>
                            <td>
                                <i class="bi bi-person text-muted"></i>
                                <?php echo htmlspecialchars($import['user_name']); ?>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">
                                    <?php echo number_format($import['total_amount'], 0, ',', '.'); ?>đ
                                </strong>
                            </td>
                            <td>
                                <?php if ($import['note']): ?>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(substr($import['note'], 0, 50)); ?>
                                        <?php if (strlen($import['note']) > 50) echo '...'; ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($import['created_at'])); ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo BASE_URL; ?>/admin/imports/view.php?id=<?php echo $import['id']; ?>"
                                   class="btn btn-sm btn-outline-info"
                                   title="Xem chi tiết">
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

<!-- DataTable JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#importsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
        },
        "pageLength": 25,
        "order": [[5, "desc"]], // Sắp xếp theo ngày nhập mới nhất
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Không cho sắp xếp cột thao tác
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
