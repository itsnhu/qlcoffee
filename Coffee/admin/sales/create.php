<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


// Tự động kiểm tra và sửa cấu trúc database nếu thiếu cột
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('customer_name', $checkCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) NULL AFTER order_code");
    }
    if (!in_array('customer_phone', $checkCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20) NULL AFTER customer_name");
    }
    if (!in_array('table_id', $checkCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN table_id INT NULL AFTER user_id");
    }
} catch (Exception $e) {
    echo "<div class='alert alert-warning'>Lỗi cấu trúc DB: " . $e->getMessage() . "</div>";
}

try {
    $products = fetchAll($pdo, "SELECT p.*, c.name as category_name 
                                  FROM products p
                                  JOIN categories c ON p.category_id = c.id
                                  WHERE p.is_available = 1
                                  ORDER BY c.name, p.name ASC");
                                  
    $tables = fetchAll($pdo, "SELECT * FROM tables ORDER BY name ASC");
    
    $pendingOrders = fetchAll($pdo, "SELECT o.*, t.name as table_name, u.full_name as staff_name
                                     FROM orders o
                                     LEFT JOIN tables t ON o.table_id = t.id
                                     LEFT JOIN users u ON o.user_id = u.id
                                     WHERE o.status IN ('pending', 'preparing', 'served')
                                     ORDER BY o.created_at DESC");
} catch (PDOException $e) {
    error_log("Load POS Data Error: " . $e->getMessage());
    $products = $tables = $pendingOrders = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (empty($product_ids)) {
        $errors[] = 'Vui lòng thêm ít nhất một món vào đơn hàng.';
    }

    
    $invoice_details = [];
    $total_amount = 0;

    foreach ($product_ids as $index => $product_id) {
        if (empty($product_id) || !is_numeric($product_id)) {
            continue; 
        }

        $quantity = $quantities[$index] ?? 0;

        if (!is_numeric($quantity) || $quantity <= 0) {
            $errors[] = "Số lượng dòng " . ($index + 1) . " phải là số dương.";
            continue;
        }

        
        try {
            $product = fetchOne($pdo, "SELECT id, name, price, quantity FROM products WHERE id = ?", [$product_id]);

            if (!$product) {
                $errors[] = "Không tìm thấy món ở dòng " . ($index + 1) . ".";
                continue;
            }

            // Bỏ qua kiểm tra tồn kho theo yêu cầu (luôn cho phép bán)
            /*
            if ($product['quantity'] < $quantity) {
                $errors[] = "Món '{$product['name']}' không đủ nguyên liệu/tồn kho. Hiện có: {$product['quantity']}, yêu cầu: {$quantity}.";
                continue;
            }
            */

            $price = $product['price'];
            $subtotal = $quantity * $price;
            $total_amount += $subtotal;

            $invoice_details[] = [
                'product_id' => $product_id,
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
        $errors[] = 'Không có món hợp lệ nào trong đơn hàng.';
    }

    
    if (empty($errors)) {
        try {
            
            $pdo->beginTransaction();

            
            // 1. Generate Order Code & Get Table Info
            $invoice_code = 'HD' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            $table_id = !empty($_POST['table_id']) ? $_POST['table_id'] : null;
            $note = !empty($_POST['note']) ? trim($_POST['note']) : null;
            
            // Payment selection from modal
            $is_paid = ($_POST['finalize_payment'] == '1');
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $status = $is_paid ? 'completed' : 'pending';
            $payment_status = $is_paid ? 'paid' : 'pending';

            // 2. Insert into Orders table (with payment choices)
            $sql = "INSERT INTO orders (order_code, user_id, table_id, customer_name, customer_phone, total_amount, note, status, payment_status, payment_method, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $invoice_code,
                $_SESSION['user_id'],
                $table_id,
                !empty($customer_name) ? $customer_name : null,
                !empty($customer_phone) ? $customer_phone : null,
                $total_amount,
                $note,
                $status,
                $payment_status,
                $payment_method
            ];

            executeQuery($pdo, $sql, $params);
            $invoice_id = $pdo->lastInsertId();

            // 3. Insert Order Details & Update Product Stock
            foreach ($invoice_details as $detail) {
                // Insert Detail
                $sql = "INSERT INTO order_details (order_id, product_id, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?)";

                $params = [
                    $invoice_id,
                    $detail['product_id'],
                    $detail['quantity'],
                    $detail['price'],
                    $detail['subtotal']
                ];

                executeQuery($pdo, $sql, $params);

                // Update Product Quantity (Stock)
                $sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                executeQuery($pdo, $sql, [$detail['quantity'], $detail['product_id']]);
            }

            // 4. Update Table Status to 'busy' if a table was assigned
            if ($table_id) {
                $statusSql = "UPDATE tables SET status = 'busy' WHERE id = ?";
                executeQuery($pdo, $statusSql, [$table_id]);
            }

            
            $pdo->commit();

            setMessage('success', "Tạo đơn hàng $invoice_code thành công!");
            header('Location: ' . BASE_URL . '/admin/sales/view.php?id=' . $invoice_id);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Create Order Error: " . $e->getMessage());
            $errors[] = 'Có lỗi khi tạo đơn hàng: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Bán hàng';
$additionalCSS = '
<style>
    .item-row {
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

<script>
    document.body.classList.add('pos-page');
</script>

<!-- Error/Success Messages -->
<div class="row">
    <div class="col-12">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="pos-layout">
    <!-- COLUMN 1: ACTIVE ORDERS -->
    <div class="col-lg-3 pos-column">
        <h5 class="pos-section-title">
            <i class="bi bi-clock-history"></i> Danh sách order
        </h5>
        
        <div class="product-search">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Tìm order #, bàn...">
        </div>

        <div class="pos-scroll">
            <?php if (empty($pendingOrders)): ?>
                <div class="order-list-empty">Chưa có đơn hàng nào đang chờ.</div>
            <?php else: ?>
                <?php foreach ($pendingOrders as $order): ?>
                    <div class="order-card-item">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold text-primary">#<?= htmlspecialchars($order['order_code']) ?></span>
                            <span class="badge bg-light text-dark"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="small mb-2">
                            <i class="bi bi-geo-alt me-1"></i> <?= $order['table_name'] ?? 'Mang về' ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><?= htmlspecialchars($order['staff_name']) ?></span>
                            <button class="btn btn-sm btn-primary py-0 px-3">Mở lại</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4 d-grid gap-2">
            <button class="btn btn-dark btn-pos" id="btnNewAtStore">
                <i class="bi bi-shop"></i> Thêm order tại quán
            </button>
            <button class="btn btn-outline-dark btn-pos" id="btnNewTakeAway">
                <i class="bi bi-bag-check"></i> Thêm order mang về
            </button>
        </div>
    </div>

    <!-- COLUMN 2: PRODUCT SELECTION & ORDER ITEMS -->
    <div class="col-lg-6 pos-column">
        <!-- Products Selection -->
        <h5 class="pos-section-title">
            <i class="bi bi-cup-hot"></i> Món ăn & Đồ uống
        </h5>
        
        <div class="pos-table-container mb-4" style="height: 40%;">
            <div class="pos-scroll">
                <table class="table pos-table table-hover align-middle">
                    <thead class="pos-table-header">
                        <tr>
                            <th>Tên món</th>
                            <th>ĐVT</th>
                            <th>Giá</th>
                            <th class="text-center">Chọn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-item" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= $product['price'] ?>" data-unit="<?= $product['unit'] ?>">
                                <td class="fw-bold"><?= htmlspecialchars($product['name']) ?></td>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($product['unit']) ?></span></td>
                                <td class="text-primary fw-bold"><?= formatCurrency($product['price']) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-product">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Current Cart -->
        <h5 class="pos-section-title">
            <i class="bi bi-receipt"></i> Đơn đang chọn
        </h5>
        
        <div class="pos-table-container" style="height: 45%;">
            <div class="pos-scroll">
                <table class="table pos-table table-hover align-middle" id="cartTable">
                    <thead class="pos-table-header">
                        <tr>
                            <th>Tên món</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Tiền</th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cart items will be added here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-warning w-100 fw-bold" id="btnSplitOrder">
                <i class="bi bi-scissors me-2"></i> Tách hóa đơn
            </button>
            <button class="btn btn-info w-100 fw-bold text-white" id="btnTransferTable">
                <i class="bi bi-arrow-left-right me-2"></i> Chuyển bàn
            </button>
        </div>
    </div>

    <!-- COLUMN 3: BILLING SUMMARY -->
    <div class="col-lg-3 pos-column">
        <h5 class="pos-section-title">
            <i class="bi bi-credit-card"></i> Hóa đơn
        </h5>
        
        <div class="receipt-card">
            <form id="orderForm" method="POST">
                <div class="receipt-header">
                    <h6 class="fw-bold mb-0">Hóa đơn</h6>
                    <small class="text-muted">Mã hóa đơn #<?= date('mdH') ?></small>
                </div>
                
                <div class="receipt-body">
                    <!-- Hidden inputs for cart items will be managed by JS -->
                    <div id="cartInputsContainer"></div>
                    <input type="hidden" name="note" id="orderNoteInput">
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="cash">
                    <input type="hidden" name="finalize_payment" id="finalizePaymentInput" value="0">

                    <!-- Mini Table for items -->
                    <div class="pos-table-container mb-2" style="max-height: 120px;">
                        <table class="table table-sm pos-table mb-0" style="font-size: 0.75rem;">
                            <thead>
                                <tr>
                                    <th>Tên món</th>
                                    <th>Giá</th>
                                    <th class="text-center">SL</th>
                                    <th class="text-end">Tiền</th>
                                </tr>
                            </thead>
                            <tbody id="receiptItems">
                                <!-- JS will populate this -->
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-2">
                        <label class="x-small fw-bold">Khách hàng:</label>
                        <input type="text" name="customer_name" class="form-control form-control-xs" placeholder="Tên khách hàng">
                    </div>

                    <div class="row g-1 mb-2">
                        <div class="col-4">
                            <label class="x-small fw-bold">Bàn:</label>
                            <select name="table_id" class="form-select form-select-xs" id="tableIdSelect">
                                <option value="">Mang về</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?= $table['id'] ?>" <?= (isset($_GET['table_id']) && $_GET['table_id'] == $table['id']) ? 'selected' : '' ?>><?= htmlspecialchars($table['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="x-small fw-bold">Ngày:</label>
                            <input type="text" class="form-control form-control-xs" value="<?= date('d/m/Y') ?>" readonly>
                        </div>
                        <div class="col-4">
                            <label class="x-small fw-bold">Giờ:</label>
                            <input type="text" class="form-control form-control-xs" value="<?= date('h:i A') ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-1 d-flex justify-content-between align-items-center">
                        <span class="x-small fw-bold text-muted text-uppercase">Thành tiền:</span>
                        <input type="text" class="form-control form-control-xs text-end border-0 bg-light w-50" id="subtotal_input" readonly value="0 ₫">
                    </div>

                    <div class="mb-1 d-flex gap-1 align-items-center">
                        <span class="x-small fw-bold text-muted text-uppercase flex-grow-1">Thuế VAT:</span>
                        <select class="form-select form-select-xs w-25 border-end-0 rounded-end-0"><option>0</option></select>
                        <input type="text" class="form-control form-control-xs text-end border-start-0 rounded-start-0 w-50" value="0 ₫" readonly>
                    </div>

                    <div class="mt-2 d-flex justify-content-between align-items-center p-2 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25">
                        <span class="fw-bold text-danger text-uppercase">Tổng cộng:</span>
                        <span id="grandTotal" class="fw-bold text-danger fs-5">0 ₫</span>
                    </div>
                </div>
                
                <div class="receipt-footer">
                    <div class="btn-pos-grid">
                        <button type="button" class="btn btn-pos bg-warning text-white border-0" onclick="window.print()">
                            <i class="bi bi-printer"></i> 
                            <span>In Order</span>
                        </button>
                        <button type="button" class="btn btn-pos bg-primary text-white border-0" style="background-color: #6366f1 !important;" data-bs-toggle="modal" data-bs-target="#noteModal">
                            <i class="bi bi-file-earmark-text"></i> 
                            <span>Ghi chú</span>
                        </button>
                        <button type="button" class="btn btn-pos bg-info text-white border-0" style="background-color: #ec4899 !important;" onclick="alert('Tính năng tách đơn đang được cập nhật!')">
                            <i class="bi bi-scissors"></i> 
                            <span>Tách đơn</span>
                        </button>
                        <button type="button" class="btn btn-pos bg-danger text-white border-0" style="background-color: #ef4444 !important;" onclick="openPaymentModalPos()">
                            <i class="bi bi-cash-stack"></i> 
                            <span>Thanh toán</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ghi chú -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-15 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Thêm ghi chú cho đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="tempNoteArea" class="form-control rounded-10 py-3" rows="4" placeholder="Nhập ghi chú tại đây... (Ví dụ: Ít đường, Không đá)"></textarea>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="saveNoteBtn">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Note Sync
    const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
    const saveNoteBtn = document.getElementById('saveNoteBtn');
    const tempNoteArea = document.getElementById('tempNoteArea');
    const orderNoteInput = document.getElementById('orderNoteInput');

    saveNoteBtn.addEventListener('click', function() {
        orderNoteInput.value = tempNoteArea.value;
        noteModal.hide();
        if(tempNoteArea.value.trim() !== "") {
            document.querySelector('[data-bs-target="#noteModal"]').classList.add('pulse-anim');
        } else {
            document.querySelector('[data-bs-target="#noteModal"]').classList.remove('pulse-anim');
        }
    });

        }
    });

    // Payment Selection POS
    const posPaymentModal = new bootstrap.Modal(document.getElementById('posPaymentModal'));
    window.openPaymentModalPos = function() {
        if(cartTable.querySelectorAll('tr').length === 0) {
            alert('Vui lòng thêm món trước khi thanh toán!');
            return;
        }
        document.getElementById('posPayTotal').innerText = grandTotalEl.innerText;
        posPaymentModal.show();
    };

    window.submitPosOrder = function(method) {
        document.getElementById('paymentMethodInput').value = method;
        document.getElementById('finalizePaymentInput').value = '1';
        document.getElementById('orderForm').submit();
    };

    const products = <?= json_encode($products) ?>;
    const cartTable = document.querySelector('#cartTable tbody');
    const subtotalEl = document.getElementById('subtotal');
    const grandTotalEl = document.getElementById('grandTotal');
    
    // Helper to format currency
    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    }

    // Add product to cart
    document.querySelectorAll('.btn-add-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const id = tr.dataset.id;
            const name = tr.dataset.name;
            const price = parseFloat(tr.dataset.price);
            const unit = tr.dataset.unit;
            
            addToCart(id, name, price, unit);
        });
    });

    function addToCart(id, name, price, unit) {
        // Check if exists
        let existingRow = cartTable.querySelector(`tr[data-id="${id}"]`);
        if (existingRow) {
            const qtyInput = existingRow.querySelector('.qty-input');
            qtyInput.value = parseInt(qtyInput.value) + 1;
            updateSubtotal(existingRow);
        } else {
            const row = document.createElement('tr');
            row.dataset.id = id;
            row.dataset.price = price;
            row.innerHTML = `
                <td>
                    <div class="fw-bold">${name}</div>
                    <input type="hidden" name="product_id[]" value="${id}">
                </td>
                <td class="text-center" style="width: 120px;">
                    <div class="input-group input-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-qty" data-action="minus">-</button>
                        <input type="number" name="quantity[]" class="form-control text-center qty-input" value="1" min="1">
                        <button type="button" class="btn btn-outline-secondary btn-qty" data-action="plus">+</button>
                    </div>
                </td>
                <td class="text-end fw-bold text-success row-subtotal">${formatVND(price)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger btn-remove-item">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </td>
            `;
            cartTable.appendChild(row);
            attachRowEvents(row);
        }
        calculateGrandTotal();
    }

    function updateSubtotal(row) {
        const price = parseFloat(row.dataset.price);
        const qty = parseInt(row.querySelector('.qty-input').value);
        row.querySelector('.row-subtotal').textContent = formatVND(price * qty);
    }

    function calculateGrandTotal() {
        let total = 0;
        let receiptHtml = '';
        let inputsHtml = '';
        
        cartTable.querySelectorAll('tr').forEach(row => {
            const id = row.dataset.id;
            const price = parseFloat(row.dataset.price);
            const qty = parseInt(row.querySelector('.qty-input').value);
            const sub = price * qty;
            total += sub;
            
            // Sync with mini receipt table
            const name = row.querySelector('.fw-bold').innerText;
            receiptHtml += `<tr><td>${name}</td><td>${formatVND(price)}</td><td class="text-center">${qty}</td><td class="text-end">${formatVND(sub)}</td></tr>`;
            
            // Create hidden inputs for the form in receipt-card
            inputsHtml += `<input type="hidden" name="product_id[]" value="${id}">`;
            inputsHtml += `<input type="hidden" name="quantity[]" value="${qty}">`;
        });
        
        if(document.getElementById('subtotal_input')) document.getElementById('subtotal_input').value = formatVND(total);
        grandTotalEl.textContent = formatVND(total);
        if(document.getElementById('receiptItems')) document.getElementById('receiptItems').innerHTML = receiptHtml;
        if(document.getElementById('cartInputsContainer')) document.getElementById('cartInputsContainer').innerHTML = inputsHtml;
    }

    function attachRowEvents(row) {
        row.querySelectorAll('.btn-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                const input = row.querySelector('.qty-input');
                let val = parseInt(input.value);
                if (action === 'plus') val++;
                else if (action === 'minus' && val > 1) val--;
                input.value = val;
                updateSubtotal(row);
                calculateGrandTotal();
            });
        });

        row.querySelector('.qty-input').addEventListener('input', function() {
            if (this.value < 1) this.value = 1;
            updateSubtotal(row);
            calculateGrandTotal();
        });

        row.querySelector('.btn-remove-item').addEventListener('click', function() {
            row.remove();
            calculateGrandTotal();
        });
    }

    // New order button logic
    document.getElementById('btnNewAtStore').addEventListener('click', () => {
        cartTable.innerHTML = '';
        calculateGrandTotal();
        document.getElementById('tableIdSelect').focus();
    });
});
</script>

<!-- Modal Thanh Toán POS -->
<div class="modal fade" id="posPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-15 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="fw-bold">Chọn hình thức thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-10 text-center mb-4">
                    <span class="text-muted text-uppercase small fw-bold">Tổng tiền thanh toán</span>
                    <h3 class="fw-bold text-danger mb-0" id="posPayTotal">0 ₫</h3>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" onclick="submitPosOrder('cash')" class="btn btn-success w-100 py-3 rounded-12 fw-bold">
                            <i class="bi bi-cash me-2"></i> Tiền mặt
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" onclick="submitPosOrder('transfer')" class="btn btn-primary w-100 py-3 rounded-12 fw-bold">
                            <i class="bi bi-qr-code me-2"></i> Chuyển khoản
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-12 { border-radius: 12px; }
    .rounded-15 { border-radius: 15px; }
</style>
<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
