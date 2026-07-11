<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

try {
    // Fetch all tables
    $sql = "SELECT id, name, status, capacity, created_at
            FROM tables
            ORDER BY CAST(SUBSTRING(name, 5) AS UNSIGNED) ASC, name ASC";
    $tables = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Table List Error: " . $e->getMessage());
    $tables = [];
}

// Fetch categories
try {
    $categories = fetchAll($pdo, "SELECT * FROM categories ORDER BY name ASC");
} catch (PDOException $e) { $categories = []; }

// Fetch available products
try {
    $products = fetchAll($pdo, "SELECT p.*, c.name as category_name 
                               FROM products p 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.is_available = 1 
                               ORDER BY p.name ASC");
} catch (PDOException $e) { $products = []; }

$pageTitle = 'Danh sách các bàn';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Toast notification -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="toastNotif" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ===== 3 PANELS: Thêm món | Thanh toán | Chuyển bàn ===== -->
<div class="row g-3 mb-4">
    <!-- Panel: Thêm món -->
    <div class="col-lg-5">
        <div class="pos-panel">
            <div class="pos-panel-title text-info"><i class="bi bi-plus-circle me-1"></i> Thêm món</div>
            <div class="row g-2 mb-2">
                <div class="col-5">
                    <label class="pos-label">Loại món</label>
                    <select class="form-select form-select-sm pos-input" id="selCategory">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-7">
                    <label class="pos-label">Tên món</label>
                    <select class="form-select form-select-sm pos-input" id="selProduct">
                        <option value="">-- Chọn món --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-category="<?= $p['category_id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-5">
                    <label class="pos-label">Số lượng</label>
                    <input type="number" class="form-control form-control-sm pos-input" id="txtQuantity" value="1" min="1">
                </div>
                <div class="col-7">
                    <button class="btn btn-info text-white fw-bold w-100 pos-btn" id="btnAddItem">
                        <i class="bi bi-plus-lg me-1"></i> THÊM MÓN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel: Thanh toán -->
    <div class="col-lg-4">
        <div class="pos-panel">
            <div class="pos-panel-title text-danger"><i class="bi bi-credit-card me-1"></i> Thanh toán</div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="pos-label">Tên khách</label>
                    <input type="text" class="form-control form-control-sm pos-input" id="txtCustomerName" placeholder="Tên khách hàng...">
                </div>
                <div class="col-6">
                    <label class="pos-label">Số điện thoại</label>
                    <input type="text" class="form-control form-control-sm pos-input" id="txtCustomerPhone" placeholder="Số điện thoại...">
                </div>
            </div>
            <div class="mb-2">
                <label class="pos-label">Giảm giá (%)</label>
                <input type="number" class="form-control form-control-sm pos-input" id="txtDiscount" value="0" min="0" max="100">
            </div>
            <div class="mb-2">
                <label class="pos-label">Hình thức sử dụng</label>
                <select class="form-select form-select-sm pos-input" id="selServiceType">
                    <option value="dine_in">Tại quán</option>
                    <option value="takeaway">Mang đi</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="pos-label">Phương thức thanh toán</label>
                <div class="d-flex gap-2" id="paymentMethodGroup">
                    <button type="button" class="btn btn-sm flex-fill payment-method-btn active" data-method="cash">
                        <i class="bi bi-cash-coin me-1"></i> Tiền mặt
                    </button>
                    <button type="button" class="btn btn-sm flex-fill payment-method-btn" data-method="transfer">
                        <i class="bi bi-bank me-1"></i> Chuyển khoản
                    </button>
                </div>
            </div>
            <button class="btn btn-info text-white fw-bold w-100 pos-btn" id="btnCheckout">
                <i class="bi bi-cash-stack me-1"></i> THANH TOÁN
            </button>
        </div>
    </div>

    <!-- Panel: Chuyển bàn -->
    <div class="col-lg-3">
        <div class="pos-panel">
            <div class="pos-panel-title text-success"><i class="bi bi-arrow-left-right me-1"></i> Chuyển bàn</div>
            <div class="mb-2">
                <label class="pos-label" id="lblTransferFrom">Từ '<span id="transferFromName">---</span>' đến</label>
                <select class="form-select form-select-sm pos-input" id="selTransferTo">
                    <option value="">-- Chọn bàn --</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-success text-white fw-bold w-100 pos-btn" id="btnTransfer">
                <i class="bi bi-arrow-left-right me-1"></i> CHUYỂN BÀN
            </button>
        </div>
    </div>
</div>

<!-- ===== BOTTOM: Danh sách bàn (trái) | Hoá đơn (phải) ===== -->
<div class="row g-3">
    <!-- Danh sách các bàn -->
    <div class="col-lg-5">
        <div class="pos-panel">
            <div class="pos-panel-title text-info"><i class="bi bi-grid-3x3-gap me-1"></i> Danh sách các bàn</div>
            <div class="d-flex flex-wrap gap-2" id="tableGrid">
                <?php foreach ($tables as $table):
                    $bg = '#2ec4b6'; // Trống = xanh ngọc
                    $label = 'Trống';
                    if ($table['status'] === 'busy' || $table['status'] === 'occupied') {
                        $bg = '#e8903a'; // Đã có người = cam
                        $label = 'Đang sử dụng';
                    } elseif ($table['status'] === 'booked' || $table['status'] === 'reserved') {
                        $bg = '#5b9bd5'; // Đã đặt = xanh dương
                        $label = 'Đã đặt';
                    }
                ?>
                <button class="table-btn" data-id="<?= $table['id'] ?>" data-name="<?= htmlspecialchars($table['name']) ?>" data-status="<?= $table['status'] ?>" style="background:<?= $bg ?>;">
                    <div class="table-btn-name"><?= htmlspecialchars($table['name']) ?></div>
                    <div class="table-btn-status"><?= $label ?></div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Hoá đơn của bàn -->
    <div class="col-lg-7">
        <div class="pos-panel">
            <div class="pos-panel-title text-danger">
                <i class="bi bi-receipt me-1"></i> Hoá đơn của '<span id="invoiceTableName">---</span>'
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" id="invoiceTable">
                    <thead class="table-light">
                        <tr>
                            <th>Tên</th>
                            <th style="width:80px;">Số lượng</th>
                            <th style="width:110px;">Giá</th>
                            <th style="width:120px;">Tổng tiền</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="invoiceBody">
                        <tr id="emptyRow">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                Chọn một bàn để xem hoá đơn
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" id="totalRow" style="display:none!important;">
                <span class="fw-bold text-muted">TỔNG CỘNG:</span>
                <span class="fw-bold fs-5 text-danger" id="totalAmount">0 ₫</span>
            </div>
        </div>
    </div>
</div>

<style>
    /* ===== POS Panel Styling ===== */
    .pos-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        height: 100%;
    }
    .pos-panel-title {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .pos-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.2rem;
        display: block;
    }
    .pos-input {
        border-radius: 6px !important;
        border-color: #d1d5db !important;
        font-size: 0.85rem;
    }
    .pos-input:focus {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15) !important;
    }
    .pos-btn {
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        margin-top: 0.25rem;
        transition: all 0.2s ease;
    }
    .pos-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* ===== Table Buttons ===== */
    .table-btn {
        border: none;
        border-radius: 10px;
        color: white;
        padding: 0.65rem 1rem;
        min-width: 110px;
        cursor: pointer;
        transition: all 0.25s ease;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }
    .table-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    .table-btn.active {
        outline: 3px solid #1e293b;
        outline-offset: 2px;
        transform: scale(1.05);
    }
    .table-btn-name {
        font-weight: 800;
        font-size: 0.9rem;
    }
    .table-btn-status {
        font-size: 0.7rem;
        opacity: 0.9;
        margin-top: 2px;
    }

    /* ===== Invoice Table ===== */
    #invoiceTable th {
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    #invoiceTable td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .btn-del-item {
        border: none;
        background: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1rem;
        padding: 2px 6px;
        border-radius: 4px;
        transition: background 0.2s;
    }
    .btn-del-item:hover {
        background: #fef2f2;
    }

    /* ===== Payment Method Buttons ===== */
    .payment-method-btn {
        border: 2px solid #d1d5db;
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 0.45rem 0.75rem;
        border-radius: 8px;
        transition: all 0.25s ease;
    }
    .payment-method-btn:hover {
        border-color: #94a3b8;
        color: #334155;
        background: #f1f5f9;
    }
    .payment-method-btn.active {
        border-color: #10b981;
        background: #ecfdf5;
        color: #059669;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    
    /* DataTables overrides */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }
