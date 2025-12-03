<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $sql = "SELECT id, name, phone, address, created_at
            FROM suppliers
            ORDER BY name ASC";
    $suppliers = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Supplier List Error: " . $e->getMessage());
    $suppliers = [];
    setMessage('danger', 'Có lỗi khi tải danh sách nhà cung cấp.');
}

$pageTitle = 'Quản lý nhà cung cấp';
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
                    <i class="bi bi-truck text-primary"></i>
                    Quản lý nhà cung cấp
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý thông tin các nhà cung cấp thuốc
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/suppliers/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm nhà cung cấp mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách nhà cung cấp</strong>
    </div>
    <div class="card-body">
        <?php if (empty($suppliers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có nhà cung cấp nào trong hệ thống</p>
                <a href="<?php echo BASE_URL; ?>/admin/suppliers/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm nhà cung cấp đầu tiên
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="suppliersTable" class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tên nhà cung cấp</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['id']); ?></td>
                                <td>
                                    <i class="bi bi-building me-2 text-primary"></i>
                                    <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                </td>
                                <td>
                                    <i class="bi bi-telephone me-2 text-success"></i>
                                    <?php echo htmlspecialchars($supplier['phone']); ?>
                                </td>
                                <td>
                                    <i class="bi bi-geo-alt me-2 text-danger"></i>
                                    <?php echo htmlspecialchars($supplier['address']); ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($supplier['created_at'], DATETIME_FORMAT); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/suppliers/edit.php?id=<?php echo $supplier['id']; ?>"
                                           class="btn btn-outline-warning"
                                           title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/suppliers/delete.php?id=<?php echo $supplier['id']; ?>"
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp <?php echo htmlspecialchars($supplier['name']); ?>? Hành động này không thể hoàn tác!');"
                                           title="Xóa">
                                            <i class="bi bi-trash3"></i>
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

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#suppliersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
        columnDefs: [
            { orderable: false, targets: 5 }
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
