<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/customer_header.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: menu.php');
    exit;
}

// Get product details
$product = fetchOne($pdo, "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
", [$productId]);

if (!$product) {
    echo "<div class='container py-5 text-center'><h3>Sản phẩm không tồn tại</h3><a href='menu.php' class='btn btn-primary'>Quay lại thực đơn</a></div>";
    require_once 'includes/customer_footer.php';
    exit;
}

$pageTitle = $product['name'];

// Get related products
$relatedProducts = fetchAll($pdo, "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.quantity > 0
    ORDER BY RAND()
    LIMIT 4
", [$product['category_id'], $productId]);

// Check availability
$isAvailable = $product['quantity'] > 0;
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-5">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="menu.php" class="text-decoration-none text-muted">Thực đơn</a></li>
            <li class="breadcrumb-item"><a href="menu.php?category=<?= $product['category_id'] ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <!-- Product Image: 4/12 width -->
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                <img src="<?= !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/600x600?text=No+Image' ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="img-fluid w-100"
                     style="height: 380px; object-fit: cover;">
                
                <?php if (!$isAvailable): ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50">
                        <span class="badge bg-danger fs-3 px-4 py-2 rounded-pill shadow">Hết hàng</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info: 6/12 width -->
        <div class="col-lg-5 col-md-7">
            <div class="ps-lg-3 d-flex flex-column h-100">
                <div class="mb-auto">
                    <span class="badge bg-primary-subtle text-primary mb-2 px-3 py-1 rounded-pill small fw-bold" style="font-size: 0.75rem;"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h2 class="fw-bold mb-2" style="color: var(--primary-color);"><?= htmlspecialchars($product['name']) ?></h2>
                    
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="h4 fw-bold text-primary" id="productPrice"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                    </div>

                    <!-- Size Selection -->
                    <div id="sizeSelectionContainer" class="mb-4">
                        <label class="form-label fw-bold mb-2">Chọn kích thước:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="radio" class="btn-check" name="product_size" id="size_s" value="S" data-price="<?= ($product['has_s'] && $product['price_s'] > 0) ? $product['price_s'] : $product['price'] ?>">
                            <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_s" style="min-width: 60px;">S</label>

                            <input type="radio" class="btn-check" name="product_size" id="size_m" value="M" data-price="<?= ($product['has_m'] && $product['price_m'] > 0) ? $product['price_m'] : $product['price'] ?>" checked>
                            <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_m" style="min-width: 60px;">M</label>

                            <input type="radio" class="btn-check" name="product_size" id="size_l" value="L" data-price="<?= ($product['has_l'] && $product['price_l'] > 0) ? $product['price_l'] : $product['price'] ?>">
                            <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_l" style="min-width: 60px;">L</label>

                            <?php if ($product['has_xl']): ?>
                                <input type="radio" class="btn-check" name="product_size" id="size_xl" value="XL" data-price="<?= $product['price_xl'] ?>">
                                <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_xl" style="min-width: 60px;">XL</label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="product-description mb-4">
                        <p class="text-muted lh-base small mb-0">
                            <?= !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'Hương vị tuyệt hảo mang đến trải nghiệm cà phê đích thực cho thực khách.' ?>
                        </p>
                    </div>
                </div>

                <?php if ($isAvailable): ?>
                    <div class="d-flex align-items-center gap-3 mb-4 pt-4 border-top">
                        <div class="input-group border rounded-pill bg-light" style="width: 110px; overflow: hidden;">
                            <button class="btn btn-link text-dark text-decoration-none px-2 py-0" type="button" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                            <input type="number" class="form-control text-center border-0 bg-transparent fw-bold small p-0" id="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" readonly>
                            <button class="btn btn-link text-dark text-decoration-none px-2 py-0" type="button" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
                        </div>
                        <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('quantity').value, document.querySelector('input[name=\'product_size\']:checked') ? document.querySelector('input[name=\'product_size\']:checked').value : 'M')" 
                                class="btn btn-primary rounded-pill px-4 flex-grow-1 shadow-sm fw-bold d-flex align-items-center justify-content-center gap-2 py-2">
                            <i class="bi bi-bag-plus fs-5"></i>Thêm vào giỏ
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning rounded-4 mb-5">
                        <i class="bi bi-exclamation-circle me-2"></i>Sản phẩm tạm thời hết hàng
                    </div>
                <?php endif; ?>

                <div class="row g-3 pt-3 mt-auto border-top">
                    <div class="col-6">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="bg-primary-subtle p-2 rounded-circle text-primary">
                                <i class="bi bi-cup-hot fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 small">Chất lượng</h6>
                                <p class="x-small text-muted mb-0">Nguyên liệu cao cấp</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="bg-primary-subtle p-2 rounded-circle text-primary">
                                <i class="bi bi-clock-history fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 small">Tươi mới</h6>
                                <p class="x-small text-muted mb-0">Pha chế tức thì</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="mt-5 pt-5">
            <h3 class="fw-bold mb-4" style="color: var(--primary-color);">Có thể bạn cũng thích</h3>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm product-card hover-card rounded-4">
                            <div class="position-relative">
                                <img src="<?= !empty($related['image']) ? $related['image'] : 'https://via.placeholder.com/300x300?text=No+Image' ?>"
                                     class="card-img-top"
                                     alt="<?= htmlspecialchars($related['name']) ?>"
                                     style="height: 220px; object-fit: cover;">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                     <span class="badge bg-light text-dark border small rounded-pill"><?= htmlspecialchars($related['category_name']) ?></span>
                                </div>
                                <h6 class="card-title fw-bold mb-2">
                                    <a href="javascript:void(0)" onclick="showProductModal(<?= $related['id'] ?>)" class="text-decoration-none text-dark stretched-link">
                                        <?= htmlspecialchars($related['name']) ?>
                                    </a>
                                </h6>
                                <p class="card-text text-muted small line-clamp-2 mb-3">
                                    <?= htmlspecialchars($related['description'] ?? 'Hương vị thơm ngon khó cưỡng...') ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-auto position-relative" style="z-index: 2;">
                                    <span class="fw-bold text-primary"><?= number_format($related['price'], 0, ',', '.') ?>đ</span>
                                    <button onclick="addToCart(<?= $related['id'] ?>)" class="btn btn-primary btn-sm rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
    }
    .btn-icon {
        width: 35px; 
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > <?= $product['quantity'] ?>) val = <?= $product['quantity'] ?>;
    input.value = val;
}

function addToCart(productId, quantity = 1, size = 'M') {
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity),
            size: size
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to update cart badge in header
            location.reload(); 
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}

// Handle price update on size change
document.querySelectorAll('input[name="product_size"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const price = this.dataset.price;
        if (price) {
            document.getElementById('productPrice').innerText = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
        }
    });
});
</script>

<?php require_once 'includes/customer_footer.php'; ?>
