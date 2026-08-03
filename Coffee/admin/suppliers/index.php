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
                <h4 class="fw-bold mb-1">Quản lý nhà cung cấp</h4>
                <p class="text-muted small mb-0">Quản lý danh sách các đối tác cung cấp nguyên liệu và sản phẩm.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/suppliers/create.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-2"></i> Thêm nhà cung cấp
            </a>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="dash-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table id="suppliersTable" class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted">
                    <th class="ps-4">Nhà cung cấp</th>
                    <th>Liên hệ</th>
                    <th>Địa chỉ</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                    <div class="text-muted small">ID: #<?php echo $supplier['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><i class="bi bi-telephone me-2 text-muted"></i><?php echo htmlspecialchars($supplier['phone']); ?></div>
                            <div class="small"><i class="bi bi-envelope me-2 text-muted"></i><?php echo htmlspecialchars($supplier['email']); ?></div>
                        </td>
                        <td>
                            <div class="text-muted small" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <i class="bi bi-geo-alt me-2"></i><?php echo htmlspecialchars($supplier['address']); ?>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo BASE_URL; ?>/admin/suppliers/edit.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/admin/suppliers/delete.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này?');">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
    .table thead th {
        background-color: #f8fafc;
        border-bottom: 2px solid #f1f5f9;
        font-weight: 700;
        color: #64748b;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    .table tbody tr:hover {
        background-color: #f8fafc !important;
    }
</style>

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
