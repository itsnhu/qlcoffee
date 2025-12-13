<?php
$pageTitle = 'Giỏ hàng';
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/cart.php';
    setMessage('warning', 'Vui lòng đăng nhập để xem giỏ hàng');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Get cart items
$cartItems = fetchAll($pdo, "
    SELECT c.*, m.name, m.code, m.price, m.quantity as stock, m.unit, c.quantity as cart_qty
    FROM cart c
    JOIN medicines m ON c.medicine_id = m.id
    WHERE c.customer_id = ?
    ORDER BY c.created_at DESC
", [$customerId]);

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['cart_qty']), 0);
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-cart3 me-2"></i>Giỏ hàng của bạn</h2>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x text-muted" style="font-size: 5rem;"></i>
            <h4 class="mt-3">Giỏ hàng trống</h4>
            <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center" width="120">Đơn giá</th>
                                        <th class="text-center" width="150">Số lượng</th>
                                        <th class="text-end" width="130">Thành tiền</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr id="cart-item-<?= $item['medicine_id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded p-2 me-3">
                                                        <i class="bi bi-capsule text-secondary fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $item['medicine_id'] ?>" class="text-decoration-none text-dark fw-medium">
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </a>
                                                        <div class="small text-muted"><?= htmlspecialchars($item['code']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?= formatCurrency($item['price']) ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="input-group input-group-sm justify-content-center">
                                                    <button class="btn btn-outline-secondary" onclick="updateCartQty(<?= $item['medicine_id'] ?>, -1)">-</button>
                                                    <input type="number" class="form-control text-center" style="max-width: 60px;"
                                                           id="qty-<?= $item['medicine_id'] ?>"
                                                           value="<?= $item['cart_qty'] ?>"
                                                           min="1" max="<?= $item['stock'] ?>"
                                                           onchange="updateCartQty(<?= $item['medicine_id'] ?>, 0, this.value)">
                                                    <button class="btn btn-outline-secondary" onclick="updateCartQty(<?= $item['medicine_id'] ?>, 1)">+</button>
                                                </div>
                                                <small class="text-muted">Còn <?= $item['stock'] ?> <?= htmlspecialchars($item['unit']) ?></small>
                                            </td>
                                            <td class="text-end align-middle fw-bold text-danger" id="subtotal-<?= $item['medicine_id'] ?>">
                                                <?= formatCurrency($item['price'] * $item['cart_qty']) ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button class="btn btn-link text-danger p-0" onclick="removeFromCart(<?= $item['medicine_id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
                    </a>
                    <button class="btn btn-outline-danger" onclick="clearCart()">
                        <i class="bi bi-trash me-2"></i>Xóa giỏ hàng
                    </button>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-receipt me-2"></i>Tóm tắt đơn hàng
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span id="cart-subtotal"><?= formatCurrency($cartTotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <span class="text-success">Miễn phí</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Tổng cộng:</strong>
                            <strong class="text-danger fs-5" id="cart-total"><?= formatCurrency($cartTotal) ?></strong>
                        </div>

                        <a href="<?= BASE_URL ?>/user/checkout.php" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-credit-card me-2"></i>Tiến hành thanh toán
                        </a>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>Thanh toán an toàn & bảo mật
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6><i class="bi bi-truck me-2"></i>Chính sách giao hàng</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Miễn phí giao hàng đơn từ 500.000đ</li>
                            <li>Giao hàng trong 2-5 ngày làm việc</li>
                            <li>Thanh toán khi nhận hàng (COD)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const prices = {
    <?php foreach ($cartItems as $item): ?>
    <?= $item['medicine_id'] ?>: <?= $item['price'] ?>,
    <?php endforeach; ?>
};

const maxStock = {
    <?php foreach ($cartItems as $item): ?>
    <?= $item['medicine_id'] ?>: <?= $item['stock'] ?>,
    <?php endforeach; ?>
};

function updateCartQty(medicineId, delta, newValue = null) {
    const input = document.getElementById('qty-' + medicineId);
    let qty = newValue !== null ? parseInt(newValue) : (parseInt(input.value) + delta);

    if (qty < 1) qty = 1;
    if (qty > maxStock[medicineId]) qty = maxStock[medicineId];

    input.value = qty;

    // Update subtotal
    const subtotal = qty * prices[medicineId];
    document.getElementById('subtotal-' + medicineId).textContent = formatCurrency(subtotal);

    // Send to server
    fetch('<?= BASE_URL ?>/user/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update',
            medicine_id: medicineId,
            quantity: qty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-subtotal').textContent = data.cartTotalFormatted;
            document.getElementById('cart-total').textContent = data.cartTotalFormatted;
            updateCartBadge(data.cartCount);
        }
    });
}

function removeFromCart(medicineId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;

    fetch('<?= BASE_URL ?>/user/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'remove',
            medicine_id: medicineId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-item-' + medicineId).remove();
            updateCartBadge(data.cartCount);
            if (data.cartCount == 0) {
                location.reload();
            } else {
                recalculateTotal();
            }
        }
    });
}

function clearCart() {
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;

    fetch('<?= BASE_URL ?>/user/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'clear'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

function recalculateTotal() {
    let total = 0;
    document.querySelectorAll('[id^="qty-"]').forEach(input => {
        const medicineId = input.id.replace('qty-', '');
        total += parseInt(input.value) * prices[medicineId];
    });
    document.getElementById('cart-subtotal').textContent = formatCurrency(total);
    document.getElementById('cart-total').textContent = formatCurrency(total);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
}
</script>

<?php require_once 'includes/footer.php'; ?>
