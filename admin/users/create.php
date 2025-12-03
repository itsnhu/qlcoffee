<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$errors = [];
$formData = [
    'username' => '',
    'full_name' => '',
    'role' => 'employee'
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['username'] = sanitize($_POST['username'] ?? '');
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['role'] = sanitize($_POST['role'] ?? 'employee');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    
    if (empty($formData['username'])) {
        $errors['username'] = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($formData['username']) < 3) {
        $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
        $errors['username'] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
    }

    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Vui lòng nhập họ và tên';
    } elseif (strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Họ và tên phải có ít nhất 3 ký tự';
    }

    if (empty($password)) {
        $errors['password'] = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }

    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
    }

    if (!in_array($formData['role'], ['admin', 'employee'])) {
        $errors['role'] = 'Vai trò không hợp lệ';
    }

    
    if (empty($errors['username'])) {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
            $result = fetchOne($pdo, $sql, [$formData['username']]);
            if ($result['count'] > 0) {
                $errors['username'] = 'Tên đăng nhập đã tồn tại';
            }
        } catch (PDOException $e) {
            error_log("Check Username Error: " . $e->getMessage());
            $errors['username'] = 'Có lỗi khi kiểm tra tên đăng nhập';
        }
    }

    
    if (empty($errors)) {
        try {
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            
            $sql = "INSERT INTO users (username, password, full_name, role, created_at, is_active)
                    VALUES (?, ?, ?, ?, NOW(), 1)";
            $params = [
                $formData['username'],
                $hashedPassword,
                $formData['full_name'],
                $formData['role']
            ];

            executeQuery($pdo, $sql, $params);

            
            setMessage('success', 'Thêm người dùng thành công!');
            redirect('/admin/users/index.php');
        } catch (PDOException $e) {
            error_log("Create User Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi khi thêm người dùng. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Thêm người dùng mới';
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
                <li class="breadcrumb-item active">Thêm mới</li>
            </ol>
        </nav>
        <h2 class="mb-2">
            <i class="bi bi-person-plus-fill text-primary"></i>
            Thêm người dùng mới
        </h2>
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Điền thông tin để tạo tài khoản người dùng mới
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

<!-- Create User Form -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pencil-square me-2"></i>
                <strong>Thông tin người dùng</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="createUserForm">
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Tên đăng nhập <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   id="username"
                                   name="username"
                                   value="<?php echo htmlspecialchars($formData['username']); ?>"
                                   placeholder="Nhập tên đăng nhập"
                                   required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['username']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Chỉ sử dụng chữ cái, số và dấu gạch dưới. Tối thiểu 3 ký tự.
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

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Mật khẩu <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   name="password"
                                   placeholder="Nhập mật khẩu"
                                   required>
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
                            Mật khẩu phải có ít nhất 6 ký tự.
                        </small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            Xác nhận mật khẩu <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                   id="confirm_password"
                                   name="confirm_password"
                                   placeholder="Nhập lại mật khẩu"
                                   required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="mb-4">
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
                                    required>
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
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Quản trị viên có toàn quyền truy cập hệ thống. Nhân viên chỉ có quyền hạn chế.
                        </small>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            Thêm người dùng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for password toggle -->
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
    const form = document.getElementById('createUserForm');
    const confirmPassword = document.getElementById('confirm_password');

    if (form) {
        form.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
                const feedback = confirmPassword.parentElement.querySelector('.invalid-feedback');
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
            if (this.value === password.value) {
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
