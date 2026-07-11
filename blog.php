<?php
$pageTitle = 'Góc Cà Phê - Blog';
require_once __DIR__ . '/includes/customer_header.php';
require_once __DIR__ . '/config/database.php';

// Category & Search filter - Initialize early to use in search logic
$catFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchFilter = isset($_GET['search']) ? $_GET['search'] : '';
?>

<div class="blog-page-wrapper py-5" style="background-color: #FFF9E1; min-height: 80vh;">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h6 class="font-subheading text-muted">Kiến thức & Chia sẻ</h6>
            <h1 class="display-4 fw-bold font-heading" style="color: var(--color-coffee-dark);">Góc Cà Phê TNT</h1>
            <p class="text-muted">Cùng khám phá những câu chuyện thú vị và mẹo pha chế hữu ích</p>
        </div>

        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Danh mục</h5>
                    <ul class="list-unstyled blog-categories">
                        <li class="mb-2"><a href="blog.php" class="text-decoration-none text-dark <?php echo $catFilter == '' ? 'active' : ''; ?>">✨ Tất cả bài viết</a></li>
                        <li class="mb-2"><a href="blog.php?category=Mẹo pha chế" class="text-decoration-none text-dark <?php echo $catFilter == 'Mẹo pha chế' ? 'active' : ''; ?>">☕ Mẹo pha chế</a></li>
                        <li class="mb-2"><a href="blog.php?category=Check-in" class="text-decoration-none text-dark <?php echo $catFilter == 'Check-in' ? 'active' : ''; ?>">📸 Check-in quán</a></li>
                        <li class="mb-2"><a href="blog.php?category=Kiến thức" class="text-decoration-none text-dark <?php echo $catFilter == 'Kiến thức' ? 'active' : ''; ?>">🌿 Kiến thức hạt</a></li>
                    </ul>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Tìm kiếm</h5>
                    <form action="blog.php" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-light" placeholder="Tìm bài viết..." value="<?php echo htmlspecialchars($searchFilter); ?>">
                            <button class="btn btn-premium p-2 px-3" type="submit" style="background-color: var(--color-coffee-dark); color: white; border: none;"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Blog List -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php
                    // Building query
                    $query = "SELECT * FROM blogs WHERE 1=1";
                    $params = [];
                    
                    if ($catFilter && $catFilter != 'Tất cả') {
                        $query .= " AND category LIKE ?";
                        $params[] = "%$catFilter%";
                    }
                    
                    if ($searchFilter) {
                        $query .= " AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
                        $params[] = "%$searchFilter%";
                        $params[] = "%$searchFilter%";
                        $params[] = "%$searchFilter%";
                    }
                    
                    $query .= " ORDER BY created_at DESC";
                    $blogs = fetchAll($pdo, $query, $params);

                    if (empty($blogs)) {
                        echo '<div class="col-12 text-center py-5"><h3>Chưa có bài viết nào phù hợp.</h3></div>';
                    }

                    foreach ($blogs as $blog):
                        $displaySummary = (string)($blog['excerpt'] ?? $blog['summary'] ?? $blog['description'] ?? '');
                        $displayImage = !empty($blog['image']) ? $blog['image'] : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=600&auto=format&fit=crop';
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="blog-card-v2 bg-white rounded-5 shadow-sm overflow-hidden h-100 transition-all border-0">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo $displayImage; ?>" class="img-fluid w-100 card-img-top" style="height: 240px; object-fit: cover; transition: 0.5s;">
                                <div class="card-date-badge position-absolute top-0 start-0 m-3 px-3 py-1 bg-white rounded-pill shadow-sm">
                                    <small class="fw-bold text-dark"><i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y', strtotime($blog['created_at'] ?? 'now')); ?></small>
                                </div>
                                <span class="position-absolute bottom-0 end-0 m-3 badge bg-gold text-white px-3 py-2 fw-bold shadow-sm rounded-pill" style="z-index: 10;"><?php echo htmlspecialchars($blog['category'] ?? 'Khám phá'); ?></span>
                                <div class="overlay-gradient"></div>
                            </div>
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <h4 class="fw-bold mb-3 font-heading line-clamp-2"><?php echo htmlspecialchars($blog['title'] ?? 'Tiêu đề bài viết'); ?></h4>
                                <p class="text-muted small mb-4 flex-grow-1 line-clamp-3"><?php echo htmlspecialchars($displaySummary); ?></p>
                                <a href="blog_detail.php?id=<?php echo $blog['id']; ?>" class="btn btn-premium-v2 w-100 rounded-pill py-2 shadow-sm fw-bold mt-auto">
                                    ĐỌC TIẾP BÀI VIẾT <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination (Placeholder) -->
                <?php if (!empty($blogs)): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled"><a class="page-link border-0 rounded-circle mx-1" href="#"><i class="bi bi-chevron-left"></i></a></li>
                        <li class="page-item active"><a class="page-link border-0 rounded-circle mx-1" href="#">1</a></li>
                        <li class="page-item"><a class="page-link border-0 rounded-circle mx-1" href="#"><i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .blog-page-wrapper { font-family: 'Be Vietnam Pro', sans-serif; background-color: #f8f6f0; }
    .font-heading { font-family: 'Playfair Display', serif; }
    .blog-categories li a { color: #555 !important; transition: 0.3s; display: block; padding: 5px 0; font-size: 0.95rem; }
    .blog-categories li a:hover, .blog-categories li a.active { color: var(--color-gold) !important; padding-left: 10px; font-weight: bold; }
    
    .blog-card-v2 { transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); background: white; }
    .blog-card-v2:hover { transform: translateY(-12px); box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important; }
    .blog-card-v2:hover .card-img-top { transform: scale(1.1); }
    
    .overlay-gradient {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
        background: linear-gradient(0deg, rgba(0,0,0,0.4) 0%, transparent 100%);
        z-index: 5;
    }
    
    .btn-premium-v2 {
        background-color: #2c1a0f;
        color: white;
        border: none;
        letter-spacing: 1px;
        font-size: 0.85rem;
        transition: 0.3s;
    }
    .btn-premium-v2:hover {
        background-color: var(--color-gold);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(230, 0, 0, 0.2);
    }
    
    .bg-gold { background-color: var(--color-gold); }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    
    .page-link { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--color-coffee-dark); border: 1px solid #ddd; }
    .page-item.active .page-link { background-color: var(--color-coffee-dark); color: white; border-color: var(--color-coffee-dark); }
</style>

<?php require_once __DIR__ . '/includes/customer_footer.php'; ?>
