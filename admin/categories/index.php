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
    setMessage('danger', 'Có lỗi khi tải danh sách loại thuốc.');
}

$pageTitle = 'Quản lý loại thuốc';
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
                    <i class="bi bi-tags-fill text-primary"></i>
                    Quản lý loại thuốc
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý các loại thuốc trong hệ thống
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/categories/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm loại thuốc mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách loại thuốc</strong>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có loại thuốc nào trong hệ thống</p>
                <a href="<?php echo BASE_URL; ?>/admin/categories/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Thêm loại thuốc đầu tiên
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tên loại thuốc</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['id']); ?></td>
                                <td>
                                    <i class="bi bi-tag-fill me-2 text-primary"></i>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo formatDate($category['created_at'], DATETIME_FORMAT); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/categories/edit.php?id=<?php echo $category['id']; ?>"
                                           class="btn btn-outline-warning"
                                           title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/categories/delete.php?id=<?php echo $category['id']; ?>"
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa loại thuốc <?php echo htmlspecialchars($category['name']); ?>? Hành động này không thể hoàn tác!');"
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
