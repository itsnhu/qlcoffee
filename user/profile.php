<?php
$pageTitle = 'Tài khoản của tôi';
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/profile.php';
    setMessage('warning', 'Vui lòng đăng nhập');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($fullName)) {
            $errors['full_name'] = 'Vui lòng nhập họ tên';
        }

        if (empty($errors)) {
            executeQuery($pdo, "
                UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE id = ?
            ", [$fullName, $phone, $address, $customerId]);

            $_SESSION['customer_name'] = $fullName;
            setMessage('success', 'Cập nhật thông tin thành công');
            header('Location: ' . BASE_URL . '/user/profile.php');
            exit;
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $errors['current_password'] = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!password_verify($currentPassword, $customer['password'])) {
            $errors['current_password'] = 'Mật khẩu hiện tại không đúng';
        }

        if (empty($newPassword)) {
            $errors['new_password'] = 'Vui lòng nhập mật khẩu mới';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            executeQuery($pdo, "UPDATE customers SET password = ? WHERE id = ?", [$hashedPassword, $customerId]);

            setMessage('success', 'Đổi mật khẩu thành công');
            header('Location: ' . BASE_URL . '/user/profile.php');
            exit;
        }
    }

    // Refresh customer data
    $customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);
}

// Get order stats
$orderStats = fetchOne($pdo, "
    SELECT
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_spent,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
    FROM orders WHERE customer_id = ?
", [$customerId]);
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-person-circle me-2"></i>Tài khoản của tôi</h2>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Profile Summary -->
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-person fs-1"></i>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($customer['full_name']) ?></h5>
                    <p class="text-muted mb-0"><?= htmlspecialchars($customer['email']) ?></p>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="h4 mb-0 text-primary"><?= $orderStats['total_orders'] ?></div>
                            <small class="text-muted">Đơn hàng</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0 text-success"><?= $orderStats['completed_orders'] ?></div>
                            <small class="text-muted">Hoàn thành</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>/user/orders.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam me-2"></i>Đơn hàng của tôi
                        <?php if ($orderStats['pending_orders'] > 0): ?>
                            <span class="badge bg-warning float-end"><?= $orderStats['pending_orders'] ?> chờ xử lý</span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>/user/cart.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cart me-2"></i>Giỏ hàng
                    </a>
                    <a href="<?= BASE_URL ?>/user/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Update Profile -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i>Thông tin cá nhân
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       name="full_name" value="<?= htmlspecialchars($customer['full_name']) ?>">
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= $errors['full_name'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
                                <small class="text-muted">Email không thể thay đổi</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày đăng ký</label>
                                <input type="text" class="form-control" value="<?= formatDate($customer['created_at'], DATETIME_FORMAT) ?>" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($customer['address']) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Cập nhật thông tin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <i class="bi bi-shield-lock me-2"></i>Đổi mật khẩu
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                                   name="current_password">
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['current_password'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                       name="new_password">
                                <?php if (isset($errors['new_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['new_password'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                       name="confirm_password">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-2"></i>Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
