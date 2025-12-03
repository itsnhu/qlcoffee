<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


try {
    $suppliers = fetchAll($pdo, "SELECT id, name FROM suppliers ORDER BY name ASC");
    $medicines = fetchAll($pdo, "SELECT id, name, code, price, unit FROM medicines ORDER BY name ASC");
} catch (PDOException $e) {
    error_log("Load Data Error: " . $e->getMessage());
    $suppliers = [];
    $medicines = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    
    $supplier_id = $_POST['supplier_id'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    
    if (empty($supplier_id) || !is_numeric($supplier_id)) {
        $errors[] = 'Vui lòng chọn nhà cung cấp.';
    }

    if (empty($medicine_ids) || count($medicine_ids) === 0) {
        $errors[] = 'Vui lòng thêm ít nhất một thuốc vào phiếu nhập.';
    }

    
    $import_details = [];
    $total_amount = 0;

    foreach ($medicine_ids as $index => $medicine_id) {
        if (empty($medicine_id) || !is_numeric($medicine_id)) {
            continue; 
        }

        $quantity = $quantities[$index] ?? 0;
        $price = $prices[$index] ?? 0;

        if (!is_numeric($quantity) || $quantity <= 0) {
            $errors[] = "Số lượng dòng " . ($index + 1) . " phải là số dương.";
            continue;
        }

        if (!is_numeric($price) || $price <= 0) {
            $errors[] = "Giá nhập dòng " . ($index + 1) . " phải là số dương.";
            continue;
        }

        $subtotal = $quantity * $price;
        $total_amount += $subtotal;

        $import_details[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal
        ];
    }

    if (empty($import_details)) {
        $errors[] = 'Không có thuốc hợp lệ nào trong phiếu nhập.';
    }

    
    if (empty($errors)) {
        try {
            
            $pdo->beginTransaction();

            
            $import_code = 'PN' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            
            $sql = "INSERT INTO imports (import_code, user_id, supplier_id, total_amount, note, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

            $params = [
                $import_code,
                $_SESSION['user_id'],
                $supplier_id,
                $total_amount,
                !empty($note) ? $note : null
            ];

            executeQuery($pdo, $sql, $params);
            $import_id = $pdo->lastInsertId();

            
            foreach ($import_details as $detail) {
                
                $sql = "INSERT INTO import_details (import_id, medicine_id, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?)";

                $params = [
                    $import_id,
                    $detail['medicine_id'],
                    $detail['quantity'],
                    $detail['price'],
                    $detail['subtotal']
                ];

                executeQuery($pdo, $sql, $params);

                
                $sql = "UPDATE medicines SET quantity = quantity + ? WHERE id = ?";
                executeQuery($pdo, $sql, [$detail['quantity'], $detail['medicine_id']]);
            }

            
            $pdo->commit();

            setMessage('success', "Tạo phiếu nhập $import_code thành công!");
            header('Location: ' . BASE_URL . '/admin/imports/view.php?id=' . $import_id);
            exit;

        } catch (PDOException $e) {
            
            $pdo->rollBack();
            error_log("Create Import Error: " . $e->getMessage());
            $errors[] = 'Có lỗi khi tạo phiếu nhập. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Tạo phiếu nhập mới';
$additionalCSS = '
<style>
    .medicine-row {
        background-color: #f8f9fa;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .remove-row {
        cursor: pointer;
    }
    #totalAmount {
        font-size: 1.5rem;
        font-weight: bold;
    }
</style>
';

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
                    Tạo phiếu nhập mới
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Nhập thông tin phiếu nhập hàng vào kho
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/imports/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form -->
<form method="POST" action="" id="importForm">
    <div class="row">
        <!-- Thông tin chung -->
        <div class="col-lg-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <strong>Thông tin phiếu nhập</strong>
                </div>
                <div class="card-body">
                    <div class="row">
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
                        </div>

                        <!-- Ghi chú -->
                        <div class="col-md-6 mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control"
                                      id="note"
                                      name="note"
                                      rows="2"
                                      placeholder="Ghi chú về phiếu nhập..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách thuốc -->
        <div class="col-lg-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-capsule-pill me-2"></i>
                        <strong>Danh sách thuốc nhập</strong>
                    </div>
                    <button type="button" class="btn btn-light btn-sm" id="addRowBtn">
                        <i class="bi bi-plus-circle"></i> Thêm thuốc
                    </button>
                </div>
                <div class="card-body">
                    <div id="medicineRows">
                        <!-- Dòng đầu tiên -->
                        <div class="medicine-row" data-row="1">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">Thuốc <span class="text-danger">*</span></label>
                                    <select class="form-select medicine-select" name="medicine_id[]" required>
                                        <option value="">-- Chọn thuốc --</option>
                                        <?php foreach ($medicines as $medicine): ?>
                                            <option value="<?php echo $medicine['id']; ?>"
                                                    data-price="<?php echo $medicine['price']; ?>"
                                                    data-unit="<?php echo $medicine['unit']; ?>">
                                                <?php echo htmlspecialchars($medicine['code'] . ' - ' . $medicine['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control quantity-input"
                                           name="quantity[]"
                                           min="1"
                                           value="1"
                                           required>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Đơn vị</label>
                                    <input type="text"
                                           class="form-control unit-display"
                                           value=""
                                           readonly>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Giá nhập <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control price-input"
                                           name="price[]"
                                           min="0"
                                           step="1"
                                           required>
                                </div>

                                <div class="col-md-2 mb-2 text-end">
                                    <label class="form-label">Thành tiền</label>
                                    <div class="subtotal-display fw-bold text-success">0đ</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tổng tiền -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Tổng cộng:</h5>
                                        <div id="totalAmount" class="text-success">0đ</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>/admin/imports/index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>
                    Hủy bỏ
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>
                    Tạo phiếu nhập
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Template cho dòng thuốc mới -->
<template id="medicineRowTemplate">
    <div class="medicine-row">
        <div class="row align-items-end">
            <div class="col-md-4 mb-2">
                <label class="form-label">Thuốc <span class="text-danger">*</span></label>
                <select class="form-select medicine-select" name="medicine_id[]" required>
                    <option value="">-- Chọn thuốc --</option>
                    <?php foreach ($medicines as $medicine): ?>
                        <option value="<?php echo $medicine['id']; ?>"
                                data-price="<?php echo $medicine['price']; ?>"
                                data-unit="<?php echo $medicine['unit']; ?>">
                            <?php echo htmlspecialchars($medicine['code'] . ' - ' . $medicine['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 mb-2">
                <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control quantity-input"
                       name="quantity[]"
                       min="1"
                       value="1"
                       required>
            </div>

            <div class="col-md-2 mb-2">
                <label class="form-label">Đơn vị</label>
                <input type="text"
                       class="form-control unit-display"
                       value=""
                       readonly>
            </div>

            <div class="col-md-2 mb-2">
                <label class="form-label">Giá nhập <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control price-input"
                       name="price[]"
                       min="0"
                       step="1"
                       required>
            </div>

            <div class="col-md-1 mb-2 text-end">
                <label class="form-label">Thành tiền</label>
                <div class="subtotal-display fw-bold text-success">0đ</div>
            </div>

            <div class="col-md-1 mb-2 text-end">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger btn-sm remove-row w-100">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
// Biến đếm số dòng
let rowCount = 1;

// Hàm tính toán
function calculateRow(row) {
    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const subtotal = quantity * price;

    row.querySelector('.subtotal-display').textContent = formatCurrency(subtotal);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.medicine-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        total += quantity * price;
    });

    document.getElementById('totalAmount').textContent = formatCurrency(total);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Thêm dòng mới
document.getElementById('addRowBtn').addEventListener('click', function() {
    const template = document.getElementById('medicineRowTemplate');
    const clone = template.content.cloneNode(true);
    const newRow = clone.querySelector('.medicine-row');

    rowCount++;
    newRow.dataset.row = rowCount;

    document.getElementById('medicineRows').appendChild(clone);
    attachRowEvents(newRow);
});

// Gắn sự kiện cho dòng
function attachRowEvents(row) {
    // Khi chọn thuốc, tự động điền giá
    row.querySelector('.medicine-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.dataset.price || 0;
        const unit = selectedOption.dataset.unit || '';

        row.querySelector('.price-input').value = price;
        row.querySelector('.unit-display').value = unit;
        calculateRow(row);
    });

    // Khi thay đổi số lượng hoặc giá
    row.querySelector('.quantity-input').addEventListener('input', function() {
        calculateRow(row);
    });

    row.querySelector('.price-input').addEventListener('input', function() {
        calculateRow(row);
    });

    // Nút xóa dòng
    const removeBtn = row.querySelector('.remove-row');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            if (document.querySelectorAll('.medicine-row').length > 1) {
                row.remove();
                calculateTotal();
            } else {
                alert('Phải có ít nhất một dòng thuốc!');
            }
        });
    }
}

// Gắn sự kiện cho dòng đầu tiên
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.medicine-row').forEach(row => {
        attachRowEvents(row);
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
