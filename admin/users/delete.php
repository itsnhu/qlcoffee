<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
    redirect('/admin/users/index.php');
}


$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($userId == $_SESSION['user_id']) {
    setMessage('danger', 'Bạn không thể xóa tài khoản của chính mình!');
    redirect('/admin/users/index.php');
}


try {
    $sql = "SELECT id, username, full_name, role FROM users WHERE id = ?";
    $user = fetchOne($pdo, $sql, [$userId]);

    if (!$user) {
        setMessage('danger', 'Người dùng không tồn tại.');
        redirect('/admin/users/index.php');
    }
} catch (PDOException $e) {
    error_log("Get User Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin người dùng.');
    redirect('/admin/users/index.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

    if (!$confirmed) {
        setMessage('warning', 'Bạn chưa xác nhận xóa người dùng.');
        redirect('/admin/users/index.php');
    }

    try {
        
        $pdo->beginTransaction();

        
        
        $invoiceCount = fetchOne($pdo, "SELECT COUNT(*) as count FROM invoices WHERE user_id = ?", [$userId])['count'];

        
        $importCount = fetchOne($pdo, "SELECT COUNT(*) as count FROM imports WHERE user_id = ?", [$userId])['count'];

        
        if ($invoiceCount > 0 || $importCount > 0) {
            
            $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
            executeQuery($pdo, $sql, [$userId]);

            $pdo->commit();

            setMessage('warning', sprintf(
                'Không thể xóa người dùng "%s" vì đã có %d hóa đơn và %d phiếu nhập liên quan. Tài khoản đã được vô hiệu hóa thay thế.',
                htmlspecialchars($user['username']),
                $invoiceCount,
                $importCount
            ));
            redirect('/admin/users/index.php');
        }

        
        $sql = "DELETE FROM users WHERE id = ?";
        executeQuery($pdo, $sql, [$userId]);

        
        $pdo->commit();

        setMessage('success', sprintf(
            'Đã xóa người dùng "%s" thành công!',
            htmlspecialchars($user['username'])
        ));
        redirect('/admin/users/index.php');
    } catch (PDOException $e) {
        
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Delete User Error: " . $e->getMessage());
        setMessage('danger', 'Có lỗi khi xóa người dùng. Vui lòng thử lại.');
        redirect('/admin/users/index.php');
    }
}


$pageTitle = 'Xóa người dùng';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>/admin/users/index.php">Người dùng</a>
                </li>
                <li class="breadcrumb-item active">Xóa</li>
            </ol>
        </nav>
        <h2 class="mb-2">
            <i class="bi bi-trash3 text-danger"></i>
            Xóa người dùng
        </h2>
        <p class="text-muted mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Xác nhận xóa người dùng khỏi hệ thống
        </p>
    </div>
</div>

<!-- Confirmation Form -->
<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Xác nhận xóa người dùng</strong>
            </div>
            <div class="card-body">
                <!-- Warning Alert -->
                <div class="alert alert-danger mb-4">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-octagon-fill fs-2 me-3"></i>
                        <div>
                            <h5 class="alert-heading">Cảnh báo!</h5>
                            <p class="mb-0">
                                Hành động này sẽ xóa vĩnh viễn người dùng khỏi hệ thống và không thể hoàn tác.
                                Nếu người dùng có dữ liệu liên quan (hóa đơn, phiếu nhập), tài khoản sẽ được vô hiệu hóa thay vì xóa.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- User Info -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-danger mb-3">
                            <i class="bi bi-person-x-fill me-2"></i>
                            Thông tin người dùng sẽ xóa:
                        </h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td width="150" class="text-muted">Tên đăng nhập:</td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Họ và tên:</td>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Vai trò:</td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Quản trị viên</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Nhân viên</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" action="" id="deleteUserForm">
                    <input type="hidden" name="confirm" value="yes">

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                        <label class="form-check-label" for="confirmCheck">
                            Tôi hiểu rằng hành động này không thể hoàn tác và xác nhận muốn xóa người dùng này.
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Hủy bỏ
                        </a>
                        <button type="submit" class="btn btn-danger" id="deleteButton" disabled>
                            <i class="bi bi-trash3 me-2"></i>
                            Xóa người dùng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Lưu ý:</strong> Nếu người dùng này đã tạo hóa đơn hoặc phiếu nhập hàng,
            hệ thống sẽ tự động vô hiệu hóa tài khoản thay vì xóa để bảo toàn tính toàn vẹn dữ liệu.
        </div>
    </div>
</div>

<!-- JavaScript for form validation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmCheck');
    const deleteButton = document.getElementById('deleteButton');
    const deleteForm = document.getElementById('deleteUserForm');

    // Enable/disable delete button based on checkbox
    if (confirmCheck && deleteButton) {
        confirmCheck.addEventListener('change', function() {
            deleteButton.disabled = !this.checked;
        });
    }

    // Confirm before submit
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác!')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
