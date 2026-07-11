<?php
$pageTitle = 'Giỏ hàng';
require_once 'includes/customer_header.php';

$userId = $_SESSION['customer_id'] ?? null;
$cartItems = [];
$cartTotal = 0;

if ($userId) {
    // DB Cart
    $cartItems = fetchAll($pdo, "
        SELECT c.quantity as cart_qty, c.size, m.id, m.name, m.price, m.price_s, m.price_m, m.price_l, m.price_xl, m.has_s, m.has_m, m.has_l, m.has_xl, m.image, m.quantity as stock, m.unit
        FROM cart c
        JOIN products m ON c.product_id = m.id
        WHERE c.customer_id = ?
    ", [$userId]);
    
    // Adjust prices based on size
    foreach ($cartItems as &$item) {
        if ($item['size'] === 'S' && $item['has_s']) $item['price'] = $item['price_s'];
        elseif ($item['size'] === 'M' && $item['has_m']) $item['price'] = $item['price_m'];
        elseif ($item['size'] === 'L' && $item['has_l']) $item['price'] = $item['price_l'];
        elseif ($item['size'] === 'XL' && $item['has_xl']) $item['price'] = $item['price_xl'];
    }
    unset($item);
} else {
    // Session Cart
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            $p = fetchOne($pdo, "SELECT * FROM products WHERE id = ?", [$item['id']]);
            if ($p) {
                $p['cart_qty'] = $item['quantity'];
                $p['size'] = $item['size'];
                $p['stock'] = $p['quantity'];
                
                // Adjust price
                if ($item['size'] === 'S' && $p['has_s']) $p['price'] = $p['price_s'];
                elseif ($item['size'] === 'M' && $p['has_m']) $p['price'] = $p['price_m'];
                elseif ($item['size'] === 'L' && $p['has_l']) $p['price'] = $p['price_l'];
                elseif ($item['size'] === 'XL' && $p['has_xl']) $p['price'] = $p['price_xl'];
                
                $cartItems[] = $p;
            }
        }
    }
}

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['cart_qty']), 0);
?>

