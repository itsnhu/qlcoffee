<?php
$pageTitle = 'Liên hệ - TNT Coffee';
require_once __DIR__ . '/includes/customer_header.php';
?>

<div class="contact-page-wrapper py-5" style="background-color: var(--color-beige); min-height: 100vh;">
    <div class="container">
        <!-- Section Header -->
        <div class="section-title text-center mb-5 animate-fade-up">
            <h6 class="font-subheading text-muted">Kết nối với chúng tôi</h6>
            <h1 class="display-4 fw-bold font-heading" style="color: var(--color-coffee-dark);">LIÊN HỆ</h1>
            <p class="text-muted">Đừng ngần ngại ghé thăm quán hoặc gửi lời nhắn cho chúng tôi</p>
        </div>

        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <div class="contact-info-card bg-white p-5 rounded-5 shadow-sm h-100 flex-column d-flex justify-content-between position-relative overflow-hidden">
                    <div class="bean-bg-mini"></div>
                    
                    <div class="info-items position-relative">
                        <div class="d-flex gap-4 mb-4 pb-4 border-bottom border-light align-items-center">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-geo-alt fs-4"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Địa chỉ quán</h5>
                                <p class="text-muted mb-0">TP. Cao Lãnh, Đồng Tháp</p>
                            </div>
                        </div>

                        <div class="d-flex gap-4 mb-4 pb-4 border-bottom border-light align-items-center">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-telephone fs-4"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Điện thoại</h5>
                                <p class="text-muted mb-0">1900 123 456</p>
                            </div>
                        </div>

                        <div class="d-flex gap-4 mb-4 pb-4 border-bottom border-light align-items-center">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-envelope fs-4"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Email</h5>
                                <p class="text-muted mb-0">contact@tntcoffee.com</p>
                            </div>
                        </div>

                        <div class="d-flex gap-4 align-items-center">
                            <div class="icon-circle bg-gold text-white"><i class="bi bi-clock fs-4"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Giờ mở cửa</h5>
                                <p class="text-muted mb-0">07:00 - 22:00 (Hàng ngày)</p>
                            </div>
                        </div>
                    </div>

                    <div class="social-links mt-5 pt-4 position-relative">
                        <h6 class="fw-bold mb-3 text-muted">Theo dõi mạng xã hội:</h6>
                        <div class="d-flex gap-3">
                            <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="social-btn"><i class="bi bi-tiktok"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="bg-white p-5 rounded-5 shadow-sm">
                    <h3 class="fw-bold mb-4" style="color: var(--color-coffee-dark);">Gửi lời nhắn cho TNT</h3>
                    <form id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control border-light-subtle rounded-3 px-4" id="name" placeholder="Họ và tên">
                                    <label for="name" class="px-4"><i class="bi bi-person me-2"></i>Họ và tên của bạn</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control border-light-subtle rounded-3 px-4" id="email" placeholder="Email">
                                    <label for="email" class="px-4"><i class="bi bi-envelope me-2"></i>Địa chỉ Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control border-light-subtle rounded-3 px-4" id="subject" placeholder="Chủ đề">
                                    <label for="subject" class="px-4"><i class="bi bi-chat-dots me-2"></i>Bạn muốn liên hệ về việc gì?</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-4">
                                    <textarea class="form-control border-light-subtle rounded-3 px-4" id="message" style="height: 150px" placeholder="Lời nhắn"></textarea>
                                    <label for="message" class="px-4"><i class="bi bi-pencil-square me-2"></i>Nội dung chi tiết lời nhắn</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-premium w-100 py-3 rounded-pill fw-bold" style="background-color: var(--color-gold); border: none;">GỬI LỜI NHẮN NGAY <i class="bi bi-send-fill ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Google Map -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="map-container rounded-5 overflow-hidden shadow-sm border border-5 border-white">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.460232428345!2d106.66488127588!3d10.77141295914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f205c066d1f%3A0xe96c4293f77f3d53!2sBitexco%20Financial%20Tower!5e0!3m2!1svi!2s!4v1700000000000!5m2!1svi!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root { --color-gold: #e67e22; }
    .contact-page-wrapper { font-family: 'Be Vietnam Pro', sans-serif; }
    .bg-gold { background-color: var(--color-gold) !important; }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .social-btn { width: 45px; height: 45px; border-radius: 50%; background: #eee; color: #555; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
    .social-btn:hover { background: var(--color-gold); color: white; transform: translateY(-3px); }
    .bean-bg-mini { position: absolute; bottom: -30px; right: -30px; width: 150px; height: 150px; background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M50 10 C70 10 90 30 90 55 C90 80 70 95 50 95 C30 95 10 80 10 55 C10 30 30 10 50 10" fill="%238D6E63" opacity="0.05"/></svg>'); background-size: contain; background-repeat: no-repeat; transform: rotate(15deg); }
    .animate-fade-up { animation: fadeUp 0.8s ease-out; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(230, 126, 34, 0.1); border-color: var(--color-gold); }
</style>

<?php require_once __DIR__ . '/includes/customer_footer.php'; ?>
