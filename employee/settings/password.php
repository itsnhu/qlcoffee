<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $user = fetchOne($pdo, "SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);

    if (!password_verify($current, $user['password'])) {
        setMessage('danger', 'Mật khẩu hiện tại không đúng!');
    } elseif (strlen($new) < 4) {
        setMessage('danger', 'Mật khẩu mới phải có ít nhất 4 ký tự!');
    } elseif ($new !== $confirm) {
        setMessage('danger', 'Xác nhận mật khẩu không khớp!');
    } else {
        executeQuery($pdo, "UPDATE users SET password = ? WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        setMessage('success', 'Đổi mật khẩu thành công!');
        redirect('/employee/settings/password.php');
    }
}

$pageTitle = 'Thay đổi mật khẩu';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<?php $message = getMessage(); if ($message): ?>
    <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($message['text']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div style="background:white;border-radius:16px;border:1px solid rgba(0,0,0,0.05);box-shadow:0 4px 20px rgba(0,0,0,0.04);overflow:hidden;">
            <div style="background:#f8fafc;border-bottom:1px solid #f1f5f9;padding:1.25rem 1.5rem;">
                <h6 class="fw-bold mb-0"><i class="bi bi-key text-primary me-2"></i>Thay đổi mật khẩu</h6>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;" required minlength="4">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius:10px;padding:0.8rem;">
                        <i class="bi bi-check-lg me-2"></i>Xác nhận đổi mật khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
