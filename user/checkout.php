<?php
$pageTitle = 'Thanh toán';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Mailer.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/checkout.php';
    setMessage('warning', 'Vui lòng đăng nhập để thanh toán');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Get customer info
$customer = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);

// Get cart items
$cartItems = fetchAll($pdo, "
    SELECT c.*, m.name, m.code, m.price, m.quantity as stock, m.unit, c.quantity as cart_qty
    FROM cart c
    JOIN medicines m ON c.medicine_id = m.id
    WHERE c.customer_id = ?
", [$customerId]);

if (empty($cartItems)) {
    setMessage('warning', 'Giỏ hàng trống');
    header('Location: ' . BASE_URL . '/user/cart.php');
    exit;
}

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['cart_qty']), 0);

$errors = [];

// Process checkout BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $customerPhone = sanitize($_POST['customer_phone'] ?? '');
    $customerEmail = sanitize($_POST['customer_email'] ?? '');
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    $note = sanitize($_POST['note'] ?? '');

    // Validation
    if (empty($customerName)) $errors['customer_name'] = 'Vui lòng nhập họ tên';
    if (empty($customerPhone)) $errors['customer_phone'] = 'Vui lòng nhập số điện thoại';
    if (empty($customerEmail)) $errors['customer_email'] = 'Vui lòng nhập email';
    if (empty($shippingAddress)) $errors['shipping_address'] = 'Vui lòng nhập địa chỉ giao hàng';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Check stock again
            foreach ($cartItems as $item) {
                $medicine = fetchOne($pdo, "SELECT quantity FROM medicines WHERE id = ? FOR UPDATE", [$item['medicine_id']]);
                if ($medicine['quantity'] < $item['cart_qty']) {
                    throw new Exception("Sản phẩm '{$item['name']}' không đủ số lượng trong kho");
                }
            }

            // Generate order code
            $orderCode = 'DH' . date('YmdHis') . rand(100, 999);

            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_code, customer_id, customer_name, customer_phone, customer_email, shipping_address, total_amount, note, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending')
            ");
            $stmt->execute([$orderCode, $customerId, $customerName, $customerPhone, $customerEmail, $shippingAddress, $cartTotal, $note]);
            $orderId = $pdo->lastInsertId();

            // Create order details and update stock
            foreach ($cartItems as $item) {
                $subtotal = $item['price'] * $item['cart_qty'];

                // Insert order detail
                executeQuery($pdo, "
                    INSERT INTO order_details (order_id, medicine_id, quantity, price, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ", [$orderId, $item['medicine_id'], $item['cart_qty'], $item['price'], $subtotal]);

                // Update stock
                executeQuery($pdo, "
                    UPDATE medicines SET quantity = quantity - ? WHERE id = ?
                ", [$item['cart_qty'], $item['medicine_id']]);
            }

            // Clear cart
            executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ?", [$customerId]);

            $pdo->commit();

            // Get order for email
            $order = fetchOne($pdo, "SELECT * FROM orders WHERE id = ?", [$orderId]);
            $orderDetails = fetchAll($pdo, "
                SELECT od.*, m.name as medicine_name
                FROM order_details od
                JOIN medicines m ON od.medicine_id = m.id
                WHERE od.order_id = ?
            ", [$orderId]);

            // Send email (don't block on failure)
            try {
                $mailer = new Mailer();
                $mailer->sendOrderConfirmation($order, $orderDetails);
            } catch (Exception $e) {
                error_log("Email Error: " . $e->getMessage());
            }

            // Redirect BEFORE any output
            setMessage('success', 'Đặt hàng thành công! Mã đơn hàng: ' . $orderCode);
            header('Location: ' . BASE_URL . '/user/order-success.php?code=' . $orderCode);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Checkout Error: " . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    }
}

// Now include header (after all redirects are handled)
require_once 'includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-credit-card me-2"></i>Thanh toán</h2>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger"><?= $errors['general'] ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-lg-7">
                <!-- Shipping Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-geo-alt me-2"></i>Thông tin giao hàng
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>"
                                       name="customer_name" value="<?= htmlspecialchars($_POST['customer_name'] ?? $customer['full_name']) ?>">
                                <?php if (isset($errors['customer_name'])): ?>
                                    <div class="invalid-feedback"><?= $errors['customer_name'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?= isset($errors['customer_phone']) ? 'is-invalid' : '' ?>"
                                       name="customer_phone" value="<?= htmlspecialchars($_POST['customer_phone'] ?? $customer['phone']) ?>">
                                <?php if (isset($errors['customer_phone'])): ?>
                                    <div class="invalid-feedback"><?= $errors['customer_phone'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?= isset($errors['customer_email']) ? 'is-invalid' : '' ?>"
                                   name="customer_email" value="<?= htmlspecialchars($_POST['customer_email'] ?? $customer['email']) ?>">
                            <?php if (isset($errors['customer_email'])): ?>
                                <div class="invalid-feedback"><?= $errors['customer_email'] ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Email xác nhận đơn hàng sẽ được gửi đến địa chỉ này</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= isset($errors['shipping_address']) ? 'is-invalid' : '' ?>"
                                      name="shipping_address" rows="2"><?= htmlspecialchars($_POST['shipping_address'] ?? $customer['address']) ?></textarea>
                            <?php if (isset($errors['shipping_address'])): ?>
                                <div class="invalid-feedback"><?= $errors['shipping_address'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú về đơn hàng (tùy chọn)"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-wallet2 me-2"></i>Phương thức thanh toán
                    </div>
                    <div class="card-body">
                        <div class="form-check p-3 border rounded bg-light">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                            <label class="form-check-label w-100" for="cod">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cash-coin text-success fs-4 me-3"></i>
                                    <div>
                                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                                        <div class="small text-muted">Thanh toán bằng tiền mặt khi nhận hàng</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mt-4 mt-lg-0">
                <!-- Order Summary -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-receipt me-2"></i>Đơn hàng của bạn
                    </div>
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-2">
                                        <i class="bi bi-capsule text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($item['name']) ?></div>
                                        <small class="text-muted">x<?= $item['cart_qty'] ?> <?= htmlspecialchars($item['unit']) ?></small>
                                    </div>
                                </div>
                                <strong><?= formatCurrency($item['price'] * $item['cart_qty']) ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span><?= formatCurrency($cartTotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <span class="text-success">Miễn phí</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong class="fs-5">Tổng cộng:</strong>
                            <strong class="text-danger fs-4"><?= formatCurrency($cartTotal) ?></strong>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Đặt hàng
                        </button>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                Bằng việc đặt hàng, bạn đồng ý với <a href="#">Điều khoản dịch vụ</a>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/user/cart.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
