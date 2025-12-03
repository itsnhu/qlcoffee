<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


try {
    $medicines = fetchAll($pdo, "SELECT id, name, code, price, quantity, unit
                                  FROM medicines
                                  WHERE quantity > 0
                                  ORDER BY name ASC");
} catch (PDOException $e) {
    error_log("Load Medicines Error: " . $e->getMessage());
    $medicines = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    

    if (empty($medicine_ids) || count($medicine_ids) === 0) {
        $errors[] = 'Vui lòng thêm ít nhất một thuốc vào hóa đơn.';
    }

    
    $invoice_details = [];
    $total_amount = 0;

    foreach ($medicine_ids as $index => $medicine_id) {
        if (empty($medicine_id) || !is_numeric($medicine_id)) {
            continue; 
        }

        $quantity = $quantities[$index] ?? 0;

        if (!is_numeric($quantity) || $quantity <= 0) {
            $errors[] = "Số lượng dòng " . ($index + 1) . " phải là số dương.";
            continue;
        }

        
        try {
            $medicine = fetchOne($pdo, "SELECT id, name, price, quantity FROM medicines WHERE id = ?", [$medicine_id]);

            if (!$medicine) {
                $errors[] = "Không tìm thấy thuốc ở dòng " . ($index + 1) . ".";
                continue;
            }

            if ($medicine['quantity'] < $quantity) {
                $errors[] = "Thuốc '{$medicine['name']}' không đủ tồn kho. Hiện có: {$medicine['quantity']}, yêu cầu: {$quantity}.";
                continue;
            }

            $price = $medicine['price'];
            $subtotal = $quantity * $price;
            $total_amount += $subtotal;

            $invoice_details[] = [
                'medicine_id' => $medicine_id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal
            ];

        } catch (PDOException $e) {
            error_log("Check Stock Error: " . $e->getMessage());
            $errors[] = "Lỗi kiểm tra tồn kho cho dòng " . ($index + 1) . ".";
        }
    }

    if (empty($invoice_details)) {
        $errors[] = 'Không có thuốc hợp lệ nào trong hóa đơn.';
    }

    
    if (empty($errors)) {
        try {
            
            $pdo->beginTransaction();

            
            $invoice_code = 'HD' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            
            $sql = "INSERT INTO invoices (invoice_code, user_id, customer_name, customer_phone, total_amount, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

            $params = [
                $invoice_code,
                $_SESSION['user_id'],
                !empty($customer_name) ? $customer_name : null,
                !empty($customer_phone) ? $customer_phone : null,
                $total_amount
            ];

            executeQuery($pdo, $sql, $params);
            $invoice_id = $pdo->lastInsertId();

            
            foreach ($invoice_details as $detail) {
                
                $sql = "INSERT INTO invoice_details (invoice_id, medicine_id, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?)";

                $params = [
                    $invoice_id,
                    $detail['medicine_id'],
                    $detail['quantity'],
                    $detail['price'],
                    $detail['subtotal']
                ];

                executeQuery($pdo, $sql, $params);

                
                $sql = "UPDATE medicines SET quantity = quantity - ? WHERE id = ?";
                executeQuery($pdo, $sql, [$detail['quantity'], $detail['medicine_id']]);
            }

            
            $pdo->commit();

            setMessage('success', "Tạo hóa đơn $invoice_code thành công!");
            header('Location: ' . BASE_URL . '/admin/sales/view.php?id=' . $invoice_id);
            exit;

        } catch (PDOException $e) {
            
            $pdo->rollBack();
            error_log("Create Invoice Error: " . $e->getMessage());
            $errors[] = 'Có lỗi khi tạo hóa đơn. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Tạo hóa đơn bán hàng';
$additionalCSS = '
<style>
    .medicine-row {
        background-color: #f8f9fa;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .stock-warning {
        color: #dc3545;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .stock-ok {
        color: #198754;
        font-weight: bold;
        font-size: 0.9rem;
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
                    Tạo hóa đơn bán hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Nhập thông tin hóa đơn bán hàng cho khách
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form -->
<form method="POST" action="" id="invoiceForm">
    <div class="row">
        <!-- Thông tin khách hàng -->
        <div class="col-lg-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person me-2"></i>
                    <strong>Thông tin khách hàng (Tùy chọn)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label">Tên khách hàng</label>
                            <input type="text"
                                   class="form-control"
                                   id="customer_name"
                                   name="customer_name"
                                   placeholder="Nhập tên khách hàng..."
                                   value="<?php echo htmlspecialchars($customer_name ?? ''); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="customer_phone" class="form-label">Số điện thoại</label>
                            <input type="text"
                                   class="form-control"
                                   id="customer_phone"
                                   name="customer_phone"
                                   placeholder="Nhập số điện thoại..."
                                   value="<?php echo htmlspecialchars($customer_phone ?? ''); ?>">
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
                        <strong>Danh sách thuốc bán</strong>
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
                                <div class="col-md-5 mb-2">
                                    <label class="form-label">Thuốc <span class="text-danger">*</span></label>
                                    <select class="form-select medicine-select" name="medicine_id[]" required>
                                        <option value="">-- Chọn thuốc --</option>
                                        <?php foreach ($medicines as $medicine): ?>
                                            <option value="<?php echo $medicine['id']; ?>"
                                                    data-price="<?php echo $medicine['price']; ?>"
                                                    data-stock="<?php echo $medicine['quantity']; ?>"
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

                                <div class="col-md-1 mb-2">
                                    <label class="form-label">ĐVT</label>
                                    <input type="text"
                                           class="form-control unit-display"
                                           value=""
                                           readonly>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Giá bán</label>
                                    <input type="text"
                                           class="form-control price-display"
                                           value=""
                                           readonly>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <label class="form-label">Thành tiền</label>
                                    <div class="subtotal-display fw-bold text-success">0đ</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="stock-info"></div>
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
                <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>
                    Hủy bỏ
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>
                    Tạo hóa đơn
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Template cho dòng thuốc mới -->
<template id="medicineRowTemplate">
    <div class="medicine-row">
        <div class="row align-items-end">
            <div class="col-md-5 mb-2">
                <label class="form-label">Thuốc <span class="text-danger">*</span></label>
                <select class="form-select medicine-select" name="medicine_id[]" required>
                    <option value="">-- Chọn thuốc --</option>
                    <?php foreach ($medicines as $medicine): ?>
                        <option value="<?php echo $medicine['id']; ?>"
                                data-price="<?php echo $medicine['price']; ?>"
                                data-stock="<?php echo $medicine['quantity']; ?>"
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

            <div class="col-md-1 mb-2">
                <label class="form-label">ĐVT</label>
                <input type="text"
                       class="form-control unit-display"
                       value=""
                       readonly>
            </div>

            <div class="col-md-2 mb-2">
                <label class="form-label">Giá bán</label>
                <input type="text"
                       class="form-control price-display"
                       value=""
                       readonly>
            </div>

            <div class="col-md-1 mb-2">
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
        <div class="row">
            <div class="col-12">
                <div class="stock-info"></div>
            </div>
        </div>
    </div>
</template>

<script>
// Biến đếm số dòng
let rowCount = 1;

// Hàm tính toán
function calculateRow(row) {
    const select = row.querySelector('.medicine-select');
    const selectedOption = select.options[select.selectedIndex];
    const stock = parseFloat(selectedOption.dataset.stock) || 0;
    const price = parseFloat(selectedOption.dataset.price) || 0;
    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
    const subtotal = quantity * price;

    row.querySelector('.subtotal-display').textContent = formatCurrency(subtotal);

    // Hiển thị cảnh báo tồn kho
    const stockInfo = row.querySelector('.stock-info');
    if (select.value) {
        if (quantity > stock) {
            stockInfo.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i><span class="stock-warning">Không đủ hàng! Tồn kho: ' + stock + '</span>';
        } else {
            stockInfo.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span class="stock-ok">Tồn kho: ' + stock + '</span>';
        }
    } else {
        stockInfo.innerHTML = '';
    }

    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.medicine-row').forEach(row => {
        const select = row.querySelector('.medicine-select');
        const selectedOption = select.options[select.selectedIndex];
        const price = parseFloat(selectedOption.dataset.price) || 0;
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
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
    // Khi chọn thuốc, tự động điền giá và hiển thị tồn kho
    row.querySelector('.medicine-select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.dataset.price || 0;
        const unit = selectedOption.dataset.unit || '';

        row.querySelector('.price-display').value = formatCurrency(price);
        row.querySelector('.unit-display').value = unit;
        calculateRow(row);
    });

    // Khi thay đổi số lượng
    row.querySelector('.quantity-input').addEventListener('input', function() {
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

// Validate form trước khi submit
document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.medicine-row');
    let hasError = false;

    rows.forEach(row => {
        const select = row.querySelector('.medicine-select');
        const selectedOption = select.options[select.selectedIndex];
        const stock = parseFloat(selectedOption.dataset.stock) || 0;
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;

        if (select.value && quantity > stock) {
            hasError = true;
        }
    });

    if (hasError) {
        e.preventDefault();
        alert('Có thuốc không đủ tồn kho! Vui lòng kiểm tra lại số lượng.');
        return false;
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
