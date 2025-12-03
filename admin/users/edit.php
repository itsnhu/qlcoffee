<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$errors = [];
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;


try {
    $sql = "SELECT * FROM users WHERE id = ?";
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

$formData = [
    'username' => $user['username'],
    'full_name' => $user['full_name'],
    'role' => $user['role'],
    'is_active' => $user['is_active']
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['role'] = sanitize($_POST['role'] ?? 'employee');
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Vui lòng nhập họ và tên';
    } elseif (strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Họ và tên phải có ít nhất 3 ký tự';
    }

    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
        }
    }

    if (!in_array($formData['role'], ['admin', 'employee'])) {
        $errors['role'] = 'Vai trò không hợp lệ';
    }

    
    if ($userId == $_SESSION['user_id'] && $formData['is_active'] == 0) {
        $errors['is_active'] = 'Bạn không thể vô hiệu hóa tài khoản của chính mình';
    }

    
    if ($userId == $_SESSION['user_id'] && $_SESSION['role'] === 'admin' && $formData['role'] !== 'admin') {
        $errors['role'] = 'Bạn không thể thay đổi vai trò của chính mình';
    }

    
    if (empty($errors)) {
        try {
            
            if (!empty($password)) {
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users
                        SET full_name = ?, password = ?, role = ?, is_active = ?
                        WHERE id = ?";
                $params = [
                    $formData['full_name'],
                    $hashedPassword,
                    $formData['role'],
                    $formData['is_active'],
                    $userId
                ];
            } else {
                
                $sql = "UPDATE users
                        SET full_name = ?, role = ?, is_active = ?
                        WHERE id = ?";
                $params = [
                    $formData['full_name'],
                    $formData['role'],
                    $formData['is_active'],
                    $userId
                ];
            }

            executeQuery($pdo, $sql, $params);

            
            if ($userId == $_SESSION['user_id']) {
                $_SESSION['full_name'] = $formData['full_name'];
                $_SESSION['role'] = $formData['role'];
            }

            
            setMessage('success', 'Cập nhật thông tin người dùng thành công!');
            redirect('/admin/users/index.php');
        } catch (PDOException $e) {
            error_log("Update User Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi khi cập nhật người dùng. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Chỉnh sửa người dùng';
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
                <li class="breadcrumb-item active">Chỉnh sửa</li>
            </ol>
        </nav>
        <h2 class="mb-2">
            <i class="bi bi-pencil-square text-warning"></i>
            Chỉnh sửa người dùng
        </h2>
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Cập nhật thông tin cho tài khoản: <strong><?php echo htmlspecialchars($user['username']); ?></strong>
        </p>
    </div>
</div>

<!-- Display general error -->
<?php if (isset($errors['general'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($errors['general']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Edit User Form -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-pencil-square me-2"></i>
                <strong>Thông tin người dùng</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="editUserForm">
                    <!-- Username (Read-only) -->
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Tên đăng nhập
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text"
                                   class="form-control"
                                   id="username"
                                   value="<?php echo htmlspecialchars($formData['username']); ?>"
                                   readonly>
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill text-warning" title="Không thể sửa"></i>
                            </span>
                        </div>
                        <small class="form-text text-muted">
                            Tên đăng nhập không thể thay đổi.
                        </small>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">
                            Họ và tên <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                   id="full_name"
                                   name="full_name"
                                   value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                                   placeholder="Nhập họ và tên đầy đủ"
                                   required>
                            <?php if (isset($errors['full_name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['full_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password (Optional) -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Mật khẩu mới
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   name="password"
                                   placeholder="Để trống nếu không muốn đổi mật khẩu">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Để trống nếu không muốn thay đổi mật khẩu. Nếu đổi, mật khẩu phải có ít nhất 6 ký tự.
                        </small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            Xác nhận mật khẩu mới
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                   id="confirm_password"
                                   name="confirm_password"
                                   placeholder="Nhập lại mật khẩu mới">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="mb-3">
                        <label for="role" class="form-label">
                            Vai trò <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-shield-check"></i>
                            </span>
                            <select class="form-select <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>"
                                    id="role"
                                    name="role"
                                    required
                                    <?php echo ($userId == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                <option value="employee" <?php echo $formData['role'] === 'employee' ? 'selected' : ''; ?>>
                                    Nhân viên
                                </option>
                                <option value="admin" <?php echo $formData['role'] === 'admin' ? 'selected' : ''; ?>>
                                    Quản trị viên
                                </option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['role']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($userId == $_SESSION['user_id']): ?>
                            <small class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Bạn không thể thay đổi vai trò của chính mình.
                            </small>
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($formData['role']); ?>">
                        <?php endif; ?>
                    </div>

                    <!-- Active Status -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input <?php echo isset($errors['is_active']) ? 'is-invalid' : ''; ?>"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   <?php echo $formData['is_active'] ? 'checked' : ''; ?>
                                   <?php echo ($userId == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <strong>Kích hoạt tài khoản</strong>
                            </label>
                            <?php if (isset($errors['is_active'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php echo htmlspecialchars($errors['is_active']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted ms-5">
                            Bỏ chọn để vô hiệu hóa tài khoản (người dùng sẽ không thể đăng nhập).
                        </small>
                        <?php if ($userId == $_SESSION['user_id']): ?>
                            <input type="hidden" name="is_active" value="1">
                            <small class="form-text text-warning d-block ms-5">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Bạn không thể vô hiệu hóa tài khoản của chính mình.
                            </small>
                        <?php endif; ?>
                    </div>

                    <!-- Additional Info -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Thông tin bổ sung:</strong><br>
                        <small>
                            <strong>Ngày tạo:</strong> <?php echo formatDate($user['created_at'], DATETIME_FORMAT); ?><br>
                            <strong>Đăng nhập lần cuối:</strong> <?php echo $user['last_login'] ? formatDate($user['last_login'], DATETIME_FORMAT) : 'Chưa đăng nhập'; ?>
                        </small>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-2"></i>
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for password toggle and validation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle icon
            if (type === 'password') {
                togglePasswordIcon.classList.remove('bi-eye-slash');
                togglePasswordIcon.classList.add('bi-eye');
            } else {
                togglePasswordIcon.classList.remove('bi-eye');
                togglePasswordIcon.classList.add('bi-eye-slash');
            }
        });
    }

    // Form validation
    const form = document.getElementById('editUserForm');
    const confirmPassword = document.getElementById('confirm_password');

    if (form) {
        form.addEventListener('submit', function(e) {
            // Chỉ validate password nếu người dùng nhập mật khẩu mới
            if (password.value && password.value !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
                let feedback = confirmPassword.parentElement.querySelector('.invalid-feedback');
                if (!feedback) {
                    const div = document.createElement('div');
                    div.className = 'invalid-feedback';
                    div.textContent = 'Mật khẩu xác nhận không khớp';
                    confirmPassword.parentElement.appendChild(div);
                }
            }
        });

        // Remove invalid class on input
        confirmPassword.addEventListener('input', function() {
            if (!password.value || this.value === password.value) {
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
