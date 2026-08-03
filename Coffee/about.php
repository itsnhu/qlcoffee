<?php
$pageTitle = 'Về chúng tôi';
require_once __DIR__ . '/includes/customer_header.php';
?>

<div class="about-page-new">
    <!-- Hero Section -->
    <section class="about-hero py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Image Side with Coffee Bean Mask -->
                <div class="col-lg-6 text-center">
                    <div class="bean-image-wrapper">
                        <div class="bean-mask">
                            <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="TNT Coffee Interior">
                        </div>
                    </div>
                </div>
                
                <!-- Text Side -->
                <div class="col-lg-6">
                    <div class="about-text-content">
                        <h1 class="display-3 fw-bold mb-4 about-main-title">VỀ CHÚNG TÔI</h1>
                        <p class="about-description">
                            Chào mừng bạn đến với <strong>TNT Coffee</strong>, nơi chúng tôi nỗ lực mang đến trải nghiệm cà phê đáng nhớ trong không gian ấm cúng và thân thiện. Tại TNT Coffee, mỗi tách cà phê không chỉ là thức uống, mà là một trải nghiệm nghệ thuật. Từ những hạt cà phê được lựa chọn kỹ càng đến từng công đoạn chuẩn bị tỉ mỉ, tất cả đều vì một mục tiêu duy nhất: mang đến cho bạn hương vị chân thực và tinh tế nhất mỗi ngày.
                        </p>
                        <a href="menu.php" class="btn btn-premium mt-4">Xem Thêm <i class="bi bi-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-grid py-5 pb-5">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="feature-item-premium">
                        <div class="feature-icon-box">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h4 class="fw-bold mt-3 mb-2">Không Gian</h4>
                        <p class="text-muted mb-0">Không gian ấm cúng, yên tĩnh, nơi bạn chậm lại và tận hưởng trọn vẹn hương vị cà phê</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item-premium">
                        <div class="feature-icon-box">
                            <i class="bi bi-cup-hot"></i>
                        </div>
                        <h4 class="fw-bold mt-3 mb-2">Hương Vị</h4>
                        <p class="text-muted mb-0">Mỗi ngụm là một trải nghiệm mới mẻ, đánh thức mọi giác quan trong bạn</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item-premium">
                        <div class="feature-icon-box">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4 class="fw-bold mt-3 mb-2">Dịch Vụ</h4>
                        <p class="text-muted mb-0">Phục vụ thân thiện, nụ cười luôn sẵn sàng tại TNT! Ghé ngay để cảm nhận sự khác biệt</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    :root {
        --coffee-brown: #2C1810;
        --coffee-accent: #8D6E63;
        --coffee-light: #F9F7F2;
    }

    .about-page-new {
        background-color: var(--color-beige);
        font-family: 'Be Vietnam Pro', sans-serif;
        color: var(--coffee-brown);
    }

    .about-main-title {
        font-family: 'Playfair Display', serif;
        letter-spacing: -1px;
        color: var(--coffee-brown);
    }

    .about-description {
        font-size: 1.15rem;
        line-height: 1.8;
        color: #555;
        text-align: justify;
    }

    /* Coffee Bean Mask Effect */
    .bean-image-wrapper {
        position: relative;
        display: inline-block;
        padding: 10px;
    }

    .bean-mask {
        width: 450px;
        height: 550px;
        background: #eee;
        overflow: hidden;
        /* Coffee bean shape using clip-path */
        clip-path: path('M225,10 C350,10 440,150 440,275 C440,400 350,540 225,540 C100,540 10,400 10,275 C10,150 100,10 225,10 Z M225,50 Q240,275 225,500');
        /* Note: Path simplified for general bean shape + curve in middle */
        clip-path: ellipse(45% 48% at 50% 50%); /* Start with simple ellipse */
        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%; /* More organic shape */
        position: relative;
    }

    /* Better bean shape using svg mask or advanced clip path */
    .bean-mask {
        width: 100%;
        max-width: 450px;
        aspect-ratio: 0.85 / 1;
        background: #eee;
        border-radius: 180px 180px 180px 180px / 280px 280px 320px 320px;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 0 50px rgba(0,0,0,0.1);
    }

    /* The coffee bean split effect */
    .bean-mask::after {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        width: 20px;
        height: 100%;
        background: var(--color-beige);
        transform: translateX(-50%) rotate(5deg);
        filter: blur(5px);
        opacity: 0.8;
    }

    .bean-mask img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Feature Items */
    .feature-item-premium {
        padding: 2rem;
        transition: all 0.3s ease;
    }

    .feature-icon-box {
        font-size: 2.5rem;
        color: #666;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .feature-item-premium:hover .feature-icon-box {
        color: var(--coffee-accent);
        transform: scale(1.1);
    }

    .btn-premium {
        background: #e67e22;
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        text-transform: uppercase;
        font-weight: 700;
        border: none;
        box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
        transition: all 0.3s ease;
    }

    .btn-premium:hover {
        background: #d35400;
        transform: translateY(-2px);
        color: white;
    }

    @media (max-width: 991px) {
        .about-main-title { font-size: 2.5rem; }
        .bean-mask { max-width: 350px; margin: 0 auto; }
    }
</style>

<?php require_once __DIR__ . '/includes/customer_footer.php'; ?>
