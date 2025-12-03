<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $categories = fetchAll($pdo, "SELECT id, name FROM categories ORDER BY name ASC");
    $suppliers = fetchAll($pdo, "SELECT id, name FROM suppliers ORDER BY name ASC");
} catch (PDOException $e) {
    error_log("Load Data Error: " . $e->getMessage());
    $categories = [];
    $suppliers = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $unit = trim($_POST['unit'] ?? 'Viên');
    $expiry_date = $_POST['expiry_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    
    if (empty($name)) {
        $errors[] = 'Tên thuốc không được để trống.';
    }

    if (empty($code)) {
        $errors[] = 'Mã thuốc không được để trống.';
    } else {
        
        $checkCode = fetchOne($pdo, "SELECT id FROM medicines WHERE code = ?", [$code]);
        if ($checkCode) {
            $errors[] = 'Mã thuốc đã tồn tại trong hệ thống.';
        }
    }

    if (empty($category_id) || !is_numeric($category_id)) {
        $errors[] = 'Vui lòng chọn loại thuốc.';
    }

    if (empty($supplier_id) || !is_numeric($supplier_id)) {
        $errors[] = 'Vui lòng chọn nhà cung cấp.';
    }

    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Giá bán phải là số dương.';
    }

    if (empty($quantity) || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = 'Số lượng phải là số không âm.';
    }

    if (!empty($expiry_date)) {
        $expiryDateTime = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if (!$expiryDateTime) {
            $errors[] = 'Ngày hết hạn không hợp lệ.';
        }
    }

    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO medicines (name, code, category_id, supplier_id, price, quantity, unit, expiry_date, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $name,
                $code,
                $category_id,
                $supplier_id,
                $price,
                $quantity,
                $unit,
                !empty($expiry_date) ? $expiry_date : null,
                !empty($description) ? $description : null
            ];

            executeQuery($pdo, $sql, $params);

            setMessage('success', 'Thêm thuốc mới thành công!');
            header('Location: ' . BASE_URL . '/admin/medicines/index.php');
            exit;

        } catch (PDOException $e) {
            error_log("Insert Medicine Error: " . $e->getMessage());
            $errors[] = 'Có lỗi khi thêm thuốc. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Thêm thuốc mới';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Hiển thị lỗi -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Có lỗi xảy ra:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-plus-circle text-primary"></i>
                    Thêm thuốc mới
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Nhập đầy đủ thông tin thuốc vào hệ thống
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/medicines/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form -->
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-file-earmark-plus me-2"></i>
                <strong>Thông tin thuốc</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="medicineForm">
                    <div class="row">
                        <!-- Tên thuốc -->
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">
                                Tên thuốc <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="name"
                                   name="name"
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                   required
                                   placeholder="Ví dụ: Paracetamol 500mg">
                        </div>

                        <!-- Mã thuốc -->
                        <div class="col-md-4 mb-3">
                            <label for="code" class="form-label">
                                Mã thuốc <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="code"
                                   name="code"
                                   value="<?php echo htmlspecialchars($code ?? ''); ?>"
                                   required
                                   placeholder="Ví dụ: MED001">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Loại thuốc -->
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">
                                Loại thuốc <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Chọn loại thuốc --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <a href="<?php echo BASE_URL; ?>/admin/categories/create.php" target="_blank">
                                    <i class="bi bi-plus-circle"></i> Thêm loại thuốc mới
                                </a>
                            </small>
                        </div>

                        <!-- Nhà cung cấp -->
                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">
                                Nhà cung cấp <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>"
                                            <?php echo (isset($supplier_id) && $supplier_id == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <a href="<?php echo BASE_URL; ?>/admin/suppliers/create.php" target="_blank">
                                    <i class="bi bi-plus-circle"></i> Thêm nhà cung cấp mới
                                </a>
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Giá bán -->
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">
                                Giá bán (VNĐ) <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="price"
                                   name="price"
                                   value="<?php echo htmlspecialchars($price ?? ''); ?>"
                                   required
                                   min="0"
                                   step="1"
                                   placeholder="0">
                        </div>

                        <!-- Số lượng -->
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">
                                Số lượng <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="quantity"
                                   name="quantity"
                                   value="<?php echo htmlspecialchars($quantity ?? '0'); ?>"
                                   required
                                   min="0"
                                   placeholder="0">
                        </div>

                        <!-- Đơn vị -->
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label">Đơn vị</label>
                            <input type="text"
                                   class="form-control"
                                   id="unit"
                                   name="unit"
                                   value="<?php echo htmlspecialchars($unit ?? 'Viên'); ?>"
                                   placeholder="Viên, Hộp, Chai...">
                        </div>
                    </div>

                    <!-- Ngày hết hạn -->
                    <div class="mb-3">
                        <label for="expiry_date" class="form-label">Hạn sử dụng</label>
                        <input type="date"
                               class="form-control"
                               id="expiry_date"
                               name="expiry_date"
                               value="<?php echo htmlspecialchars($expiry_date ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> Để trống nếu không có thời hạn sử dụng
                        </small>
                    </div>

                    <!-- Mô tả -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Thông tin thêm về thuốc (công dụng, cách dùng...)"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?php echo BASE_URL; ?>/admin/medicines/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>
                            Hủy bỏ
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            Thêm thuốc
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto format giá bán
document.getElementById('price').addEventListener('blur', function() {
    if (this.value) {
        this.value = Math.round(parseFloat(this.value));
    }
});

// Tự động tạo mã thuốc từ tên (tuỳ chọn)
document.getElementById('name').addEventListener('blur', function() {
    const codeInput = document.getElementById('code');
    if (!codeInput.value) {
        // Tạo mã từ tên (lấy chữ cái đầu)
        const nameParts = this.value.trim().split(' ');
        const codePrefix = nameParts.map(p => p.charAt(0).toUpperCase()).join('');
        const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        codeInput.value = codePrefix + randomNum;
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
