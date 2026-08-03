<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Fetch main post
$post = fetchOne($pdo, "SELECT * FROM blogs WHERE id = ?", [$id]);

if (!$post) {
    header('Location: blog.php');
    exit;
}

// Fetch related posts (same category or others)
$relatedPosts = fetchAll($pdo, "SELECT * FROM blogs WHERE id != ? AND category = ? ORDER BY created_at DESC LIMIT 3", [$id, $post['category']]);
if (empty($relatedPosts)) {
    $relatedPosts = fetchAll($pdo, "SELECT * FROM blogs WHERE id != ? ORDER BY created_at DESC LIMIT 3", [$id]);
}

$pageTitle = htmlspecialchars($post['title'] ?? 'Bài viết') . ' - TNT Coffee';
require_once __DIR__ . '/includes/customer_header.php';
?>

<!-- Reading Progress Bar -->
<div class="reading-progress-container">
    <div class="reading-progress-bar" id="readingProgress"></div>
</div>

<div class="blog-detail-premium-wrapper">
    <!-- Hero Section with Parallax Effect -->
    <header class="post-hero">
        <div class="hero-bg" style="background-image: url('<?php echo $post['image'] ?? 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=1200&auto=format&fit=crop'; ?>');"></div>
        <div class="hero-overlay"></div>
        <div class="container container-narrow">
            <div class="hero-content text-center text-white" data-aos="fade-up">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['category'] ?? 'Khám phá'); ?></li>
                    </ol>
                </nav>
                <h1 class="display-3 fw-bold mb-4 text-white"><?php echo htmlspecialchars($post['title'] ?? 'Tiêu đề bài viết'); ?></h1>
                <div class="post-meta-v2">
                    <div class="author-info">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['author'] ?? 'Admin'); ?>&background=e67e22&color=fff" class="rounded-circle me-2" width="40">
                        <span>Đăng bởi <strong><?php echo htmlspecialchars($post['author'] ?? 'Admin TNT'); ?></strong></span>
                    </div>
                    <span class="meta-divider"></span>
                    <span class="post-date"><i class="bi bi-calendar3 me-2"></i> <?php echo date('d/m/Y', strtotime($post['created_at'] ?? 'now')); ?></span>
                    <span class="meta-divider"></span>
                    <span class="reading-time"><i class="bi bi-clock me-2"></i> 5 phút đọc</span>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <!-- Article Content Card -->
                <article class="post-main-card shadow-sm rounded-5 bg-white p-4 p-md-5">
                    <!-- Excerpt / Summary -->
                    <div class="post-excerpt-v2 mb-5">
                        <p><?php echo htmlspecialchars((string)($post['excerpt'] ?? $post['summary'] ?? $post['description'] ?? '')); ?></p>
                    </div>

                    <!-- Main Dynamic Content -->
                    <div class="post-rich-content">
                        <?php echo $post['content'] ?? '<div class="alert alert-info">Nội dung đang được cập nhật. Cảm ơn bạn đã quan tâm!</div>'; ?>
                    </div>

                    <!-- Footnote / Tagline -->
                    <div class="post-footer mt-5 pt-5 border-top">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                            <div class="tags">
                                <span class="text-muted me-2 fw-bold">Tags:</span>
                                <a href="#" class="tag-pill">#TNT_Coffee</a>
                                <a href="#" class="tag-pill">#CapheSach</a>
                                <a href="#" class="tag-pill">#DaLat</a>
                            </div>
                            <div class="share-actions">
                                <span class="text-muted me-3 fw-bold">Chia sẻ bài viết:</span>
                                <div class="share-buttons-v2">
                                    <button class="share-btn fb" title="Share on Facebook"><i class="bi bi-facebook"></i></button>
                                    <button class="share-btn tw" title="Share on Twitter"><i class="bi bi-twitter-x"></i></button>
                                    <button class="share-btn copy" title="Copy Link"><i class="bi bi-link-45deg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Related Content -->
                <section class="related-posts-v2 mt-5 pt-5">
                    <h3 class="fw-bold mb-4 font-heading text-center">Có thể bạn thích</h3>
                    <div class="row g-4">
                        <?php foreach ($relatedPosts as $rp): ?>
                        <div class="col-md-4">
                            <a href="blog_detail.php?id=<?php echo $rp['id']; ?>" class="text-decoration-none">
                                <div class="mini-post-card h-100 shadow-sm rounded-4 overflow-hidden bg-white transition-all">
                                    <div class="mini-card-img" style="background-image: url('<?php echo $rp['image'] ?? 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400'; ?>');"></div>
                                    <div class="p-3">
                                        <span class="badge bg-light text-dark mb-2"><?php echo htmlspecialchars($rp['category'] ?? 'Blog'); ?></span>
                                        <h6 class="fw-bold text-dark mb-0 line-clamp-2"><?php echo htmlspecialchars($rp['title']); ?></h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Be+Vietnam+Pro:wght@300;400;600;700&display=swap');

    :root {
        --color-coffee-deep: #2c1a0f;
        --color-coffee-medium: #4a3427;
        --color-coffee-light: #8b5e3c;
        --color-gold: #e67e22;
        --color-cream: #FFF9E1;
        --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
    }

    /* Reading Progress */
    .reading-progress-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: transparent;
        z-index: 9999;
    }
    .reading-progress-bar {
        height: 100%;
        background: var(--color-gold);
        width: 0%;
        box-shadow: 0 0 10px rgba(230, 126, 34, 0.5);
    }

    .blog-detail-premium-wrapper {
        background-color: var(--color-cream);
        font-family: 'Be Vietnam Pro', sans-serif;
        color: var(--color-coffee-deep);
    }

    /* Post Hero */
    .post-hero {
        position: relative;
        height: 70vh;
        min-height: 500px;
        display: flex;
        align-items: center;
        overflow: hidden;
    }
    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        transform: scale(1.1);
        z-index: 1;
    }
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
        z-index: 2;
    }
    .hero-content {
        position: relative;
        z-index: 3;
    }
    .container-narrow {
        max-width: 900px;
    }
    .post-hero .breadcrumb-item, .post-hero .breadcrumb-item a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .post-hero .breadcrumb-item.active {
        color: var(--color-gold);
        font-weight: bold;
    }

    .post-meta-v2 {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        color: rgba(255,255,255,0.9);
        font-size: 0.95rem;
    }
    .meta-divider {
        width: 1px;
        height: 15px;
        background: rgba(255,255,255,0.3);
    }

    /* Article Card */
    .post-main-card {
        margin-top: -100px;
        position: relative;
        z-index: 10;
        border: none;
    }
    .post-excerpt-v2 {
        font-size: 1.4rem;
        font-weight: 600;
        line-height: 1.6;
        color: var(--color-coffee-medium);
        font-family: 'Playfair Display', serif;
        border-left: 6px solid var(--color-gold);
        padding-left: 30px;
        font-style: italic;
    }

    .post-rich-content {
        font-size: 1.15rem;
        line-height: 1.9;
        color: #444;
    }
    .post-rich-content h2, .post-rich-content h3, .post-rich-content h4 {
        margin-top: 2.5rem;
        margin-bottom: 1.2rem;
        font-family: 'Playfair Display', serif;
        font-weight: bold;
        color: var(--color-coffee-deep);
    }
    .post-rich-content p {
        margin-bottom: 1.8rem;
    }
    .post-rich-content img {
        max-width: 100%;
        height: auto;
        border-radius: 20px;
        margin: 2.5rem 0;
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    .post-rich-content blockquote {
        margin: 3rem 0;
        padding: 2rem;
        background: #fdfaf3;
        border-radius: 15px;
        text-align: center;
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-style: italic;
        color: var(--color-coffee-light);
    }

    /* Tags & Share */
    .tag-pill {
        display: inline-block;
        padding: 5px 15px;
        background: #eee;
        color: #666;
        text-decoration: none;
        border-radius: 50px;
        font-size: 0.85rem;
        margin-right: 5px;
        transition: 0.3s;
    }
    .tag-pill:hover {
        background: var(--color-coffee-light);
        color: white;
    }

    .share-buttons-v2 {
        display: flex;
        gap: 10px;
    }
    .share-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid #ddd;
        background: white;
        color: #555;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .share-btn:hover {
        background: var(--color-coffee-deep);
        color: white;
        transform: translateY(-3px);
    }

    /* Related Posts */
    .mini-post-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }
    .mini-card-img {
        height: 160px;
        background-size: cover;
        background-position: center;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Animation on scroll hint */
    [data-aos] {
        transition: 1s ease-out;
    }
</style>

<script>
    // Reading Progress Bar logic
    window.onscroll = function() {
        let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        let scrolled = (winScroll / height) * 100;
        document.getElementById("readingProgress").style.width = scrolled + "%";
    };

    // Simple AOS like effect
    document.addEventListener("DOMContentLoaded", function() {
        const hero = document.querySelector('.hero-bg');
        window.addEventListener('scroll', function() {
            let offset = window.pageYOffset;
            hero.style.transform = 'translateY(' + (offset * 0.4) + 'px) scale(1.1)';
        });
    });
</script>

<?php require_once __DIR__ . '/includes/customer_footer.php'; ?>
