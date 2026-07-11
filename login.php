<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/employee/tables/index.php');
    }
}
if (isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$errors = [];
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input)) {
        $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email.';
    }
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (empty($errors)) {
        try {
            // 1. Thử đăng nhập Nhân viên / Admin (Bảng users)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();

                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                if ($user['role'] === 'admin') {
                    redirect('/admin/dashboard.php');
                } else {
                    redirect('/employee/tables/index.php');
                }
            }

            // 2. Thử đăng nhập Khách hàng (Bảng customers)
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE (email = ? OR phone = ?) AND is_active = 1");
            $stmt->execute([$login_input, $login_input]);
            $customer = $stmt->fetch();

            if ($customer && password_verify($password, $customer['password'])) {
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_name'] = $customer['full_name'];
                $_SESSION['customer_login_time'] = time();

                $redirect = $_SESSION['redirect_after_login'] ?? '/';
                if (strpos($redirect, 'http') !== 0) {
                    $redirect = BASE_URL . ($redirect[0] === '/' ? $redirect : '/' . $redirect);
                }
                unset($_SESSION['redirect_after_login']);

                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Thông tin đăng nhập hoặc mật khẩu không đúng.';
            }
        } catch (PDOException $e) {
            error_log("Unified Login Error: " . $e->getMessage());
            $errors[] = 'Đã xảy ra lỗi. Vui lòng thử lại.';
        }
    }
}

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TNT Coffee</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="globals.css">
    
    <style>
        :root {
            --primary-coffee: #6F4E37;
            --secondary-coffee: #8D6E63;
            --cream: #F5F5DC;
            --dark-coffee: #3E2723;
        }
        body {
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 40px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0,0,0,0.05);
            min-height: 540px;
        }
        .login-banner {
            flex: 1;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            color: white;
            position: relative;
        }
        .login-banner::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(62, 39, 35, 0.2);
            z-index: 1;
        }
        .banner-content {
            position: relative;
            z-index: 2;
        }
        .login-form-section {
            width: 420px;
            padding: 2.5rem 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .brand-logo {
            font-size: 2.5rem;
            color: var(--primary-coffee);
            margin-bottom: 2rem;
        }
        .input-premium {
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            padding: 14px 20px;
            width: 100%;
            transition: all 0.3s;
            outline: none;
            color: var(--dark-coffee);
        }
        .input-premium:focus {
            background: white;
            border-color: var(--secondary-coffee);
            box-shadow: 0 0 0 4px rgba(141, 110, 99, 0.1);
        }
        .btn-premium {
            background: var(--primary-coffee);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px;
            width: 100%;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .btn-premium:hover {
            background: var(--dark-coffee);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(62, 39, 35, 0.3);
        }
        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%; left: 0; right: 0;
            height: 1px;
            background: #e2e8f0;
            z-index: 1;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            color: #94a3b8;
            font-size: 0.85rem;
            position: relative;
            z-index: 2;
        }
        @media (max-width: 992px) {
            .login-banner { display: none; }
            .login-form-section { width: 100%; padding: 3rem; }
            .login-container { max-width: 500px; border-radius: 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-banner">
            <div class="banner-content">
                <h1 class="display-3 fw-bold mb-4 text-white">TNT Coffee</h1>
                <p class="lead opacity-90 mb-0">Hương vị cà phê truyền thống hòa quyện cùng phong cách hiện đại.</p>
            </div>
        </div>
        
        <div class="login-form-section">
            <div class="mb-4">
                <h2 class="fw-bold text-dark mb-1">Đăng Nhập</h2>
                <p class="text-muted small">Chào mừng bạn trở lại hệ thống!</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 small mb-4 py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?><li><?= $error ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message['type'] ?> border-0 rounded-4 small mb-4 py-2">
                    <?= $message['text'] ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase ms-2 mb-1">Tài khoản hoặc Email</label>
                    <input type="text" name="login_input" class="input-premium" required placeholder="Tên đăng nhập / Email" value="<?= htmlspecialchars($login_input) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase ms-2 mb-1">Mật khẩu</label>
                    <input type="password" name="password" class="input-premium" required placeholder="••••••••">
                    <div class="text-end mt-2">
                        <a href="forgot_password.php" class="small text-decoration-none text-muted">Quên mật khẩu?</a>
                    </div>
                </div>
                <button type="submit" class="btn-premium">ĐĂNG NHẬP NGAY</button>
            </form>

            <div class="divider">
                <span>Bạn là khách hàng mới?</span>
            </div>

            <div class="text-center">
                <a href="register.php" class="btn btn-outline-light text-dark fw-bold border-2 rounded-4 py-2 px-4 w-100">
                    TẠO TÀI KHOẢN MỚI
                </a>
            </div>

            <div class="mt-4 pt-2 text-center border-top">
                <a href="index.php" class="text-decoration-none text-muted small"><i class="bi bi-house-door me-1"></i> Quay lại Trang chủ</a>
            </div>
        </div>
    </div>
</body>
</html>

