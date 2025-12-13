<?php

require_once 'config/config.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/employee/dashboard.php');
    }
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . '/user/');
    exit;
}

$errors = [];
$username = '';
$email = '';
$loginType = $_GET['type'] ?? 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'staff';

    if ($loginType === 'staff') {
        // Staff login
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username)) {
            $errors[] = 'Vui lòng nhập tên đăng nhập.';
        }
        if (empty($password)) {
            $errors[] = 'Vui lòng nhập mật khẩu.';
        }

        if (empty($errors)) {
            try {
                $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
                $user = fetchOne($pdo, $sql, [$username]);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();

                    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                    executeQuery($pdo, $updateSql, [$user['id']]);

                    setMessage('success', 'Đăng nhập thành công! Chào mừng ' . $user['full_name']);

                    if ($user['role'] === 'admin') {
                        redirect('/admin/dashboard.php');
                    } else {
                        redirect('/employee/dashboard.php');
                    }
                } else {
                    $errors[] = 'Tên đăng nhập hoặc mật khẩu không đúng.';
                }
            } catch (PDOException $e) {
                error_log("Login Error: " . $e->getMessage());
                $errors[] = 'Đã xảy ra lỗi. Vui lòng thử lại.';
            }
        }
    } else {
        // Customer login
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = 'Vui lòng nhập email.';
        }
        if (empty($password)) {
            $errors[] = 'Vui lòng nhập mật khẩu.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $customer = $stmt->fetch();

                if ($customer && password_verify($password, $customer['password'])) {
                    $_SESSION['customer_id'] = $customer['id'];
                    $_SESSION['customer_email'] = $customer['email'];
                    $_SESSION['customer_name'] = $customer['full_name'];
                    $_SESSION['customer_login_time'] = time();

                    $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/user/';
                    unset($_SESSION['redirect_after_login']);

                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $errors[] = 'Email hoặc mật khẩu không đúng.';
                }
            } catch (PDOException $e) {
                error_log("Customer Login Error: " . $e->getMessage());
                $errors[] = 'Đã xảy ra lỗi. Vui lòng thử lại.';
            }
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
    <title>Đăng nhập - PharmaManager</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💊</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-50: #f0fdfa;
            --primary-100: #ccfbf1;
            --primary-200: #99f6e4;
            --primary-400: #2dd4bf;
            --primary-500: #14b8a6;
            --primary-600: #0d9488;
            --primary-700: #0f766e;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 50%, #99f6e4 100%);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 150%;
            background: radial-gradient(ellipse at center, rgba(20, 184, 166, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .floating-pills {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .pill {
            position: absolute;
            font-size: 2rem;
            opacity: 0.12;
            animation: float 20s infinite ease-in-out;
        }

        .pill:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .pill:nth-child(2) { top: 20%; right: 15%; animation-delay: 3s; font-size: 1.5rem; }
        .pill:nth-child(3) { bottom: 30%; left: 5%; animation-delay: 6s; }
        .pill:nth-child(4) { top: 60%; right: 10%; animation-delay: 9s; font-size: 2.5rem; }
        .pill:nth-child(5) { bottom: 10%; right: 25%; animation-delay: 12s; }
        .pill:nth-child(6) { top: 40%; left: 15%; animation-delay: 15s; font-size: 1.8rem; }
        .pill:nth-child(7) { top: 75%; left: 20%; animation-delay: 4s; font-size: 1.3rem; }
        .pill:nth-child(8) { top: 5%; right: 30%; animation-delay: 8s; font-size: 2.2rem; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(20px) rotate(-5deg); }
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .login-branding {
            flex: 1;
            display: none;
            padding: 3rem;
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            color: white;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .login-branding { display: flex; }
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            max-width: 480px;
        }

        .branding-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .branding-logo i { font-size: 3.5rem; }
        .branding-logo span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2rem;
            font-weight: 800;
        }

        .branding-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .branding-desc {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .branding-features {
            list-style: none;
            padding: 0;
        }

        .branding-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            font-size: 1rem;
            opacity: 0.9;
        }

        .branding-features i {
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 460px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            padding: 2rem 2rem 0;
            text-align: center;
        }

        .login-header .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.3);
        }

        .login-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .login-header p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* Login Type Tabs */
        .login-tabs {
            display: flex;
            margin: 1.5rem 2rem 0;
            background: var(--gray-100);
            border-radius: 12px;
            padding: 4px;
        }

        .login-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-500);
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-tab:hover {
            color: var(--gray-700);
        }

        .login-tab.active {
            background: white;
            color: var(--primary-600);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .login-tab i {
            font-size: 1rem;
        }

        .login-body {
            padding: 1.5rem 2rem 2rem;
        }

        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert i { font-size: 1.1rem; margin-top: 1px; }

        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }

        .input-group { position: relative; }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
            z-index: 5;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-50);
            color: var(--gray-700);
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-500);
            background: white;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
        }

        .form-control:focus ~ .input-icon {
            color: var(--primary-600);
        }

        .form-control::placeholder { color: var(--gray-400); }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            z-index: 5;
            transition: color 0.2s;
        }

        .password-toggle:hover { color: var(--gray-600); }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }

        .demo-section {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--gray-200);
        }

        .demo-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.875rem;
            text-align: center;
        }

        .demo-accounts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.625rem;
        }

        .demo-account {
            padding: 0.75rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .demo-account:hover {
            background: var(--primary-50);
            border-color: var(--primary-400);
        }

        .demo-account .role {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--primary-600);
            margin-bottom: 0.125rem;
        }

        .demo-account .credentials {
            font-size: 0.7rem;
            color: var(--gray-600);
        }

        .demo-account .credentials code {
            background: white;
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
            font-size: 0.65rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .register-link a {
            color: var(--primary-600);
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            padding: 1.25rem;
            color: var(--gray-500);
            font-size: 0.8rem;
            border-top: 1px solid var(--gray-100);
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .back-home:hover {
            color: var(--primary-600);
        }

        @media (max-width: 480px) {
            .login-card { border-radius: 20px; }
            .login-header, .login-body { padding-left: 1.5rem; padding-right: 1.5rem; }
            .login-tabs { margin-left: 1.5rem; margin-right: 1.5rem; }
            .demo-accounts { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="floating-pills">
        <div class="pill">💊</div>
        <div class="pill">💉</div>
        <div class="pill">🩺</div>
        <div class="pill">💊</div>
        <div class="pill">🏥</div>
        <div class="pill">💊</div>
        <div class="pill">🩹</div>
        <div class="pill">💉</div>
    </div>

    <div class="login-wrapper">
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <i class="bi bi-capsule-pill"></i>
                    <span>PharmaManager</span>
                </div>
                <h2 class="branding-title">Nhà thuốc trực tuyến uy tín & chuyên nghiệp</h2>
                <p class="branding-desc">
                    Hệ thống quản lý nhà thuốc hiện đại, kết hợp bán hàng trực tuyến.
                    Đa dạng thuốc chính hãng với giá cả hợp lý.
                </p>
                <ul class="branding-features">
                    <li><i class="bi bi-check"></i>Thuốc chính hãng, nguồn gốc rõ ràng</li>
                    <li><i class="bi bi-check"></i>Giao hàng nhanh, thanh toán COD</li>
                    <li><i class="bi bi-check"></i>Tư vấn dược sĩ chuyên nghiệp</li>
                    <li><i class="bi bi-check"></i>Quản lý đơn hàng tiện lợi</li>
                </ul>
            </div>
        </div>

        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <a href="<?= BASE_URL ?>/user/" class="back-home">
                        <i class="bi bi-arrow-left"></i>Về trang chủ
                    </a>
                    <div class="logo-icon">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <h1>Chào mừng!</h1>
                    <p>Đăng nhập để tiếp tục</p>
                </div>

                <div class="login-tabs">
                    <button type="button" class="login-tab <?= $loginType === 'customer' ? 'active' : '' ?>" onclick="switchTab('customer')">
                        <i class="bi bi-person"></i>Khách hàng
                    </button>
                    <button type="button" class="login-tab <?= $loginType === 'staff' ? 'active' : '' ?>" onclick="switchTab('staff')">
                        <i class="bi bi-person-badge"></i>Nhân viên
                    </button>
                </div>

                <div class="login-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message['type'] ?>">
                            <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
                            <div><?= htmlspecialchars($message['text']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Customer Login Form -->
                    <div class="form-panel <?= $loginType === 'customer' ? 'active' : '' ?>" id="customer-panel">
                        <form method="POST" action="">
                            <input type="hidden" name="login_type" value="customer">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <input type="email" name="email" class="form-control" placeholder="Nhập địa chỉ email" value="<?= htmlspecialchars($email) ?>" required>
                                    <i class="bi bi-envelope input-icon"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="customer-password" class="form-control" placeholder="Nhập mật khẩu" required>
                                    <i class="bi bi-lock input-icon"></i>
                                    <button type="button" class="password-toggle" onclick="togglePassword('customer-password', 'customer-toggle')">
                                        <i class="bi bi-eye" id="customer-toggle"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn-login">
                                <i class="bi bi-box-arrow-in-right"></i>Đăng nhập
                            </button>
                        </form>

                        <div class="register-link">
                            Chưa có tài khoản? <a href="<?= BASE_URL ?>/register.php">Đăng ký ngay</a>
                        </div>

                        <div class="demo-section">
                            <div class="demo-title">Tài khoản demo</div>
                            <div class="demo-accounts">
                                <div class="demo-account" onclick="fillCustomerDemo('khachhang@gmail.com', 'admin123')">
                                    <div class="role">👤 Khách hàng</div>
                                    <div class="credentials">
                                        <code>khachhang@gmail.com</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Login Form -->
                    <div class="form-panel <?= $loginType === 'staff' ? 'active' : '' ?>" id="staff-panel">
                        <form method="POST" action="">
                            <input type="hidden" name="login_type" value="staff">
                            <div class="form-group">
                                <label class="form-label">Tên đăng nhập</label>
                                <div class="input-group">
                                    <input type="text" name="username" class="form-control" placeholder="Nhập tên đăng nhập" value="<?= htmlspecialchars($username) ?>" required>
                                    <i class="bi bi-person input-icon"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="staff-password" class="form-control" placeholder="Nhập mật khẩu" required>
                                    <i class="bi bi-lock input-icon"></i>
                                    <button type="button" class="password-toggle" onclick="togglePassword('staff-password', 'staff-toggle')">
                                        <i class="bi bi-eye" id="staff-toggle"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn-login">
                                <i class="bi bi-box-arrow-in-right"></i>Đăng nhập
                            </button>
                        </form>

                        <div class="demo-section">
                            <div class="demo-title">Tài khoản demo</div>
                            <div class="demo-accounts">
                                <div class="demo-account" onclick="fillStaffDemo('admin', 'admin123')">
                                    <div class="role">👨‍💼 Admin</div>
                                    <div class="credentials"><code>admin</code> / <code>admin123</code></div>
                                </div>
                                <div class="demo-account" onclick="fillStaffDemo('nhanvien', 'admin123')">
                                    <div class="role">👩‍⚕️ Nhân viên</div>
                                    <div class="credentials"><code>nhanvien</code> / <code>admin123</code></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="login-footer">
                    &copy; <?= date('Y') ?> PharmaManager. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.login-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.form-panel').forEach(panel => panel.classList.remove('active'));

            document.querySelector(`.login-tab:nth-child(${type === 'customer' ? 1 : 2})`).classList.add('active');
            document.getElementById(type + '-panel').classList.add('active');
        }

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        function fillStaffDemo(username, password) {
            document.querySelector('#staff-panel input[name="username"]').value = username;
            document.querySelector('#staff-panel input[name="password"]').value = password;
        }

        function fillCustomerDemo(email, password) {
            document.querySelector('#customer-panel input[name="email"]').value = email;
            document.querySelector('#customer-panel input[name="password"]').value = password;
        }
    </script>
</body>
</html>
