<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

// Auto-migration
try {
    $cols = array_column(fetchAll($pdo, "SHOW COLUMNS FROM users"), 'Field');
    if (!in_array('gender', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('Nam','Nữ','Khác') DEFAULT 'Nam' AFTER full_name");
    if (!in_array('birth_date', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN birth_date DATE NULL AFTER gender");
    if (!in_array('address', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL AFTER birth_date");
    if (!in_array('phone', $cols)) $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER full_name");
} catch (PDOException $e) { error_log("Migration: " . $e->getMessage()); }

$user = fetchOne($pdo, "SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Nam';
    $birth_date = $_POST['birth_date'] ?? null;
    $address = sanitize($_POST['address'] ?? '');

    if (empty($full_name)) {
        setMessage('danger', 'Vui lòng nhập họ tên!');
    } else {
        executeQuery($pdo, "UPDATE users SET full_name=?, phone=?, gender=?, birth_date=?, address=? WHERE id=?",
            [$full_name, $phone, $gender, $birth_date ?: null, $address, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $full_name;
        setMessage('success', 'Cập nhật thông tin thành công!');
        redirect('/employee/settings/profile.php');
    }
}

$pageTitle = 'Thông tin tài khoản';
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
    <div class="col-lg-6 col-md-8">
        <div style="background:white;border-radius:16px;border:1px solid rgba(0,0,0,0.05);box-shadow:0 4px 20px rgba(0,0,0,0.04);overflow:hidden;">
            <div style="background:#f8fafc;border-bottom:1px solid #f1f5f9;padding:1.25rem 1.5rem;">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard text-primary me-2"></i>Thông tin tài khoản</h6>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tên đăng nhập</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled
                               style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#eef2f7;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Vai trò</label>
                        <input type="text" class="form-control" value="<?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?>" disabled
                               style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#eef2f7;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required
                               style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Giới tính</label>
                            <select name="gender" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;">
                                <option value="Nam" <?= ($user['gender'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="Nữ" <?= ($user['gender'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                <option value="Khác" <?= ($user['gender'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Ngày sinh</label>
                            <input type="date" name="birth_date" class="form-control" value="<?= $user['birth_date'] ?? '' ?>"
                                   style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09xxxxxxxx"
                               style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Địa chỉ</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Hà Nội..."
                               style="border-radius:10px;border:1px solid #e2e8f0;padding:0.7rem 1rem;background:#f8fafc;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius:10px;padding:0.8rem;">
                        <i class="bi bi-check-lg me-2"></i>Lưu thông tin
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
