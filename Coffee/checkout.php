<?php
$pageTitle = 'Thanh toán';
require_once 'includes/customer_header.php';

// Require Login
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    echo "<script>window.location.href = 'login.php?type=customer';</script>";
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

$bookingId = $_GET['booking_id'] ?? $_POST['booking_id'] ?? null;
$booking = null;
if ($bookingId) {
    $booking = fetchOne($pdo, "
        SELECT b.*, t.name as table_name 
        FROM bookings b 
        LEFT JOIN tables t ON b.table_id = t.id 
        WHERE b.id = ?
    ", [$bookingId]);
}

// Check and add size column to order_details if not exists
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM order_details")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('size', $checkCols)) {
        $pdo->exec("ALTER TABLE order_details ADD COLUMN size VARCHAR(10) DEFAULT 'M' AFTER product_id");
    }
} catch (Exception $e) {}

// Get Cart
$cartItems = fetchAll($pdo, "
    SELECT c.quantity as cart_qty, c.size, m.id, m.name, m.price, m.price_s, m.price_m, m.price_l, m.price_xl, m.has_s, m.has_m, m.has_l, m.has_xl, m.quantity as stock
    FROM cart c
    JOIN products m ON c.product_id = m.id
    WHERE c.customer_id = ?
", [$customerId]);

// Adjust prices based on size
foreach ($cartItems as &$item) {
    if ($item['size'] === 'S' && $item['has_s']) $item['price'] = $item['price_s'];
    elseif ($item['size'] === 'M' && $item['has_m']) $item['price'] = $item['price_m'];
    elseif ($item['size'] === 'L' && $item['has_l']) $item['price'] = $item['price_l'];
    elseif ($item['size'] === 'XL' && $item['has_xl']) $item['price'] = $item['price_xl'];
}
unset($item);

if (empty($cartItems)) {
    header('Location: menu.php');
    exit;
}

$totalAmount = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['cart_qty']), 0);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = sanitize($_POST['note'] ?? '');
    $address = sanitize($_POST['address'] ?? $customer['address']);
    $phone = sanitize($_POST['phone'] ?? $customer['phone']);
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cod');

    try {
        $pdo->beginTransaction();

        // Create Order
        // Get the next sequence number
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
        $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $next_num = $total_orders + 1;

        // Format: ORD-XXX (e.g. ORD-001)
        do {
            $orderCode = 'ORD-' . sprintf('%03d', $next_num);
            $exists = fetchOne($pdo, "SELECT id FROM orders WHERE order_code = ?", [$orderCode]);
            if ($exists) $next_num++;
        } while ($exists);
        $tableId = ($booking) ? $booking['table_id'] : null;

        $sql = "INSERT INTO orders (order_code, customer_id, table_id, customer_name, customer_phone, customer_email, shipping_address, total_amount, note, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $orderCode, 
            $customerId, 
            $tableId,
            $customer['full_name'], 
            $phone, 
            $customer['email'], 
            $address, 
            $totalAmount, 
            $note, 
            $paymentMethod
        ]);
        $orderId = $pdo->lastInsertId();

        // Create Order Details
        $sqlDetail = "INSERT INTO order_details (order_id, product_id, quantity, size, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtDetail = $pdo->prepare($sqlDetail);

        foreach ($cartItems as $item) {
            $subtotal = $item['price'] * $item['cart_qty'];
            $stmtDetail->execute([
                $orderId, 
                $item['id'], 
                $item['cart_qty'], 
                $item['size'],
                $item['price'], 
                $subtotal
            ]);

            // Deduction of stock done when order is confirmed by Admin? 
            // Or immediate deduction? 
            // Usually immediate deduction for e-commerce.
            $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$item['cart_qty'], $item['id']]);
        }

        // Clear Cart
        $pdo->prepare("DELETE FROM cart WHERE customer_id = ?")->execute([$customerId]);

        // Accumulate points (e.g., 1 point per 10,000 VND)
        $earnedPoints = floor($totalAmount / 10000);
        if ($earnedPoints > 0) {
            $pdo->prepare("UPDATE customers SET points = points + ? WHERE id = ?")->execute([$earnedPoints, $customerId]);
        }

        // Update Table and Booking status
        if ($tableId) {
            $pdo->prepare("UPDATE tables SET status = 'booked' WHERE id = ?")->execute([$tableId]);
        }
        if ($bookingId) {
            $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
        }

        $pdo->commit();

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'orderCode' => $orderCode]);
            exit;
        }

        header("Location: order_success.php?code=$orderCode");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Lỗi xử lý đơn hàng: " . $e->getMessage();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}
