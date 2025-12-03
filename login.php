<?php

require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/employee/dashboard.php');
    }
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - PharmaManager</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💊</text></svg>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">

    <style>
        :root {
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 50%, #99f6e4 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* Decorative Elements */
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

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 60%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(13, 148, 136, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Floating Pills Animation */
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
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
        }

        .pill:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .pill:nth-child(2) { top: 20%; right: 15%; animation-delay: 3s; font-size: 1.5rem; }
        .pill:nth-child(3) { bottom: 30%; left: 5%; animation-delay: 6s; }
        .pill:nth-child(4) { top: 60%; right: 10%; animation-delay: 9s; font-size: 2.5rem; }
        .pill:nth-child(5) { bottom: 10%; right: 25%; animation-delay: 12s; }
        .pill:nth-child(6) { top: 40%; left: 15%; animation-delay: 15s; font-size: 1.8rem; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(20px) rotate(-5deg); }
        }

        /* Login Container */
        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Left Side - Branding */
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
            .login-branding {
                display: flex;
            }
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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

        .branding-logo i {
            font-size: 3.5rem;
        }

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

        /* Right Side - Form */
        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            padding: 2.5rem 2.5rem 0;
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
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.3);
        }

        .login-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        .login-body {
            padding: 2rem 2.5rem 2.5rem;
        }

        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 1.25rem;
            margin-top: 2px;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
            z-index: 5;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
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

        .form-control:focus + .input-icon,
        .form-control:not(:placeholder-shown) + .input-icon {
            color: var(--primary-600);
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        /* Password Toggle */
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

        .password-toggle:hover {
            color: var(--gray-600);
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            border-radius: 12px;
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

        .btn-login:active {
            transform: translateY(0);
        }

        /* Demo Accounts */
        .demo-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .demo-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            text-align: center;
        }

        .demo-accounts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .demo-account {
            padding: 0.875rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .demo-account:hover {
            background: #f0fdfa;
            border-color: var(--primary-500);
        }

        .demo-account .role {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--primary-600);
            margin-bottom: 0.25rem;
        }

        .demo-account .credentials {
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .demo-account .credentials code {
            background: white;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                border-radius: 20px;
            }

            .login-header, .login-body {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .demo-accounts {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Pills Background -->
    <div class="floating-pills">
        <div class="pill">💊</div>
        <div class="pill">💉</div>
        <div class="pill">🩺</div>
        <div class="pill">💊</div>
        <div class="pill">🏥</div>
        <div class="pill">💊</div>
    </div>

    <div class="login-wrapper">
        <!-- Left Branding -->
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <i class="bi bi-capsule-pill"></i>
                    <span>PharmaManager</span>
                </div>
                <h2 class="branding-title">Quản lý nhà thuốc thông minh & hiệu quả</h2>
                <p class="branding-desc">
                    Giải pháp toàn diện giúp bạn quản lý kho thuốc, theo dõi doanh thu,
                    và tối ưu hóa hoạt động kinh doanh nhà thuốc.
                </p>
                <ul class="branding-features">
                    <li>
                        <i class="bi bi-check"></i>
                        Quản lý kho thuốc & theo dõi hạn sử dụng
                    </li>
                    <li>
                        <i class="bi bi-check"></i>
                        Bán hàng nhanh chóng, in hóa đơn tự động
                    </li>
                    <li>
                        <i class="bi bi-check"></i>
                        Báo cáo doanh thu chi tiết theo ngày/tháng
                    </li>
                    <li>
                        <i class="bi bi-check"></i>
                        Phân quyền Admin & Nhân viên linh hoạt
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Form -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <div class="logo-icon">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <h1>Chào mừng trở lại!</h1>
                    <p>Đăng nhập để tiếp tục</p>
                </div>

                <div class="login-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Tên đăng nhập</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    name="username"
                                    class="form-control"
                                    placeholder="Nhập tên đăng nhập"
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required
                                    autofocus
                                >
                                <i class="bi bi-person input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    placeholder="Nhập mật khẩu"
                                    required
                                >
                                <i class="bi bi-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Đăng nhập
                        </button>
                    </form>

                    <div class="demo-section">
                        <div class="demo-title">Tài khoản demo</div>
                        <div class="demo-accounts">
                            <div class="demo-account" onclick="fillDemo('admin', 'admin123')">
                                <div class="role">👨‍💼 Admin</div>
                                <div class="credentials">
                                    <code>admin</code> / <code>admin123</code>
                                </div>
                            </div>
                            <div class="demo-account" onclick="fillDemo('nhanvien', 'admin123')">
                                <div class="role">👩‍⚕️ Nhân viên</div>
                                <div class="credentials">
                                    <code>nhanvien</code> / <code>admin123</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="login-footer">
                    &copy; <?php echo date('Y'); ?> PharmaManager. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        function fillDemo(username, password) {
            document.querySelector('input[name="username"]').value = username;
            document.querySelector('input[name="password"]').value = password;
            document.querySelector('input[name="username"]').focus();
        }
    </script>
</body>
</html>
