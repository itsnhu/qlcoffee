<?php

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    setMessage('danger', 'ID khách hàng không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

// Get customer details
try {
    $customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$id]);

    if (!$customer) {
        setMessage('danger', 'Không tìm thấy khách hàng.');
        header('Location: ' . BASE_URL . '/admin/customers/index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Load Customer Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin khách hàng.');
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = trim($_POST['new_password'] ?? '');

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Họ và tên không được để trống.';
    }

    if (empty($email)) {
        $errors[] = 'Email không được để trống.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    } else {
        // Check if email exists for another customer
        $existingEmail = fetchOne($pdo, "SELECT id FROM customers WHERE email = ? AND id != ?", [$email, $id]);
        if ($existingEmail) {
            $errors[] = 'Email đã được sử dụng bởi khách hàng khác.';
        }
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = 'Số điện thoại không hợp lệ (10-11 số).';
    }

    if (!empty($new_password) && strlen($new_password) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }

    // Update if no errors
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $sql = "UPDATE customers
                        SET full_name = ?, email = ?, phone = ?, address = ?, is_active = ?, password = ?
                        WHERE id = ?";
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $params = [$full_name, $email, $phone ?: null, $address ?: null, $is_active, $hashedPassword, $id];
            } else {
                // Update without password
                $sql = "UPDATE customers
                        SET full_name = ?, email = ?, phone = ?, address = ?, is_active = ?
                        WHERE id = ?";
                $params = [$full_name, $email, $phone ?: null, $address ?: null, $is_active, $id];
            }

            executeQuery($pdo, $sql, $params);

            setMessage('success', 'Cập nhật thông tin khách hàng thành công!');
            header('Location: ' . BASE_URL . '/admin/customers/index.php');
            exit;

        } catch (PDOException $e) {
            error_log("Update Customer Error: " . $e->getMessage());
            $errors[] = 'Có lỗi khi cập nhật thông tin. Vui lòng thử lại.';
        }
    }

    // Keep form values if there are errors
    if (!empty($errors)) {
        $customer['full_name'] = $full_name;
        $customer['email'] = $email;
        $customer['phone'] = $phone;
        $customer['address'] = $address;
        $customer['is_active'] = $is_active;
    }
}

$pageTitle = 'Chỉnh sửa khách hàng: ' . $customer['full_name'];
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Display errors -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Có lỗi xảy ra:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-pencil-square text-primary"></i>
                    Chỉnh sửa khách hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Cập nhật thông tin khách hàng: <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/customers/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form -->
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-gear me-2"></i>
                <strong>Thông tin khách hàng</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="customerForm">
                    <!-- Full name -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">
                            Họ và tên <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="full_name"
                               name="full_name"
                               value="<?php echo htmlspecialchars($customer['full_name']); ?>"
                               required
                               placeholder="Nhập họ và tên">
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               value="<?php echo htmlspecialchars($customer['email']); ?>"
                               required
                               placeholder="Nhập địa chỉ email">
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel"
                               class="form-control"
                               id="phone"
                               name="phone"
                               value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                               placeholder="Nhập số điện thoại">
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <textarea class="form-control"
                                  id="address"
                                  name="address"
                                  rows="3"
                                  placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   <?php echo $customer['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Tài khoản hoạt động
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> Tắt để vô hiệu hóa tài khoản khách hàng
                        </small>
                    </div>

                    <hr>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input type="password"
                               class="form-control"
                               id="new_password"
                               name="new_password"
                               placeholder="Để trống nếu không đổi mật khẩu">
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> Chỉ nhập nếu muốn thay đổi mật khẩu (tối thiểu 6 ký tự)
                        </small>
                    </div>

                    <!-- Info -->
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Thông tin:</strong> Tài khoản được tạo vào <?php echo formatDate($customer['created_at'], DATETIME_FORMAT); ?>
                            <?php if ($customer['updated_at'] != $customer['created_at']): ?>
                                - Cập nhật lần cuối: <?php echo formatDate($customer['updated_at'], DATETIME_FORMAT); ?>
                            <?php endif; ?>
                        </small>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?php echo BASE_URL; ?>/admin/customers/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>
                            Hủy bỏ
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