<div class="container py-5">
    <h2 class="mb-4 fw-bold font-heading text-center" style="color: var(--color-coffee-dark);">Giỏ Hàng Của Bạn</h2>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <div class="mb-3 opacity-25" style="font-size: 5rem;">🛒</div>
            <h4 class="mt-4 fw-bold font-heading">Giỏ hàng trống</h4>
            <p class="text-muted mb-4">Bạn chưa chọn món nào. Hãy xem thực đơn nhé!</p>
            <a href="menu.php" class="btn btn-premium shadow-sm">
                <i class="bi bi-cup-hot me-2"></i>Xem Thực Đơn
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead style="background-color: var(--color-beige);">
                                    <tr>
                                        <th class="ps-4 py-3 font-subheading text-dark">Món</th>
                                        <th class="text-center py-3 font-subheading text-dark">Giá</th>
                                        <th class="text-center py-3 font-subheading text-dark">Số lượng</th>
                                        <th class="text-end pe-4 py-3 font-subheading text-dark">Tổng</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                    <tr id="cart-item-<?= $item['id'] ?>-<?= $item['size'] ?>">
                                        <td class="ps-4 py-3">
                                             <div class="d-flex align-items-center gap-3">
                                                 <img src="<?= !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/100?text=Coffee' ?>" 
                                                      class="rounded-3 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                                 <div>
                                                     <h6 class="mb-1 fw-bold font-cinzel text-dark">
                                                         <a href="product.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                                             <?= htmlspecialchars($item['name']) ?>
                                                         </a>
                                                     </h6>
                                                     <div class="d-flex gap-2">
                                                         <small class="text-muted"><?= htmlspecialchars($item['unit'] ?? '') ?></small>
                                                         <span class="badge bg-light text-dark border rounded-pill px-2" style="font-size: 0.7rem;">Size <?= $item['size'] ?></span>
                                                     </div>
                                                 </div>
                                             </div>
                                         </td>
                                         <td class="text-center fw-medium text-muted">
                                             <?= number_format($item['price'], 0, ',', '.') ?>đ
                                         </td>
                                         <td class="text-center">
                                             <div class="input-group input-group-sm justify-content-center border rounded-pill overflow-hidden" style="width: 120px; margin: 0 auto;">
                                                 <button class="btn btn-light border-0" onclick="updateCart(<?= $item['id'] ?>, -1, '<?= $item['size'] ?>')"><i class="bi bi-dash"></i></button>
                                                 <input type="number" class="form-control text-center border-0 bg-white fw-bold" 
                                                        id="qty-<?= $item['id'] ?>-<?= $item['size'] ?>" value="<?= $item['cart_qty'] ?>" readonly>
                                                <button class="btn btn-light border-0" onclick="updateCart(<?= $item['id'] ?>, 1, '<?= $item['size'] ?>')"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4 fw-bold" style="color: var(--color-coffee-dark);" id="subtotal-<?= $item['id'] ?>-<?= $item['size'] ?>">
                                            <?= number_format($item['price'] * $item['cart_qty'], 0, ',', '.') ?>đ
                                        </td>
                                        <td class="text-center">
                                            <button onclick="removeFromCart(<?= $item['id'] ?>, '<?= $item['size'] ?>')" class="btn btn-link text-danger p-0 opacity-50 hover-opacity-100">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 sticky-top" style="top: 100px; background-color: white;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4 font-heading text-center">Tổng Quan Đơn Hàng</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tạm tính</span>
                            <span class="fw-bold" id="cart-subtotal"><?= number_format($cartTotal, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Giảm giá</span>
                            <span class="fw-bold text-success">0đ</span>
                        </div>
                        <hr class="border-secondary opacity-10">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5 fw-bold mb-0 font-heading">Tổng cộng</span>
                            <span class="h4 fw-bold mb-0" style="color: var(--color-gold);" id="cart-total"><?= number_format($cartTotal, 0, ',', '.') ?>đ</span>
                        </div>

                        <?php if ($userId): ?>
                            <a href="booking.php?from_cart=1" class="btn btn-premium w-100 shadow-sm mb-3 justify-content-center">
                                Đặt Bàn & Thanh Toán
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=booking.php?from_cart=1" class="btn btn-premium w-100 shadow-sm mb-3 justify-content-center">
                                Đăng Nhập Để Đặt Bàn
                            </a>
                        <?php endif; ?>
                            
                        <a href="menu.php" class="btn btn-premium-outline w-100 justify-content-center">
                            <i class="bi bi-arrow-left me-2"></i>Tiếp tục chọn món
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Custom Confirmation Modal -->
<div class="delete-modal-overlay" id="deleteModal">
    <div class="delete-modal-card">
        <div class="delete-icon-circle">
            <i class="bi bi-trash3"></i>
        </div>
        <h3 class="modal-delete-title">Đã xóa sản phẩm</h3>
        <p class="modal-delete-subtitle">Bạn có chắc chắn muốn xóa món này khỏi giỏ hàng không?</p>
        <div class="modal-delete-actions">
            <button class="modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="modal-btn-confirm" id="confirmDeleteBtn">OK</button>
        </div>
    </div>
</div>

<style>
    .delete-modal-overlay {
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

    .delete-modal-card {
        background: white;
        width: 90%;
        max-width: 400px;
        padding: 40px 30px;
        border-radius: 30px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .delete-modal-overlay.show {
        display: flex;
        opacity: 1;
    }

    .delete-modal-overlay.show .delete-modal-card {
        transform: scale(1);
    }

    .delete-icon-circle {
        width: 70px;
        height: 70px;
        background: #fee2e2;
        color: #ef4444;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 30px;
    }

    .modal-delete-title {
        font-weight: 800;
        font-size: 1.4rem;
        color: #1a1a1a;
        margin-bottom: 10px;
    }

    .modal-delete-subtitle {
        color: #6b7280;
        font-size: 0.95rem;
        margin-bottom: 30px;
        line-height: 1.5;
    }

    .modal-delete-actions {
        display: flex;
        gap: 12px;
    }

    .modal-btn-cancel {
        flex: 1;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #374151;
        font-weight: 600;
        transition: all 0.2s;
    }

    .modal-btn-confirm {
        flex: 1;
        padding: 12px;
        border-radius: 12px;
        border: none;
        background: #ef4444;
        color: white;
        font-weight: 600;
        transition: all 0.2s;
    }

    .modal-btn-confirm:hover {
        background: #dc2626;
    }

    .modal-btn-cancel:hover {
        background: #f9fafb;
    }

    /* Success Modal Styles */
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
        border-radius: 30px;
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

    .success-icon-circle {
        width: 70px;
        height: 70px;
        background: #ecfdf5;
        color: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 30px;
    }

    .modal-success-title {
        font-weight: 800;
        font-size: 1.4rem;
        color: #1a1a1a;
        margin-bottom: 30px;
    }
</style>

<!-- Success Modal -->
<div class="success-modal-overlay" id="deleteSuccessModal">
    <div class="success-modal-card">
        <div class="success-icon-circle">
            <i class="bi bi-check-lg"></i>
        </div>
        <h3 class="modal-success-title">Đã xóa sản phẩm</h3>
        <button class="modal-btn-confirm w-100" onclick="location.reload()">OK</button>
    </div>
</div>

<script>
function updateCart(productId, delta, size = 'M') {
    const qtyInput = document.getElementById('qty-' + productId + '-' + size);
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + delta;
    
    if (newQty < 1) return;

    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: newQty,
            size: size
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            qtyInput.value = newQty;
            document.getElementById('cart-subtotal').textContent = data.cartTotalFormatted;
            document.getElementById('cart-total').textContent = data.cartTotalFormatted;
            
            // Should verify if we can get single item subtotal from server or calc locally.
            // For simplicity, reloading or implementing complex JS.
            // Let's reload to be safe and simple for now as per "simple" instruction, 
            // OR calculate locally if prices are available.
            location.reload(); 
        } else {
            alert(data.message || 'Lỗi cập nhật');
        }
    });
}

let productToDelete = null;
let sizeToDelete = null;

function removeFromCart(productId, size = 'M') {
    productToDelete = productId;
    sizeToDelete = size;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    productToDelete = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!productToDelete) return;

    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'remove',
            product_id: productToDelete,
            size: sizeToDelete
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            document.getElementById('deleteSuccessModal').classList.add('show');
        } else {
            alert(data.message || 'Lỗi khi xóa món');
            closeDeleteModal();
        }
    });
});
</script>

<?php require_once 'includes/customer_footer.php'; ?>
