<?php
require_once 'includes/header.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: ' . BASE_URL . '/user/products.php');
    exit;
}

// Get product details
$product = fetchOne($pdo, "
    SELECT m.*, c.name as category_name, s.name as supplier_name
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    JOIN suppliers s ON m.supplier_id = s.id
    WHERE m.id = ?
", [$productId]);

if (!$product) {
    setMessage('danger', 'Sản phẩm không tồn tại');
    header('Location: ' . BASE_URL . '/user/products.php');
    exit;
}

$pageTitle = $product['name'];

// Get related products
$relatedProducts = fetchAll($pdo, "
    SELECT m.*, c.name as category_name
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    WHERE m.category_id = ? AND m.id != ? AND m.quantity > 0 AND m.expiry_date > CURDATE()
    ORDER BY RAND()
    LIMIT 4
", [$product['category_id'], $productId]);

// Check if product is available
$isAvailable = $product['quantity'] > 0 && strtotime($product['expiry_date']) > time();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb" style="font-size: 0.9rem;">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/" class="text-decoration-none"><i class="bi bi-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/products.php" class="text-decoration-none">Sản phẩm</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/products.php?category=<?= $product['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Image -->
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: var(--radius-lg); overflow: hidden;">
                <div class="card-body text-center p-5" style="background: linear-gradient(135deg, var(--gray-50), var(--primary-50));">
                    <i class="bi bi-capsule-pill" style="font-size: 10rem; color: var(--primary-400);"></i>
                </div>
                <?php if (!$isAvailable): ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5);">
                        <span class="badge bg-danger fs-5 p-3">
                            <?= $product['quantity'] <= 0 ? 'Hết hàng' : 'Hết hạn' ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-7">
            <span class="badge bg-primary-subtle text-primary mb-2 px-3 py-2"><?= htmlspecialchars($product['category_name']) ?></span>
            <h1 class="mb-3" style="font-family: var(--font-display); font-weight: 800; font-size: 1.75rem; color: var(--gray-800);"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="mb-3 d-flex align-items-center gap-3">
                <span class="text-muted">Mã sản phẩm:</span>
                <span class="badge bg-light text-dark"><?= htmlspecialchars($product['code']) ?></span>
            </div>

            <div class="mb-4 p-3 rounded-3" style="background: var(--primary-50);">
                <span class="h2 text-danger" style="font-family: var(--font-display); font-weight: 800;"><?= formatCurrency($product['price']) ?></span>
                <small class="text-muted">/ <?= htmlspecialchars($product['unit']) ?></small>
            </div>

            <!-- Stock Status -->
            <?php if ($isAvailable): ?>
                <div class="alert border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: var(--radius-lg);">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong>Còn hàng</strong> - <?= $product['quantity'] ?> <?= htmlspecialchars($product['unit']) ?> trong kho
                </div>
            <?php else: ?>
                <div class="alert alert-danger border-0 mb-4" style="border-radius: var(--radius-lg);">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong><?= $product['quantity'] <= 0 ? 'Hết hàng' : 'Sản phẩm đã hết hạn' ?></strong>
                </div>
            <?php endif; ?>

            <!-- Add to Cart -->
            <?php if ($isAvailable): ?>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-primary" type="button" onclick="changeQty(-1)">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control text-center border-primary" id="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" style="font-weight: 600;">
                        <button class="btn btn-outline-primary" type="button" onclick="changeQty(1)">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('quantity').value)" class="btn btn-primary btn-lg flex-grow-1" style="border-radius: var(--radius-full); font-family: var(--font-display); font-weight: 600;">
                        <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                </div>
            <?php endif; ?>

            <!-- Quick Info -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="text-center p-3 rounded-3 bg-light">
                        <i class="bi bi-truck text-primary d-block mb-1" style="font-size: 1.5rem;"></i>
                        <small class="text-muted">Giao hàng nhanh</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center p-3 rounded-3 bg-light">
                        <i class="bi bi-shield-check text-primary d-block mb-1" style="font-size: 1.5rem;"></i>
                        <small class="text-muted">Chính hãng</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center p-3 rounded-3 bg-light">
                        <i class="bi bi-arrow-repeat text-primary d-block mb-1" style="font-size: 1.5rem;"></i>
                        <small class="text-muted">Đổi trả 7 ngày</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center p-3 rounded-3 bg-light">
                        <i class="bi bi-headset text-primary d-block mb-1" style="font-size: 1.5rem;"></i>
                        <small class="text-muted">Hỗ trợ 24/7</small>
                    </div>
                </div>
            </div>

            <!-- Product Details -->
            <div class="card border-0 shadow-sm" style="border-radius: var(--radius-lg);">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-0" style="font-family: var(--font-display); font-weight: 700;">
                        <i class="bi bi-info-circle text-primary me-2"></i>Thông tin sản phẩm
                    </h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted ps-0" width="150"><i class="bi bi-tag me-2"></i>Danh mục:</td>
                            <td class="fw-medium"><?= htmlspecialchars($product['category_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0"><i class="bi bi-building me-2"></i>Nhà cung cấp:</td>
                            <td class="fw-medium"><?= htmlspecialchars($product['supplier_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0"><i class="bi bi-box me-2"></i>Đơn vị:</td>
                            <td class="fw-medium"><?= htmlspecialchars($product['unit']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0"><i class="bi bi-calendar-event me-2"></i>Hạn sử dụng:</td>
                            <td class="fw-medium"><?= formatDate($product['expiry_date']) ?></td>
                        </tr>
                    </table>

                    <?php if ($product['description']): ?>
                        <hr class="my-4">
                        <h6 class="mb-3" style="font-family: var(--font-display); font-weight: 600;">
                            <i class="bi bi-file-text text-primary me-2"></i>Mô tả sản phẩm
                        </h6>
                        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="mt-5 pt-4">
            <h4 class="section-title mb-4"><i class="bi bi-grid-3x3-gap-fill"></i>Sản phẩm liên quan</h4>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="col-6 col-md-3">
                        <div class="product-card">
                            <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $related['id'] ?>">
                                <div class="card-img-top">
                                    <i class="bi bi-capsule-pill" style="font-size: 4rem; color: var(--primary-400);"></i>
                                </div>
                            </a>
                            <div class="card-body">
                                <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($related['category_name']) ?></span>
                                <h6 class="card-title mb-2" style="font-family: var(--font-display); font-weight: 600;">
                                    <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $related['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($related['name']) ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="product-price"><?= formatCurrency($related['price']) ?></span>
                                    <button onclick="addToCart(<?= $related['id'] ?>)" class="btn btn-add-cart">
                                        <i class="bi bi-cart-plus"></i>
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

<script>
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > <?= $product['quantity'] ?>) val = <?= $product['quantity'] ?>;
    input.value = val;
}
</script>

<?php require_once 'includes/footer.php'; ?>
