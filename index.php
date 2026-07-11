<?php
$pageTitle = 'Trang chủ';
require_once 'includes/customer_header.php';

// Fetch Featured Products (Random 4 for now)
try {
    $featuredProducts = fetchAll($pdo, "SELECT * FROM products WHERE quantity > 0 ORDER BY RAND() LIMIT 4");
    $categories = fetchAll($pdo, "SELECT * FROM categories WHERE name LIKE '%Cà phê%' OR name LIKE '%Coffee%' ORDER BY name ASC");
} catch (PDOException $e) {
    $featuredProducts = [];
    $categories = [];
    error_log("Home Page Error: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero-premium" style="background-image: url('https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');">
    <div class="hero-content">
        <h1 class="hero-title" style="color: white !important; font-weight: 800; text-shadow: 0 4px 15px rgba(0,0,0,0.5);">Trải Nghiệm Nghệ Thuật Cà Phê</h1>
        <p class="hero-subtitle" style="color: white !important; font-weight: 500; opacity: 1;">Mỗi tách cà phê là một tác phẩm nghệ thuật, được pha chế từ những hạt cà phê thượng hạng nhất.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="menu.php" class="btn btn-premium">
                <i class="bi bi-cup-hot"></i> Đặt Món Ngay
            </a>
            <a href="#featured" class="btn btn-premium-outline text-white border-white">
                Khám Phá
            </a>
        </div>
    </div>
</section>

<!-- About Preview Section -->
<section class="about-preview py-4" style="background-color: var(--color-beige);">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 text-center">
                <div class="bean-image-wrapper-mini">
                    <div class="bean-mask-mini">
                        <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="About TNT">
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <h6 class="font-subheading text-muted mb-2">Hành trình của chúng tôi</h6>
                <h2 class="font-heading fw-bold mb-4" style="font-size: 2.8rem; color: var(--color-coffee-dark);">Về chúng tôi</h2>
                <p class="text-muted lead mb-4" style="line-height: 1.8;">Chào mừng bạn đến với <strong>TNT Coffee</strong>, nơi mỗi tách cà phê là một tác phẩm nghệ thuật. Chúng tôi nỗ lực mang đến trải nghiệm chân thực nhất từ những hạt cà phê thượng hạng...</p>
                <a href="about.php" class="btn btn-premium">Xem Chi Tiết <i class="bi bi-arrow-right ms-2"></i></a>
            </div>
        </div>
    </div>
</section>



<!-- Featured Products Section -->
<section id="featured" class="py-4" style="background-color: #FAFAFA;">
    <div class="container">
        <div class="section-title">
            <h2>Hương Vị Được Yêu Thích</h2>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-md-6 col-lg-3">
                <div class="product-card-premium">
                    <div class="card-img-wrapper">
                        <?php
                        $featImgSrc = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=600&auto=format&fit=crop';
                        if (!empty($product['image'])) {
                            if (filter_var($product['image'], FILTER_VALIDATE_URL)) {
                                $featImgSrc = $product['image'];
                            } else {
                                $featImgSrc = BASE_URL . '/assets/img/products/' . $product['image'];
                            }
                        }
                        ?>
                        <img src="<?= $featImgSrc ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if ($product['quantity'] < 10): ?>
                            <span class="position-absolute top-0 start-0 badge bg-danger m-3 rounded-pill">Sắp hết hàng</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h5 class="card-title-premium">
                            <a href="javascript:void(0)" onclick="showProductModal(<?php echo $product['id']; ?>)" class="text-decoration-none text-dark stretched-link">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h5>
                        <p class="text-muted small mb-3 text-truncate">
                            <?php echo htmlspecialchars($product['description'] ?? 'Hương vị tuyệt hảo...'); ?>
                        </p>
                        <hr style="opacity: 0.1">
                        <span class="price-tag"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-add-cart shadow">
                            <i class="bi bi-plus-lg fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="menu.php" class="btn btn-premium-outline">Xem Toàn Bộ Menu <i class="bi bi-arrow-right ml-2"></i></a>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<section class="py-4 bg-white overflow-hidden position-relative">
    <div class="bean-bg-decoration"></div>
    <div class="container position-relative">
        <div class="section-title">
            <h6 class="font-subheading text-muted">Phản hồi thực tế</h6>
            <h2>Đánh Giá Từ Khách Hàng</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="review-card-premium shadow-sm">
                    <div class="stars mb-2 text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <p class="review-text italic">"Không gian ở đây cực chill, cà phê muối rất đậm đà, đúng gu mình."</p>
                    <div class="reviewer d-flex align-items-center gap-3 mt-4">
                        <div class="avatar">MA</div>
                        <div class="info"><h6 class="mb-0 fw-bold">Minh Anh</h6><small class="text-muted">Khách hàng quen</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="review-card-premium shadow-sm">
                    <div class="stars mb-2 text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i></div>
                    <p class="review-text italic">"Nhân viên nhiệt tình, phục vụ nhanh. Thích nhất góc ngồi cạnh cửa sổ."</p>
                    <div class="reviewer d-flex align-items-center gap-3 mt-4">
                        <div class="avatar">TH</div>
                        <div class="info"><h6 class="mb-0 fw-bold">Tuấn Hùng</h6><small class="text-muted">Nhân viên văn phòng</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="review-card-premium shadow-sm">
                    <div class="stars mb-2 text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    <p class="review-text italic">"Chất lượng hạt cà phê tuyệt vời, thơm lừng ngay khi bước chân vào quán."</p>
                    <div class="reviewer d-flex align-items-center gap-3 mt-4">
                        <div class="avatar">LN</div>
                        <div class="info"><h6 class="mb-0 fw-bold">Linh Nga</h6><small class="text-muted">Designer</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section class="py-4" style="background-color: #F9F7F2;">
    <div class="container">
        <div class="section-title">
            <h6 class="font-subheading text-muted">Kiến thức cà phê</h6>
            <h2>Góc Chia Sẻ - TNT Blog</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="blog-card-premium shadow-sm">
                    <div class="blog-img"><img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog"></div>
                    <div class="p-4 bg-white rounded-bottom-4">
                        <small class="text-accent fw-bold text-uppercase">Mẹo pha chế</small>
                        <h5 class="fw-bold mt-2 mb-3">Bật mí cách pha Cà phê muối tại nhà chuẩn vị</h5>
                        <a href="blog_detail.php?id=1" class="text-decoration-none fw-bold text-dark small">Đọc tiếp <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="blog-card-premium shadow-sm">
                    <div class="blog-img"><img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog"></div>
                    <div class="p-4 bg-white rounded-bottom-4">
                        <small class="text-accent fw-bold text-uppercase">Check-in</small>
                        <h5 class="fw-bold mt-2 mb-3">Top 3 góc "triệu view" tại quán bạn không nên bỏ lỡ</h5>
                        <a href="blog_detail.php?id=2" class="text-decoration-none fw-bold text-dark small">Đọc tiếp <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="blog-card-premium shadow-sm">
                    <div class="blog-img"><img src="https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog"></div>
                    <div class="p-4 bg-white rounded-bottom-4">
                        <small class="text-accent fw-bold text-uppercase">Kiến thức</small>
                        <h5 class="fw-bold mt-2 mb-3">Hành trình từ hạt cà phê thô đến tách Espresso đậm đà</h5>
                        <a href="blog_detail.php?id=3" class="text-decoration-none fw-bold text-dark small">Đọc tiếp <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .bean-image-wrapper-mini { position: relative; padding: 10px; }
    .bean-mask-mini { width: 100%; max-width: 380px; aspect-ratio: 0.8 / 1; border-radius: 150px 150px 150px 150px / 220px 220px 260px 260px; overflow: hidden; position: relative; box-shadow: var(--shadow-lg); margin: 0 auto; }
    .bean-mask-mini img { width: 100%; height: 100%; object-fit: cover; }
    .bean-mask-mini::after { content: ''; position: absolute; top: 0; left: 50%; width: 15px; height: 100%; background: var(--color-beige); transform: translateX(-50%) rotate(5deg); filter: blur(4px); opacity: 0.8; }
    
    .review-card-premium { background: white; padding: 2.5rem; border-radius: 24px; border: 1px solid rgba(0,0,0,0.03); }
    .avatar { width: 50px; height: 50px; border-radius: 50%; background: var(--color-coffee-light); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    
    .blog-card-premium { border-radius: 24px; overflow: hidden; transition: 0.3s; }
    .blog-img { height: 200px; overflow: hidden; }
    .blog-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .blog-card-premium:hover .blog-img img { transform: scale(1.1); }
    .text-accent { color: var(--color-gold); }
    
    .bean-bg-decoration { position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M50 10 C70 10 90 30 90 55 C90 80 70 95 50 95 C30 95 10 80 10 55 C10 30 30 10 50 10" fill="%238D6E63" opacity="0.05"/></svg>'); background-size: contain; background-repeat: no-repeat; z-index: 0; }
</style>

<script>
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
            // Update badge (implementation needed)
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.textContent = data.cartCount;
                badge.classList.remove('d-none');
            } else {
                 location.reload(); 
            }
            alert('Đã thêm vào giỏ hàng!');
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi thêm vào giỏ hàng');
    });
}
</script>

<?php require_once 'includes/customer_footer.php'; ?>