</style>

<?php
$additionalJS = '
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
const BASE = "' . BASE_URL . '";
let selectedTableId = null;
let selectedTableName = "";
let selectedPaymentMethod = "cash";
let toastObj = null;

document.addEventListener("DOMContentLoaded", function() {
    const toastEl = document.getElementById("toastNotif");
    if (toastEl) toastObj = new bootstrap.Toast(toastEl, { delay: 3000 });

    function showToast(msg, type = "success") {
        if (!toastEl || !toastObj) return;
        toastEl.className = "toast align-items-center text-white border-0 bg-" + (type === "success" ? "success" : "danger");
        document.getElementById("toastMsg").textContent = msg;
        toastObj.show();
    }

    function formatMoney(n) {
        return new Intl.NumberFormat("vi-VN").format(n) + " ₫";
    }

    window.selectTable = function(btn) {
        document.querySelectorAll(".table-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        selectedTableId = parseInt(btn.dataset.id);
        selectedTableName = btn.dataset.name;
        
        document.getElementById("invoiceTableName").textContent = selectedTableName;
        document.getElementById("transferFromName").textContent = selectedTableName;

        loadInvoice();
    };

    window.loadInvoice = function() {
        if (!selectedTableId) return;

        fetch(BASE + "/ajax/table_order.php?action=get_order&table_id=" + selectedTableId)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById("invoiceBody");
                const totalRow = document.getElementById("totalRow");

                if (!data.success || !data.order || data.items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>Bàn này chưa có món nào</td></tr>`;
                    totalRow.style.setProperty("display", "none", "important");
                    document.getElementById("totalAmount").textContent = "0 ₫";
                    document.getElementById("txtCustomerName").value = "";
                    document.getElementById("txtCustomerPhone").value = "";
                    return;
                }

                if (data.order) {
                    document.getElementById("txtCustomerName").value = data.order.customer_name || "";
                    document.getElementById("txtCustomerPhone").value = data.order.customer_phone || "";
                }

                let html = "";
                let total = 0;
                data.items.forEach(item => {
                    total += parseFloat(item.subtotal);
                    html += `<tr>
                        <td class="fw-bold">${item.product_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">${formatMoney(item.price)}</td>
                        <td class="text-end fw-bold text-danger">${formatMoney(item.subtotal)}</td>
                        <td class="text-center">
                            <button class="btn-del-item" onclick="deleteItem(${item.id})" title="Xoá"><i class="bi bi-x-lg"></i></button>
                        </td>
                    </tr>`;
                });
                tbody.innerHTML = html;
                totalRow.style.setProperty("display", "flex", "important");
                document.getElementById("totalAmount").textContent = formatMoney(total);

                if ($.fn.DataTable.isDataTable("#invoiceTable")) {
                    $("#invoiceTable").DataTable().destroy();
                }
                $("#invoiceTable").DataTable({
                    paging: true,
                    pageLength: 5,
                    searching: true,
                    info: true,
                    ordering: true,
                    language: {
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiện thị _MENU_ dữ liệu",
                        info: "Hiển thị _START_ - _END_ / _TOTAL_",
                        paginate: { previous: "‹", next: "›" },
                        zeroRecords: "Không có dữ liệu",
                        emptyTable: "Chưa có món"
                    }
                });
            })
            .catch(err => console.error(err));
    };

    let customerUpdateTimer = null;
    function updateCustomerInfo() {
        if (!selectedTableId) return;
        const fd = new FormData();
        fd.append("action", "update_customer");
        fd.append("table_id", selectedTableId);
        fd.append("customer_name", document.getElementById("txtCustomerName").value);
        fd.append("customer_phone", document.getElementById("txtCustomerPhone").value);
        fetch(BASE + "/ajax/table_order.php", { method: "POST", body: fd });
    }

    document.getElementById("txtCustomerName").addEventListener("input", function() {
        clearTimeout(customerUpdateTimer);
        customerUpdateTimer = setTimeout(updateCustomerInfo, 500);
    });

    document.getElementById("txtCustomerPhone").addEventListener("input", function() {
        clearTimeout(customerUpdateTimer);
        customerUpdateTimer = setTimeout(updateCustomerInfo, 500);
    });

    document.getElementById("selCategory").addEventListener("change", function() {
        const catId = this.value;
        const opts = document.getElementById("selProduct").options;
        for (let i = 1; i < opts.length; i++) {
            if (!catId || opts[i].dataset.category === catId) {
                opts[i].style.display = "";
            } else {
                opts[i].style.display = "none";
            }
        }
        document.getElementById("selProduct").value = "";
    });

    document.getElementById("btnAddItem").addEventListener("click", function() {
        if (!selectedTableId) { showToast("Vui lòng chọn bàn trước!", "error"); return; }
        const productId = document.getElementById("selProduct").value;
        const quantity = document.getElementById("txtQuantity").value;
        if (!productId) { showToast("Vui lòng chọn món!", "error"); return; }

        const fd = new FormData();
        fd.append("action", "add_item");
        fd.append("table_id", selectedTableId);
        fd.append("product_id", productId);
        fd.append("quantity", quantity);

        fetch(BASE + "/ajax/table_order.php", { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    loadInvoice();
                    const btn = document.querySelector(`.table-btn[data-id="${selectedTableId}"]`);
                    if (btn) {
                        btn.style.background = "#e8903a";
                        btn.querySelector(".table-btn-status").textContent = "Đang sử dụng";
                        btn.dataset.status = "busy";
                    }
                    document.getElementById("txtQuantity").value = 1;
                } else {
                    showToast(data.message, "error");
                }
            });
    });

    document.querySelectorAll(".payment-method-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.querySelectorAll(".payment-method-btn").forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            selectedPaymentMethod = this.dataset.method;
        });
    });

    document.getElementById("btnCheckout").addEventListener("click", function() {
        if (!selectedTableId) { showToast("Vui lòng chọn bàn trước!", "error"); return; }
        const methodLabel = selectedPaymentMethod === "cash" ? "Tiền mặt" : "Chuyển khoản";
        if (!confirm("Xác nhận thanh toán cho " + selectedTableName + " (" + methodLabel + ")?")) return;

        const fd = new FormData();
        fd.append("action", "checkout");
        fd.append("table_id", selectedTableId);
        fd.append("discount", document.getElementById("txtDiscount").value);
        fd.append("service_type", document.getElementById("selServiceType").value);
        fd.append("payment_method", selectedPaymentMethod);
        fd.append("customer_name", document.getElementById("txtCustomerName").value);
        fd.append("customer_phone", document.getElementById("txtCustomerPhone").value);

        fetch(BASE + "/ajax/table_order.php", { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    loadInvoice();
                    const btn = document.querySelector(`.table-btn[data-id="${selectedTableId}"]`);
                    if (btn) {
                        btn.style.background = "#2ec4b6";
                        btn.querySelector(".table-btn-status").textContent = "Trống";
                        btn.dataset.status = "free";
                    }
                    document.getElementById("txtDiscount").value = 0;
                    document.getElementById("txtCustomerName").value = "";
                    document.getElementById("txtCustomerPhone").value = "";
                } else {
                    showToast(data.message, "error");
                }
            });
    });

    document.getElementById("btnTransfer").addEventListener("click", function() {
        if (!selectedTableId) { showToast("Vui lòng chọn bàn nguồn trước!", "error"); return; }
        const toId = document.getElementById("selTransferTo").value;
        if (!toId) { showToast("Vui lòng chọn bàn đích!", "error"); return; }
        if (parseInt(toId) === selectedTableId) { showToast("Bàn đích phải khác bàn nguồn!", "error"); return; }

        const fd = new FormData();
        fd.append("action", "transfer");
        fd.append("from_table_id", selectedTableId);
        fd.append("to_table_id", toId);

        fetch(BASE + "/ajax/table_order.php", { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    const srcBtn = document.querySelector(`.table-btn[data-id="${selectedTableId}"]`);
                    if (srcBtn) {
                        srcBtn.style.background = "#2ec4b6";
                        srcBtn.querySelector(".table-btn-status").textContent = "Trống";
                        srcBtn.dataset.status = "free";
                    }
                    const destBtn = document.querySelector(`.table-btn[data-id="${toId}"]`);
                    if (destBtn) {
                        destBtn.style.background = "#e8903a";
                        destBtn.querySelector(".table-btn-status").textContent = "Đang sử dụng";
                        destBtn.dataset.status = "busy";
                        selectTable(destBtn);
                    }
                } else {
                    showToast(data.message, "error");
                }
            });
    });

    window.deleteItem = function(itemId) {
        if (!confirm("Xoá món này khỏi hoá đơn?")) return;
        const fd = new FormData();
        fd.append("action", "delete_item");
        fd.append("item_id", itemId);

        fetch(BASE + "/ajax/table_order.php", { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    loadInvoice();
                    const btn = document.querySelector(`.table-btn[data-id="${selectedTableId}"]`);
                    fetch(BASE + "/ajax/table_order.php?action=get_order&table_id=" + selectedTableId)
                        .then(r => r.json())
                        .then(check => {
                            if (!check.order && btn) {
                                btn.style.background = "#2ec4b6";
                                btn.querySelector(".table-btn-status").textContent = "Trống";
                                btn.dataset.status = "free";
                            }
                        });
                } else {
                    showToast(data.message, "error");
                }
            });
    };

    document.querySelectorAll(".table-btn").forEach(btn => {
        btn.addEventListener("click", function() { selectTable(this); });
    });

    const firstBtn = document.querySelector(".table-btn");
    if (firstBtn) selectTable(firstBtn);
});
</script>
';
require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
