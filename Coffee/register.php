<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $old = compact('full_name', 'email', 'phone', 'address');

    // Validation
    if (empty($full_name)) {
        $errors['full_name'] = 'Vui lòng nhập họ tên';
    }

    if (empty($email)) {
        $errors['email'] = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email đã được sử dụng';
        }
    }

    if (empty($phone)) {
        $errors['phone'] = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors['phone'] = 'Số điện thoại không hợp lệ';
    }

    if (empty($password)) {
        $errors['password'] = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }

    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Mật khẩu xác nhận không khớp';
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO customers (email, password, full_name, phone, address, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$email, $hashed_password, $full_name, $phone, $address]);

            setMessage('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            header('Location: ' . BASE_URL . '/login.php?type=customer');
            exit;
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Coffee Manager</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="globals.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        body {
            background-color: var(--color-beige);
            background-image: radial-gradient(circle at 20% 20%, rgba(198, 168, 124, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 80% 80%, rgba(44, 24, 16, 0.03) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-card {
            background: white;
            width: 100%;
            max-width: 800px;
            border-radius: 30px;
            box-shadow: var(--shadow-lg);
            padding: 3.5rem;
            position: relative;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            color: var(--color-coffee-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            background: var(--color-beige);
            border-radius: var(--radius-pill);
            transition: all 0.3s;
            font-size: 0.9rem;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .back-btn:hover {
            background: var(--color-coffee-dark);
            color: white;
            transform: translateX(-5px);
        }
        
        .form-label-premium {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-text-muted);
            margin-bottom: 0.6rem;
            display: block;
        }
        
        .input-group-premium {
            background: var(--color-beige);
            border-radius: 12px;
            transition: all 0.3s;
            border: 1px solid transparent;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .input-group-premium:focus-within {
            background: white;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 4px rgba(198, 168, 124, 0.1);
        }
        
        .input-group-premium .input-group-text {
            background: transparent;
            border: none;
            padding-left: 1.2rem;
            color: var(--color-coffee-light);
            font-size: 1.1rem;
        }
        
        .input-group-premium .form-control {
            background: transparent;
            border: none;
            padding: 1rem 1.2rem 1rem 0.5rem;
            font-size: 1rem;
            color: var(--color-coffee-dark);
        }

        .input-group-premium .form-control::placeholder {
            color: #A0A0A0;
            font-weight: 400;
        }

        .input-group-premium .form-control:focus {
            box-shadow: none;
        }

        .btn-register {
            background: #BFA27E; /* Custom gold/tan color from image */
            color: white;
            border: none;
            border-radius: var(--radius-pill);
            padding: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 2rem;
            width: 100%;
            box-shadow: 0 10px 20px rgba(191, 162, 126, 0.2);
        }

        .btn-register:hover {
            background: var(--color-coffee-dark);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(44, 24, 16, 0.2);
            color: white;
        }

        .login-link {
            color: var(--color-coffee-dark);
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .login-link:hover {
            color: var(--color-gold);
        }

        @media (max-width: 768px) {
            .register-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            .back-btn {
                position: static;
                margin-bottom: 2rem;
                display: inline-flex;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <a href="index.php" class="back-btn shadow-sm">
            <i class="bi bi-arrow-left"></i> Trang chủ
        </a>

        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold mb-2">Đăng Ký Tài Khoản</h1>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 p-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Full Name -->
            <div class="mb-4">
                <label class="form-label-premium">Họ và tên <span class="text-danger">*</span></label>
                <div class="input-group-premium <?php echo isset($errors['full_name']) ? 'border-danger' : ''; ?>">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" name="full_name" 
                           value="<?php echo htmlspecialchars($old['full_name'] ?? ''); ?>" placeholder="Nhập họ và tên của bạn">
                </div>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="text-danger small mt-2 ms-1"><?php echo htmlspecialchars($errors['full_name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <!-- Email -->
                <div class="col-md-6 mb-4">
                    <label class="form-label-premium">Email <span class="text-danger">*</span></label>
                    <div class="input-group-premium <?php echo isset($errors['email']) ? 'border-danger' : ''; ?>">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" 
                               placeholder="Nhập địa chỉ email">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="text-danger small mt-2 ms-1"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>
                <!-- Phone -->
                <div class="col-md-6 mb-4">
                    <label class="form-label-premium">Số điện thoại <span class="text-danger">*</span></label>
                    <div class="input-group-premium <?php echo isset($errors['phone']) ? 'border-danger' : ''; ?>">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>" 
                               placeholder="Nhập số điện thoại">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="text-danger small mt-2 ms-1"><?php echo htmlspecialchars($errors['phone']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="form-label-premium">Địa chỉ</label>
                <div class="input-group-premium">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <input type="text" class="form-control" name="address"
                           value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>" placeholder="Số nhà, đường, phường/xã...">
                </div>
            </div>

            <div class="row">
                <!-- Password -->
                <div class="col-md-6 mb-4">
                    <label class="form-label-premium">Mật khẩu <span class="text-danger">*</span></label>
                    <div class="input-group-premium <?php echo isset($errors['password']) ? 'border-danger' : ''; ?>">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Nhập ít nhất 6 ký tự">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="text-danger small mt-2 ms-1"><?php echo htmlspecialchars($errors['password']); ?></div>
                    <?php endif; ?>
                </div>
                <!-- Confirm Password -->
                <div class="col-md-6 mb-4">
                    <label class="form-label-premium">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <div class="input-group-premium <?php echo isset($errors['password_confirm']) ? 'border-danger' : ''; ?>">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="password_confirm" placeholder="Xác nhận lại mật khẩu">
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="text-danger small mt-2 ms-1"><?php echo htmlspecialchars($errors['password_confirm']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-register shadow-sm mb-4">
                <i class="bi bi-person-plus-fill"></i> ĐĂNG KÝ NGAY
            </button>

            <div class="text-center">
                <span class="text-muted">Đã có tài khoản?</span>
                <a href="login.php?type=customer" class="login-link ms-1">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
