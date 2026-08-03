<?php
$pageTitle = 'Hỗ trợ khách hàng - TNT Coffee';
require_once __DIR__ . '/includes/customer_header.php';
?>

<div class="support-page-wrapper py-5" style="background-color: var(--color-beige); min-height: 100vh;">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title text-center mb-5 animate-fade-up">
            <h6 class="font-subheading text-muted">TNT Coffee Care</h6>
            <h1 class="display-4 fw-bold font-heading" style="color: var(--color-coffee-dark);">TRUNG TÂM HỖ TRỢ</h1>
            <p class="text-muted">Mọi thông tin bạn cần biết khi trải nghiệm dịch vụ tại TNT Coffee</p>
        </div>

        <div class="row g-5">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="bg-white p-4 rounded-4 shadow-sm position-sticky" style="top: 100px;">
                    <h5 class="fw-bold mb-4 border-bottom pb-3">Danh mục hỗ trợ</h5>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link active text-dark fw-bold mb-2 p-3 rounded-3" id="v-pills-guide-tab" data-bs-toggle="pill" href="#v-pills-guide" role="tab" aria-controls="v-pills-guide" aria-selected="true">
                            <i class="bi bi-bag-check me-2"></i> Hướng dẫn đặt hàng
                        </a>
                        <a class="nav-link text-dark fw-bold mb-2 p-3 rounded-3" id="v-pills-privacy-tab" data-bs-toggle="pill" href="#v-pills-privacy" role="tab" aria-controls="v-pills-privacy" aria-selected="false">
                            <i class="bi bi-shield-lock me-2"></i> Chính sách bảo mật
                        </a>
                        <a class="nav-link text-dark fw-bold mb-2 p-3 rounded-3" id="v-pills-terms-tab" data-bs-toggle="pill" href="#v-pills-terms" role="tab" aria-controls="v-pills-terms" aria-selected="false">
                            <i class="bi bi-file-earmark-text me-2"></i> Điều khoản sử dụng
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9">
                <div class="bg-white p-5 rounded-5 shadow-sm tab-content" id="v-pills-tabContent">
                    
                    <!-- Guide Tab -->
                    <div class="tab-pane fade show active" id="v-pills-guide" role="tabpanel" aria-labelledby="v-pills-guide-tab">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-1">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-bag-check fs-4"></i></div>
                            <h3 class="fw-bold mb-0" style="color: var(--color-coffee-dark);">Hướng dẫn đặt hàng</h3>
                        </div>
                        <div class="content-body" style="line-height: 1.8; color: #555;">
                            <h5 class="fw-bold text-dark mt-4">1. Đặt hàng qua Website</h5>
                            <p>Bạn có thể dễ dàng đặt những món uống yêu thích ngay trên website của TNT Coffee chỉ với vài bước đơn giản:</p>
                            <ul>
                                <li><strong>Bước 1:</strong> Truy cập vào mục <a href="menu.php" class="text-decoration-none fw-bold" style="color: var(--color-gold);">Thực đơn</a>.</li>
                                <li><strong>Bước 2:</strong> Chọn món uống bạn yêu thích và nhấn nút "Thêm vào giỏ".</li>
                                <li><strong>Bước 3:</strong> Vào "Giỏ hàng" (biểu tượng túi xách góc trên bên phải) để kiểm tra lại các món đã chọn.</li>
                                <li><strong>Bước 4:</strong> Điền thông tin giao hàng và chọn phương thức thanh toán. Nhấn "Xác nhận đặt hàng".</li>
                            </ul>
                            
                            <h5 class="fw-bold text-dark mt-4">2. Đặt hàng qua Hotline</h5>
                            <p>Nếu bạn cần hỗ trợ nhanh chóng hoặc muốn đặt số lượng lớn cho công ty/sự kiện, vui lòng gọi trực tiếp đến Hotline: <strong>1900 123 456</strong>. Nhân viên của chúng tôi luôn sẵn sàng hỗ trợ bạn từ 07:00 đến 22:00 mỗi ngày.</p>
                            
                            <div class="alert mt-4 rounded-4" style="background-color: var(--color-beige); border-left: 5px solid var(--color-gold);">
                                <i class="bi bi-info-circle-fill text-warning me-2"></i>
                                <strong>Lưu ý:</strong> Vui lòng kiểm tra kỹ thông tin địa chỉ giao hàng và số điện thoại trước khi xác nhận để shipper có thể giao đến bạn nhanh nhất nhé!
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Tab -->
                    <div class="tab-pane fade" id="v-pills-privacy" role="tabpanel" aria-labelledby="v-pills-privacy-tab">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-1">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-shield-lock fs-4"></i></div>
                            <h3 class="fw-bold mb-0" style="color: var(--color-coffee-dark);">Chính sách bảo mật</h3>
                        </div>
                        <div class="content-body" style="line-height: 1.8; color: #555;">
                            <p>Cám ơn quý khách đã truy cập vào website của TNT Coffee. Chúng tôi cam kết bảo mật tuyệt đối các thông tin cá nhân của khách hàng.</p>
                            
                            <h5 class="fw-bold text-dark mt-4">1. Thu thập thông tin cá nhân</h5>
                            <p>Chúng tôi tiến hành thu thập các thông tin cá nhân (Tên, Số điện thoại, Địa chỉ, Email) chỉ khi khách hàng tự nguyện đăng ký tài khoản hoặc thực hiện giao dịch đặt hàng trên website. Các thông tin này được sử dụng phục vụ cho việc giao hàng và cung cấp dịch vụ CSKH tốt nhất.</p>

                            <h5 class="fw-bold text-dark mt-4">2. Sử dụng thông tin</h5>
                            <p>TNT Coffee sử dụng thông tin thu thập được từ khách hàng vào các mục đích:</p>
                            <ul>
                                <li>Xử lý đơn đặt hàng và cung cấp dịch vụ thông qua website.</li>
                                <li>Gửi thông báo về tiến độ giao hàng hoặc hỗ trợ kỹ thuật/khiếu nại.</li>
                                <li>Gửi các chương trình khuyến mãi, ưu đãi đặc biệt (nếu khách hàng đồng ý nhận bản tin).</li>
                            </ul>

                            <h5 class="fw-bold text-dark mt-4">3. Bảo mật thông tin</h5>
                            <p>Toàn bộ thông tin dữ liệu của người dùng được mã hóa và bảo vệ. Chúng tôi tuyệt đối <strong>KHÔNG</strong> chia sẻ, bán hoặc trao đổi thông tin cá nhân của khách hàng cho bất kỳ bên thứ ba nào vì mục đích thương mại.</p>
                        </div>
                    </div>

                    <!-- Terms Tab -->
                    <div class="tab-pane fade" id="v-pills-terms" role="tabpanel" aria-labelledby="v-pills-terms-tab">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-1">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-file-earmark-text fs-4"></i></div>
                            <h3 class="fw-bold mb-0" style="color: var(--color-coffee-dark);">Điều khoản sử dụng</h3>
                        </div>
                        <div class="content-body" style="line-height: 1.8; color: #555;">
                            <p>Chào mừng bạn đến với hệ thống đặt hàng trực tuyến của TNT Coffee. Khi truy cập và sử dụng dịch vụ trên website, bạn đã đồng ý tuân thủ những điều khoản dưới đây.</p>
                            
                            <h5 class="fw-bold text-dark mt-4">1. Quy định về tài khoản</h5>
                            <p>Trang web cho phép người dùng đăng ký tài khoản để quản lý đơn hàng. Bản thân người dùng phải có trách nhiệm bảo mật mật khẩu và các thông tin đăng nhập của mình. Bất kỳ tổn thất nào phát sinh từ việc để lộ thông tin tài khoản sẽ do người dùng tự chịu trách nhiệm.</p>

                            <h5 class="fw-bold text-dark mt-4">2. Quy định về Nội dung</h5>
                            <p>Toàn bộ thiết kế, văn bản, đồ họa, âm thanh, hình ảnh... mang thương hiệu và thuộc quyền sở hữu trí tuệ của TNT Coffee. Không được sao chép, phân phối hoặc sử dụng cho mục đích thương mại mà không có sự đồng ý bằng văn bản của chúng tôi.</p>

                            <h5 class="fw-bold text-dark mt-4">3. Hủy và Thay đổi Đơn hàng</h5>
                            <ul>
                                <li><strong>Trước khi pha chế:</strong> Bạn có thể hủy hoặc thay đổi món thông qua nút "Hủy đơn" trên website hoặc gọi trực tiếp Hotline.</li>
                                <li><strong>Khi đơn đã vào trạng thái Đang giao:</strong> Bạn không thể hủy đơn hàng trên hệ thống. Mọi vấn đề phát sinh vui lòng liên hệ tư vấn viên để được hỗ trợ giải quyết thỏa đáng.</li>
                            </ul>
                            
                            <h5 class="fw-bold text-dark mt-4">4. Thay đổi điều khoản</h5>
                            <p>TNT Coffee giữ quyền thay đổi, chỉnh sửa, thêm hoặc lược bỏ bất kỳ phần nào trong Quy định và Điều kiện sử dụng vào bất cứ lúc nào. Các thay đổi có hiệu lực ngay khi được đăng trên trang web mà không cần thông báo trước.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root { --color-gold: #e67e22; --color-beige: #F9F7F2; }
    .support-page-wrapper { font-family: 'Be Vietnam Pro', sans-serif; }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .nav-pills .nav-link { transition: all 0.3s ease; border-left: 4px solid transparent; }
    .nav-pills .nav-link:hover { background-color: var(--color-beige); transform: translateX(5px); }
    .nav-pills .nav-link.active { background-color: var(--color-beige); color: var(--color-gold) !important; border-left: 4px solid var(--color-gold); }
    .content-body ul { padding-left: 20px; }
    .content-body li { margin-bottom: 10px; }
</style>

<!-- Add a script to handle URL parameters for auto-opening tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        let tabId = '';
        if (tab === 'guide') tabId = 'v-pills-guide-tab';
        if (tab === 'privacy') tabId = 'v-pills-privacy-tab';
        if (tab === 'terms') tabId = 'v-pills-terms-tab';
        
        if (tabId) {
            let tabElement = document.getElementById(tabId);
            if(tabElement) {
                let bsTab = new bootstrap.Tab(tabElement);
                bsTab.show();
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/customer_footer.php'; ?>
