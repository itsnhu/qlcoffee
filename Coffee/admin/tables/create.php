<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$errors = [];
$formData = [
    'name' => '',
    'seat_count' => 4
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['seat_count'] = (int)($_POST['seat_count'] ?? 4);

    if (empty($formData['name'])) {
        $errors['name'] = 'Vui lòng nhập tên bàn';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Tên bàn phải có ít nhất 2 ký tự';
    }

    if ($formData['seat_count'] <= 0) {
        $errors['seat_count'] = 'Số ghế phải lớn hơn 0';
    }

    if (empty($errors['name'])) {
        try {
            $sql = "SELECT COUNT(*) as count FROM tables WHERE name = ?";
            $result = fetchOne($pdo, $sql, [$formData['name']]);
            if ($result['count'] > 0) {
                $errors['name'] = 'Tên bàn đã tồn tại';
            }
        } catch (PDOException $e) {
            error_log("Check Table Name Error: " . $e->getMessage());
            $errors['name'] = 'Có lỗi khi kiểm tra tên bàn';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO tables (name, capacity, status, created_at) VALUES (?, ?, 'free', NOW())";
            $params = [$formData['name'], $formData['seat_count']];
            executeQuery($pdo, $sql, $params);

            setMessage('success', 'Thêm bàn thành công!');
            redirect('/admin/tables/index.php');
        } catch (PDOException $e) {
            error_log("Create Table Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi khi thêm bàn. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Thêm bàn mới';
$additionalCSS = '
<style>
    .premium-card {
        background: white;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .premium-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem 2rem;
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.6rem;
    }
    .form-control-premium {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1.2rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }
    .form-control-premium:focus {
        background-color: white;
        border-color: #0d6efd;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        outline: none;
    }
    .input-group-text-premium {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-right: none;
        border-radius: 12px 0 0 12px;
        color: #64748b;
        padding-left: 1.2rem;
        padding-right: 1.2rem;
    }
    .form-control-premium-group {
        border-radius: 0 12px 12px 0 !important;
    }
    .btn-premium {
        background: #0d6efd;
        color: white;
        border: none;
        padding: 0.9rem 2.5rem;
        border-radius: 12px;
        font-weight: 700;
        box-shadow: 0 4px 14px rgba(13, 110, 253, 0.3);
        transition: all 0.3s ease;
    }
    .btn-premium:hover {
        background: #0b5ed7;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
    }
</style>
';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="row mb-5">
    <div class="col-12 text-center">
        <h2 class="fw-bold mb-1" style="color: #1e293b;">THÊM BÀN MỚI</h2>
        <p class="text-muted">Thiết lập vị trí và số lượng chỗ ngồi cho không gian quán</p>
    </div>
</div>

<div class="row mb-5">
    <div class="col-lg-6 col-md-8 mx-auto">
        <div class="premium-card">
            <div class="premium-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Cấu hình bàn</h5>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/tables/index.php" class="small text-decoration-none text-muted fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="name" class="form-label">Tên bàn / Vị trí <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-premium"><i class="bi bi-hash"></i></span>
                            <input type="text" class="form-control form-control-premium form-control-premium-group <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                   id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>"
                                   placeholder="Ví dụ: Bàn 12, Khu VIP 1..." required autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="seat_count" class="form-label">Sức chứa (Số ghế) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-premium"><i class="bi bi-people"></i></span>
                            <input type="number" class="form-control form-control-premium form-control-premium-group <?php echo isset($errors['seat_count']) ? 'is-invalid' : ''; ?>"
                                   id="seat_count" name="seat_count" value="<?php echo htmlspecialchars($formData['seat_count']); ?>"
                                   min="1" required>
                            <?php if (isset($errors['seat_count'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['seat_count']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-premium rounded-pill w-100 py-3">
                            <i class="bi bi-plus-lg me-2"></i> Xác nhận thêm bàn
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
