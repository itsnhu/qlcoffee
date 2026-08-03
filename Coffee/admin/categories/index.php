<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $sql = "SELECT id, name, created_at
            FROM categories
            ORDER BY name ASC";
    $categories = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Category List Error: " . $e->getMessage());
    $categories = [];
    setMessage('danger', 'Có lỗi khi tải danh sách danh mục.');
}

$pageTitle = 'Quản lý danh mục';
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
                <h4 class="fw-bold mb-1">Quản lý danh mục</h4>
                <p class="text-muted small mb-0">Phân loại các món ăn, đồ uống để dễ dàng quản lý thực đơn.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/categories/create.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-2"></i> Thêm danh mục
            </a>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="dash-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table id="categoriesTable" class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted">
                    <th class="ps-4">ID</th>
                    <th>Tên danh mục</th>
                    <th>Ngày tạo</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td class="ps-4 text-muted small">#<?php echo $category['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <span class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></span>
                            </div>
                        </td>
                        <td class="text-muted small">
                            <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo BASE_URL; ?>/admin/categories/edit.php?id=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-square me-1"></i> Sửa
                                </a>
                                <a href="<?php echo BASE_URL; ?>/admin/categories/delete.php?id=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                    <i class="bi bi-trash3 me-1"></i> Xóa
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
        columnDefs: [
            { orderable: false, targets: 3 }
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
