<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$errors = [];
$supplierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;


try {
    $sql = "SELECT * FROM suppliers WHERE id = ?";
    $supplier = fetchOne($pdo, $sql, [$supplierId]);

    if (!$supplier) {
        setMessage('danger', 'Không tìm thấy nhà cung cấp!');
        redirect('/admin/suppliers/index.php');
    }
} catch (PDOException $e) {
    error_log("Fetch Supplier Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin nhà cung cấp.');
    redirect('/admin/suppliers/index.php');
}

$formData = [
    'name' => $supplier['name'],
    'phone' => $supplier['phone'],
    'address' => $supplier['address']
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['phone'] = sanitize($_POST['phone'] ?? '');
    $formData['address'] = sanitize($_POST['address'] ?? '');

    
    if (empty($formData['name'])) {
        $errors['name'] = 'Vui lòng nhập tên nhà cung cấp';
    } elseif (strlen($formData['name']) < 3) {
        $errors['name'] = 'Tên nhà cung cấp phải có ít nhất 3 ký tự';
    }

    if (empty($formData['phone'])) {
        $errors['phone'] = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $formData['phone'])) {
        $errors['phone'] = 'Số điện thoại phải có 10-11 chữ số';
    }

    if (empty($formData['address'])) {
        $errors['address'] = 'Vui lòng nhập địa chỉ';
    } elseif (strlen($formData['address']) < 5) {
        $errors['address'] = 'Địa chỉ phải có ít nhất 5 ký tự';
    }

    
    if (empty($errors['name'])) {
        try {
            $sql = "SELECT COUNT(*) as count FROM suppliers WHERE name = ? AND id != ?";
            $result = fetchOne($pdo, $sql, [$formData['name'], $supplierId]);
            if ($result['count'] > 0) {
                $errors['name'] = 'Tên nhà cung cấp đã tồn tại';
            }
        } catch (PDOException $e) {
            error_log("Check Supplier Name Error: " . $e->getMessage());
            $errors['name'] = 'Có lỗi khi kiểm tra tên nhà cung cấp';
        }
    }

    
    if (empty($errors)) {
        try {
            
            $sql = "UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?";
            $params = [
                $formData['name'],
                $formData['phone'],
                $formData['address'],
                $supplierId
            ];

            executeQuery($pdo, $sql, $params);

            
            setMessage('success', 'Cập nhật nhà cung cấp thành công!');
            redirect('/admin/suppliers/index.php');
        } catch (PDOException $e) {
            error_log("Update Supplier Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi khi cập nhật nhà cung cấp. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Chỉnh sửa nhà cung cấp';
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
                    <a href="<?php echo BASE_URL; ?>/admin/suppliers/index.php">Nhà cung cấp</a>
                </li>
                <li class="breadcrumb-item active">Chỉnh sửa</li>
            </ol>
        </nav>
        <h2 class="mb-2">
            <i class="bi bi-pencil-square text-warning"></i>
            Chỉnh sửa nhà cung cấp
        </h2>
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Cập nhật thông tin nhà cung cấp: <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
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

<!-- Edit Supplier Form -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-pencil-square me-2"></i>
                <strong>Thông tin nhà cung cấp</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="editSupplierForm">
                    <!-- Supplier Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Tên nhà cung cấp <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-building"></i>
                            </span>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                   id="name"
                                   name="name"
                                   value="<?php echo htmlspecialchars($formData['name']); ?>"
                                   placeholder="Ví dụ: Công ty TNHH Dược phẩm ABC"
                                   required
                                   autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Tên nhà cung cấp phải có ít nhất 3 ký tự.
                        </small>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            Số điện thoại <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                   id="phone"
                                   name="phone"
                                   value="<?php echo htmlspecialchars($formData['phone']); ?>"
                                   placeholder="Ví dụ: 0901234567"
                                   required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['phone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Số điện thoại phải có 10-11 chữ số.
                        </small>
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label for="address" class="form-label">
                            Địa chỉ <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                                      id="address"
                                      name="address"
                                      rows="3"
                                      placeholder="Nhập địa chỉ đầy đủ của nhà cung cấp"
                                      required><?php echo htmlspecialchars($formData['address']); ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['address']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Địa chỉ phải có ít nhất 5 ký tự.
                        </small>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo BASE_URL; ?>/admin/suppliers/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-2"></i>
                            Cập nhật nhà cung cấp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
