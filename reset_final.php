<?php
require_once 'config/config.php';
require_once 'config/database.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$token = $_GET['token'] ?? '';
$errors = [];
$success = false;

// Basic validation for demo
if (empty($id) || empty($token)) {
    die("Yêu cầu không hợp lệ.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($password) || strlen($password) < 3) {
        $errors[] = 'Mật khẩu phải có ít nhất 3 ký tự.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if ($type === 'customer') {
            $sql = "UPDATE customers SET password = ? WHERE id = ?";
        } else {
            $sql = "UPDATE users SET password = ? WHERE id = ?";
        }
        
        if (executeQuery($pdo, $sql, [$hashed, $id])) {
            $success = true;
            setMessage('success', 'Mật khẩu của bạn đã được cập nhật thành công!');
        } else {
            $errors[] = 'Có lỗi xảy ra khi cập nhật mật khẩu.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Coffee Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #fdf8f6 0%, #eaddd7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .reset-card {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(111, 78, 55, 0.15);
            padding: 2.5rem;
        }
        .btn-primary { background: #6F4E37; border: none; padding: 0.8rem; font-weight: 600; }
        .btn-primary:hover { background: #5D4037; }
    </style>
</head>
<body>
    <div class="reset-card text-center">
        <h2 class="fw-bold mb-4">Đặt lại mật khẩu mới</h2>

        <?php if ($success): ?>
            <div class="alert alert-success mt-4">
                <i class="bi bi-check-circle-fill"></i> Thành công!<br>
                Hệ thống sẽ chuyển hướng sau 3 giây...
            </div>
            <script>setTimeout(() => location.href='login.php', 3000);</script>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger text-start">
                    <?php foreach ($errors as $error) echo "<div>$error</div>"; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3 text-start">
                    <label class="form-label small">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" required placeholder="Nhập mật khẩu mới">
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label small">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm">Cập nhật mật khẩu</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
