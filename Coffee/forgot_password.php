<?php
require_once 'config/config.php';
require_once 'config/database.php';

$message = getMessage();
$errors = [];
$step = $_POST['step'] ?? ($_GET['step'] ?? 1);
$login_input = sanitize($_POST['login_input'] ?? ($_SESSION['recovery_target'] ?? ''));

// Handle Resend Request
if (isset($_GET['resend']) && isset($_SESSION['recovery_target'])) {
    $_SESSION['recovery_otp'] = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $step = 2;
    $login_input = $_SESSION['recovery_target'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        if (empty($login_input)) {
            $errors[] = 'Vui lòng nhập email hoặc tài khoản.';
        } else {
            $customer = fetchOne($pdo, "SELECT * FROM customers WHERE email = ?", [$login_input]);
            $user = fetchOne($pdo, "SELECT * FROM users WHERE username = ?", [$login_input]);
            
            if ($customer || $user) {
                $step = 2;
                $_SESSION['recovery_target'] = $login_input;
                $_SESSION['recovery_type'] = $customer ? 'customer' : 'staff';
                $_SESSION['recovery_id'] = $customer ? $customer['id'] : $user['id'];
                // Generate Random OTP
                $_SESSION['recovery_otp'] = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            } else {
                $errors[] = 'Không tìm thấy thông tin phù hợp trong hệ thống.';
            }
        }
    } elseif ($step == 2) {
        $otp_array = $_POST['otp'] ?? [];
        $otp = implode('', $otp_array);
        $stored_otp = $_SESSION['recovery_otp'] ?? '';
        
        if (strlen($otp) === 6) {
            if ($otp === $stored_otp) {
                $type = $_SESSION['recovery_type'];
                $id = $_SESSION['recovery_id'];
                $token = md5($_SESSION['recovery_target'] . 'secret');
                
                unset($_SESSION['recovery_otp']);
                header("Location: " . BASE_URL . "/reset_final.php?type=$type&id=$id&token=$token");
                exit;
            } else {
                $errors[] = 'Mã OTP không chính xác. Vui lòng thử lại.';
                // Keep the same OTP so it doesn't "jump"
            }
        } else {
            $errors[] = 'Vui lòng nhập đầy đủ 6 chữ số OTP.';
        }
    }
}

$display_otp = $_SESSION['recovery_otp'] ?? '';
$otp_digits = str_split($display_otp);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - TNT Coffee</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;800&display=swap');
        
        body {
            background: #f1f1f1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        .recovery-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 35px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
        }
        .lock-icon-wrapper {
            width: 80px;
            height: 80px;
            background: #f1f1f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        .lock-icon-wrapper i { font-size: 2rem; color: #333; }
        .recovery-title { font-weight: 800; color: #1a1a1a; font-size: 1.75rem; margin-bottom: 0.5rem; text-transform: uppercase; }
        .recovery-subtitle { color: #666; font-size: 0.95rem; margin-bottom: 2.5rem; }
        .input-group-premium { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; gap: 12px; margin-bottom: 1.5rem; transition: all 0.3s; }
        .input-group-premium:focus-within { border-color: #1a1a1a; background: white; }
        .input-group-premium input { border: none; background: transparent; width: 100%; outline: none; color: #333; }
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 2.5rem; }
        .otp-input { width: 50px; height: 60px; border: 1px solid #e0e0e0; border-radius: 12px; background: #f8f8f8; text-align: center; font-size: 1.5rem; font-weight: 700; transition: all 0.3s; }
        .otp-input:focus { border-color: #1a1a1a; background: white; outline: none; }
        .btn-recovery { background: #170d0a; color: white; border: none; border-radius: 12px; padding: 14px; width: 100%; font-weight: 800; transition: all 0.3s; margin-bottom: 1.5rem; }
        .btn-recovery:hover { background: #2b1d19; transform: translateY(-2px); }
        .footer-link { color: #666; font-size: 0.9rem; text-decoration: none; display: block; }
        .resend-text { font-size: 0.9rem; color: #666; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="recovery-card shadow-lg">
        <div class="lock-icon-wrapper">
            <i class="bi bi-lock-fill"></i>
        </div>
        
        <?php if ($step == 1): ?>
            <h2 class="recovery-title">QUÊN MẬT KHẨU?</h2>
            <p class="recovery-subtitle">Nhập email của bạn để lấy lại mật khẩu</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 text-start mb-4 small py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="step" value="1">
                <div class="input-group-premium text-start">
                    <i class="bi bi-envelope text-muted"></i>
                    <input type="text" name="login_input" placeholder="Nhập email hoặc tài khoản..." required value="<?= htmlspecialchars($login_input) ?>">
                </div>
                <button type="submit" class="btn-recovery">GỬI ĐI</button>
                <a href="login.php" class="footer-link">Nhớ mật khẩu? <strong>Đăng nhập</strong></a>
            </form>

        <?php else: ?>
            <h2 class="recovery-title">XÁC NHẬN MÃ</h2>
            <p class="recovery-subtitle">Vui lòng nhập mã OTP 6 số đã được gửi tới email của bạn</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 text-start mb-4 small py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="otp-form">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="login_input" value="<?= htmlspecialchars($login_input) ?>">
                
                <div class="otp-inputs">
                    <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1" required pattern="\d*" value="<?= $otp_digits[$i] ?? '' ?>">
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn-recovery shadow">XÁC NHẬN</button>
                
                <div class="resend-text" id="resend-wrapper">
                    Gửi lại mã (<strong id="timer">15s</strong>)
                </div>

                <a href="forgot_password.php" class="footer-link">Quay lại</a>
            </form>
            
            <script>
                const inputs = document.querySelectorAll('.otp-input');
                const form = document.getElementById('otp-form');
                const timerEl = document.getElementById('timer');
                const resendWrapper = document.getElementById('resend-wrapper');
                let timeLeft = 15;
                let isSubmitting = false;

                inputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => {
                        if (e.target.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus();
                        
                        // Auto submit logic
                        const allFilled = Array.from(inputs).every(i => i.value.length === 1);
                        if (allFilled && !isSubmitting) {
                            isSubmitting = true;
                            form.submit();
                        }
                    });
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !e.target.value && index > 0) inputs[index - 1].focus();
                    });
                });

                // Prevent manual multi-submit
                form.addEventListener('submit', () => { isSubmitting = true; });

                const countdown = setInterval(() => {
                    timeLeft--;
                    if (timerEl) {
                        timerEl.textContent = `${timeLeft}s`;
                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            resendWrapper.innerHTML = `<a href="?resend=1&step=2" class="text-dark fw-bold text-decoration-none">Gửi lại mã ngay</a>`;
                        }
                    } else {
                        clearInterval(countdown);
                    }
                }, 1000);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
