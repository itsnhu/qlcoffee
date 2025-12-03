<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $sql = "SELECT id, username, full_name, role, created_at, is_active
            FROM users
            ORDER BY created_at DESC";
    $users = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("User List Error: " . $e->getMessage());
    $users = [];
    setMessage('danger', 'Có lỗi khi tải danh sách người dùng.');
}

$pageTitle = 'Quản lý người dùng';
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
                    <i class="bi bi-people-fill text-primary"></i>
                    Quản lý người dùng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý tài khoản và phân quyền người dùng hệ thống
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Thêm người dùng mới
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách người dùng</strong>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có người dùng nào trong hệ thống</p>
                <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Thêm người dùng đầu tiên
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="usersTable" class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ và tên</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <i class="bi bi-person-circle me-2 text-primary"></i>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-shield-fill-check me-1"></i>
                                            Quản trị viên
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-person-badge me-1"></i>
                                            Nhân viên
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Vô hiệu hóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($user['created_at'], DATETIME_FORMAT); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $user['id']; ?>"
                                           class="btn btn-outline-warning"
                                           title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="<?php echo BASE_URL; ?>/admin/users/delete.php?id=<?php echo $user['id']; ?>"
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng <?php echo htmlspecialchars($user['username']); ?>? Hành động này không thể hoàn tác!');"
                                               title="Xóa">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" disabled title="Không thể xóa chính mình">
                                                <i class="bi bi-lock-fill"></i>
                                            </button>
                                        <?php endif; ?>
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
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
        columnDefs: [
            { orderable: false, targets: 6 }
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