?>

<style>
    :root {
        --invoice-bg: #ffffff;
        --invoice-shadow: 0 10px 40px rgba(0,0,0,0.08);
        --text-dark: #2d3436;
        --text-muted: #636e72;
        --accent-dark: #353535;
        --accent-light: #f8f9fa;
    }

    body {
        background-color: #f1f3f6;
    }

    .checkout-invoice {
        max-width: 600px;
        margin: 50px auto;
        background: var(--invoice-bg);
        border-radius: 30px;
        box-shadow: var(--invoice-shadow);
        padding: 40px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .invoice-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .invoice-header i {
        font-size: 2.5rem;
        color: #a29bfe;
        margin-bottom: 15px;
    }

    .invoice-title {
        font-weight: 800;
        font-size: 1.75rem;
        color: var(--text-dark);
        margin-bottom: 5px;
    }

    .invoice-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .invoice-table {
        width: 100%;
        margin-bottom: 30px;
    }

    .invoice-table th {
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        letter-spacing: 1px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f1f1;
    }

    .invoice-table td {
        padding: 20px 0;
        vertical-align: middle;
        border-bottom: 1px solid #f8f8f8;
    }

    .product-name-cell {
        font-weight: 700;
        color: var(--text-dark);
        font-size: 0.95rem;
    }

    .size-badge {
        background: none;
        color: #4a4a4a;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0;
        border-radius: 0;
        text-transform: uppercase;
    }

    .qty-cell {
        font-weight: 700;
        color: var(--text-muted);
        text-align: center;
    }

    .price-cell {
        font-weight: 800;
        color: var(--text-dark);
        text-align: right;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        margin-bottom: 40px;
        border-top: 2px dashed #eee;
    }

    .total-label {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-dark);
    }

    .total-value {
        font-size: 1.25rem;
        font-weight: 900;
        color: var(--text-dark);
    }

    .payment-section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 15px;
    }

    .payment-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }

    .payment-card {
        border: 2px solid #eee;
        border-radius: 15px;
        padding: 20px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        background: #fff;
    }

    .payment-card i {
        font-size: 1.8rem;
        color: var(--text-muted);
    }

    .payment-card span {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
    }

    /* Dynamic Payment Method Colors */
    .payment-card.active-cash {
        background-color: #E8F5E9 !important;
        border-color: #2E7D32 !important;
        color: #2E7D32 !important;
    }
    .payment-card.active-cash i, .payment-card.active-cash span {
        color: #2E7D32 !important;
    }

    .payment-card.active-wallet {
        background-color: #F3E5F5 !important;
        border-color: #8E24AA !important;
        color: #8E24AA !important;
    }
    .payment-card.active-wallet i, .payment-card.active-wallet span {
        color: #8E24AA !important;
    }

    .payment-card.active-transfer {
        background-color: #E3F2FD !important;
        border-color: #1565C0 !important;
        color: #1565C0 !important;
    }
    .payment-card.active-transfer i, .payment-card.active-transfer span {
        color: #1565C0 !important;
    }

    .confirm-btn {
        background: #333; /* Default */
        color: white;
        border: none;
        width: 100%;
        padding: 15px;
        border-radius: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }

    .confirm-btn:disabled {
        background-color: #cccccc !important;
        cursor: not-allowed;
        filter: none;
        transform: none;
        opacity: 0.7;
    }

    .confirm-btn:hover:not(:disabled) {
        filter: brightness(0.9);
        transform: translateY(-2px);
    }

    .back-shopping {
        display: block;
        text-align: center;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Transfer Screen Styles */
    .transfer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #f8f9fa;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        padding: 20px;
    }

    .transfer-overlay.show {
        display: flex;
    }

    .transfer-card {
        background: white;
        width: 100%;
        max-width: 450px;
        padding: 30px;
        border-radius: 20px;
        border: 1px solid #eee;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .qr-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        margin: 20px auto;
        max-width: 250px;
    }

    .qr-container img {
        width: 100%;
        height: auto;
    }

    .bank-info-box {
        background: #f1f4f9;
        border-radius: 12px;
        padding: 20px;
        text-align: left;
        margin-top: 20px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .info-label {
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    .info-value {
        color: #1a1a1a;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .info-value.amount {
        color: #e74c3c;
    }

    .transfer-btn-group {
        width: 100%;
        max-width: 450px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .paid-confirm-btn {
        background: #333;
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-weight: 700;
        width: 100%;
    }

    .return-btn {
        background: white;
        color: #333;
        border: 1px solid #ddd;
        padding: 14px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
    }

    /* Modal Styles */
    .success-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
    }

    .success-modal-card {
        background: white;
        width: 90%;
        max-width: 400px;
        padding: 40px 30px;
        border-radius: 35px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .success-modal-overlay.show {
        display: flex;
        opacity: 1;
    }

    .success-modal-overlay.show .success-modal-card {
        transform: scale(1);
    }

    .checkmark-circle {
        width: 80px;
        height: 80px;
        background: #4CAF50;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: white;
        font-size: 40px;
    }

    .checkmark-circle i {
        animation: checkmarkScale 0.4s ease-in-out forwards;
    }

    @keyframes checkmarkScale {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .modal-success-title {
        font-weight: 800;
        font-size: 1.6rem;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .modal-success-subtitle {
        color: #7f8c8d;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }

    .payment-method-badge {
        background: #f1f4f9;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #333;
        margin-bottom: 30px;
    }

    .go-home-btn {
        background: #333;
        color: white;
        border: none;
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .go-home-btn:hover {
        background: #000;
        color: white;
    }

    /* Validation Toast */
    .validation-toast {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #ff7675;
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        display: none;
        z-index: 10000;
    }
</style>

<div class="validation-toast" id="valToast">
    <i class="bi bi-exclamation-circle me-1"></i> Chưa thanh toán! Vui lòng chọn phương thức
</div>

<div class="success-modal-overlay" id="successModal">
    <div class="success-modal-card">
        <div class="checkmark-circle">
            <i class="bi bi-check-lg"></i>
        </div>
        <h3 class="modal-success-title">Thanh toán thành công!</h3>
        <p class="modal-success-subtitle">Cảm ơn bạn đã mua hàng tại TNT Coffee</p>
        <div class="payment-method-badge">
            <span id="modalPaymentLabel">💵 Tiền mặt</span>
        </div>
        <a href="index.php" class="go-home-btn">
            🏠 Về trang chủ
        </a>
    </div>
</div>

<div class="transfer-overlay" id="transferOverlay">
    <div class="transfer-card">
        <p class="fw-bold mb-0">Quét mã QR để chuyển khoản</p>
        <div class="qr-container">
            <img src="https://img.vietqr.io/image/PVCOMBANK-107001822762-compact.png?amount=<?= $totalAmount ?>&addInfo=TNT_Coffee_Order&accountName=HUYNH%20THI%20THANH%20THUY" alt="QR Payment">
        </div>
        <div class="bank-info-box">
            <div class="info-item">
                <span class="info-label">Ngân hàng:</span>
                <span class="info-value">PVcomBank</span>
            </div>
            <div class="info-item">
                <span class="info-label">Số tài khoản:</span>
                <span class="info-value">1070 0182 2762</span>
            </div>
            <div class="info-item">
                <span class="info-label">Người nhận:</span>
                <span class="info-value uppercase">HUỲNH THỊ THANH THÚY</span>
            </div>
            <div class="info-item">
                <span class="info-label">Số tiền:</span>
                <span class="info-value amount"><?= number_format($totalAmount, 0, ',', '.') ?> VNĐ</span>
            </div>
        </div>
    </div>
    <div class="transfer-btn-group">
        <button class="paid-confirm-btn" onclick="submitFinalOrder()">
            <i class="bi bi-check-circle-fill me-2"></i> Đã thanh toán
        </button>
        <button class="return-btn" onclick="hideTransfer()">
            ← Quay lại mua sắm
        </button>
    </div>
</div>

<div class="container">
    <div class="checkout-invoice">
        <form method="POST" id="checkoutForm" onsubmit="return confirmPayment(event)">
            <!-- Hidden inputs for existing logic -->
            <input type="hidden" name="payment_method" id="payment_method_input" value="">
            <input type="hidden" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
            <input type="hidden" name="address" value="<?= htmlspecialchars($customer['address']) ?>">
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($bookingId) ?>">

            <?php if ($booking): ?>
                <div class="alert alert-info rounded-4 border-0 shadow-sm mb-4 d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="bi bi-calendar-check text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                            Thông tin đặt bàn: <?= htmlspecialchars($booking['table_name'] ?? 'Chưa chọn bàn') ?>
                        </div>
                        <div class="text-muted small">
                            Ngày: <?= date('d/m/Y', strtotime($booking['booking_date'])) ?> | 
                            Giờ: <?= date('H:i', strtotime($booking['booking_time'])) ?> | 
                            <?= $booking['guests'] ?> khách
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="invoice-header">
                <i class="bi bi-file-earmark-text"></i>
                <h2 class="invoice-title">Hóa Đơn Thanh Toán</h2>
                <p class="invoice-subtitle">TNT Coffee - Cảm ơn quý khách!</p>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="text-start">Sản phẩm</th>
                        <th class="text-center">Size</th>
                        <th class="text-center">SL</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td class="product-name-cell"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="text-center"><span class="size-badge"><?= $item['size'] ?></span></td>
                            <td class="qty-cell"><?= $item['cart_qty'] ?></td>
                            <td class="price-cell"><?= number_format($item['price'] * $item['cart_qty'], 0, ',', '.') ?> VNĐ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-row">
                <span class="total-label">Tổng cộng:</span>
                <span class="total-value"><?= number_format($totalAmount, 0, ',', '.') ?> VNĐ</span>
            </div>

            <div class="payment-section">
                <p class="payment-section-title">Phương thức thanh toán</p>
                <div class="payment-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="payment-card" onclick="setPayment('cash', this)">
                        <i class="bi bi-wallet2"></i>
                        <span>Tiền mặt</span>
                    </div>
                    <div class="payment-card" onclick="setPayment('transfer', this)">
                        <i class="bi bi-bank"></i>
                        <span>Chuyển khoản</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="confirm-btn" id="confirm-booking-btn" style="background-color: #cccccc;">
                <i class="bi bi-check-square-fill"></i> Xác nhận thanh toán
            </button>

            <a href="menu.php" class="back-shopping">
                <i class="bi bi-arrow-left me-1"></i> Quay lại mua sắm
            </a>
        </form>
    </div>
</div>

<script>
function setPayment(method, element) {
    document.getElementById('payment_method_input').value = method;
    
    document.querySelectorAll('.payment-card').forEach(card => {
        card.classList.remove('active-cash', 'active-wallet', 'active-transfer');
    });

    const confirmBtn = document.getElementById('confirm-booking-btn');
    confirmBtn.disabled = false; 
    
    if (method === 'cash') {
        element.classList.add('active-cash');
        confirmBtn.style.backgroundColor = '#2E7D32';
        document.getElementById('modalPaymentLabel').innerText = '💵 Tiền mặt';
    } else if (method === 'transfer') {
        element.classList.add('active-transfer');
        confirmBtn.style.backgroundColor = '#1565C0';
        document.getElementById('modalPaymentLabel').innerText = '🏦 Chuyển khoản';
    }
}

async function confirmPayment(event) {
    if (event) event.preventDefault();

    const method = document.getElementById('payment_method_input').value;
    if (!method) {
        showToast();
        return false;
    }
    
    if (method === 'transfer') {
        // Show QR Screen
        document.getElementById('transferOverlay').classList.add('show');
    } else {
        // Direct cash payment
        submitFinalOrder();
    }

    return false;
}

async function submitFinalOrder() {
    const formData = new FormData(document.getElementById('checkoutForm'));
    
    try {
        const response = await fetch('checkout.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            // Hide transfer overlay if open
            hideTransfer();
            // Show Success Modal
            document.getElementById('successModal').classList.add('show');
        } else {
            alert("Lỗi khi xử lý thanh toán. Vui lòng thử lại.");
        }
    } catch (error) {
        console.error(error);
        alert("Có lỗi xảy ra trong quá trình kết nối.");
    }
}

function hideTransfer() {
    document.getElementById('transferOverlay').classList.remove('show');
}

function showToast() {
    const toast = document.getElementById('valToast');
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}
</script>

<?php require_once 'includes/customer_footer.php'; ?>
