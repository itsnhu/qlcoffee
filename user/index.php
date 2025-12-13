<?php
$pageTitle = 'Trang chủ';
require_once 'includes/header.php';

// Get featured products (latest medicines)
$featuredProducts = fetchAll($pdo, "
    SELECT m.*, c.name as category_name
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    WHERE m.quantity > 0 AND m.expiry_date > CURDATE()
    ORDER BY m.created_at DESC
    LIMIT 8
");

// Get best sellers (most sold)
$bestSellers = fetchAll($pdo, "
    SELECT m.*, c.name as category_name, COALESCE(SUM(od.quantity), 0) as total_sold
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    LEFT JOIN order_details od ON m.id = od.medicine_id
    WHERE m.quantity > 0 AND m.expiry_date > CURDATE()
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 8
");

// Get categories with product count
$categoriesWithCount = fetchAll($pdo, "
    SELECT c.*, COUNT(m.id) as product_count
    FROM categories c
    LEFT JOIN medicines m ON c.id = m.category_id AND m.quantity > 0
    GROUP BY c.id
    ORDER BY product_count DESC
    LIMIT 8
");

// Category icons mapping
$categoryIcons = [
    'Thuốc kháng sinh' => 'bi-capsule-pill',
    'Thuốc giảm đau' => 'bi-bandaid',
    'Vitamin' => 'bi-sun',
    'Thực phẩm chức năng' => 'bi-heart-pulse',
    'Thuốc tiêu hóa' => 'bi-droplet',
    'Thuốc tim mạch' => 'bi-heart',
    'Thuốc hô hấp' => 'bi-wind',
    'Chăm sóc cá nhân' => 'bi-person-heart',
];
?>

<!-- Hero Banner -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary px-3 py-2 mb-3 rounded-pill">
                    <i class="bi bi-patch-check-fill me-1"></i>Nhà thuốc được cấp phép bởi Bộ Y Tế
                </span>
                <h1 class="display-5 fw-bold mb-3">Chăm sóc sức khỏe <br>từ những điều nhỏ nhất</h1>
                <p class="lead mb-4 opacity-90">Cung cấp đa dạng thuốc, vitamin và thực phẩm chức năng chính hãng. Giao hàng nhanh - Giá tốt nhất thị trường.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-grid me-2"></i>Khám phá ngay
                    </a>
                    <?php if (!isset($_SESSION['customer_id'])): ?>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i>Đăng ký thành viên
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div class="position-relative">
                    <i class="bi bi-capsule-pill" style="font-size: 12rem; opacity: 0.15;"></i>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="bg-white rounded-4 p-4 shadow-lg text-center" style="min-width: 200px;">
                            <i class="bi bi-shield-fill-check text-success fs-1 mb-2 d-block"></i>
                            <h6 class="text-dark mb-1">Thuốc chính hãng</h6>
                            <small class="text-muted">100% nguồn gốc rõ ràng</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-4 bg-white border-bottom">
    <div class="container">
        <div class="row">
            <div class="col-6 col-lg-3">
                <div class="feature-box">
                    <i class="bi bi-truck d-block"></i>
                    <h6>Giao hàng nhanh</h6>
                    <p>Miễn phí đơn từ 500k</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-box">
                    <i class="bi bi-shield-check d-block"></i>
                    <h6>Thuốc chính hãng</h6>
                    <p>100% nguồn gốc rõ ràng</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-box">
                    <i class="bi bi-cash-coin d-block"></i>
                    <h6>Thanh toán COD</h6>
                    <p>Nhận hàng mới thanh toán</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-box">
                    <i class="bi bi-headset d-block"></i>
                    <h6>Hỗ trợ 24/7</h6>
                    <p>Hotline: 1900 1234</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title"><i class="bi bi-grid-3x3-gap-fill"></i>Danh mục sản phẩm</h2>
            <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-outline-primary btn-sm">
                Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3">
            <?php foreach ($categoriesWithCount as $cat): ?>
                <?php $icon = $categoryIcons[$cat['name']] ?? 'bi-capsule'; ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="<?= BASE_URL ?>/user/products.php?category=<?= $cat['id'] ?>" class="category-card">
                        <i class="bi <?= $icon ?> d-block"></i>
                        <h6><?= htmlspecialchars($cat['name']) ?></h6>
                        <small><?= $cat['product_count'] ?> sản phẩm</small>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title"><i class="bi bi-stars"></i>Sản phẩm mới</h2>
            <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-outline-primary btn-sm">
                Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php if (empty($featuredProducts)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3">Chưa có sản phẩm nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>">
                                <div class="card-img-top">
                                    <i class="bi bi-capsule-pill" style="font-size: 4rem; color: var(--primary-400);"></i>
                                </div>
                            </a>
                            <div class="card-body">
                                <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                                <h6 class="card-title mb-2" style="font-family: var(--font-display); font-weight: 600;">
                                    <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark stretched-link-title">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="product-price"><?= formatCurrency($product['price']) ?></span>
                                    <button onclick="event.preventDefault(); addToCart(<?= $product['id'] ?>)" class="btn btn-add-cart">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Best Sellers -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title"><i class="bi bi-fire" style="color: var(--danger);"></i>Bán chạy nhất</h2>
            <a href="<?= BASE_URL ?>/user/products.php?sort=bestseller" class="btn btn-outline-primary btn-sm">
                Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php if (empty($bestSellers)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3">Chưa có sản phẩm nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($bestSellers as $index => $product): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>">
                                <div class="card-img-top position-relative">
                                    <i class="bi bi-capsule-pill" style="font-size: 4rem; color: var(--primary-400);"></i>
                                    <?php if ($index < 3): ?>
                                        <span class="position-absolute top-0 start-0 m-2">
                                            <span class="badge bg-danger rounded-pill">
                                                <i class="bi bi-trophy-fill me-1"></i>Top <?= $index + 1 ?>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($product['total_sold'] > 0): ?>
                                        <span class="position-absolute bottom-0 end-0 m-2">
                                            <span class="badge bg-dark bg-opacity-75">
                                                <i class="bi bi-bag-check me-1"></i><?= $product['total_sold'] ?> đã bán
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <div class="card-body">
                                <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                                <h6 class="card-title mb-2" style="font-family: var(--font-display); font-weight: 600;">
                                    <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="product-price"><?= formatCurrency($product['price']) ?></span>
                                    <button onclick="event.preventDefault(); addToCart(<?= $product['id'] ?>)" class="btn btn-add-cart">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Promo Banner -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="mb-2"><i class="bi bi-gift me-2"></i>Đăng ký thành viên - Nhận ngay ưu đãi!</h3>
                <p class="mb-lg-0 opacity-90">Tích điểm đổi quà, giảm giá độc quyền và nhiều ưu đãi hấp dẫn dành riêng cho thành viên.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (!isset($_SESSION['customer_id'])): ?>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-light btn-lg">
                        <i class="bi bi-person-plus me-2"></i>Đăng ký ngay
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-light btn-lg">
                        <i class="bi bi-bag me-2"></i>Mua sắm ngay
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
