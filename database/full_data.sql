-- =============================================
-- PHARMAMANAGER - DATABASE COMPLETE
-- Xóa database cũ và tạo mới với đầy đủ dữ liệu
-- =============================================

DROP DATABASE IF EXISTS pharmamanager;
CREATE DATABASE pharmamanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmamanager;

-- =============================================
-- 1. BẢNG USERS (Người dùng)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password: admin123 (hash bằng password_hash)
INSERT INTO users (username, password, full_name, role, is_active) VALUES
('admin', '$2y$10$8YaT6KEgPKnIbiss8o8OUegP3W3kW7O1MqQM98tqOwDqT/rdQIeLu', 'Nguyễn Văn Admin', 'admin', 1),
('nhanvien', '$2y$10$8YaT6KEgPKnIbiss8o8OUegP3W3kW7O1MqQM98tqOwDqT/rdQIeLu', 'Trần Thị Nhân Viên', 'employee', 1),
('duocsi01', '$2y$10$8YaT6KEgPKnIbiss8o8OUegP3W3kW7O1MqQM98tqOwDqT/rdQIeLu', 'Lê Văn Dược Sĩ', 'employee', 1),
('thungan', '$2y$10$8YaT6KEgPKnIbiss8o8OUegP3W3kW7O1MqQM98tqOwDqT/rdQIeLu', 'Phạm Thị Thu Ngân', 'employee', 1);

-- =============================================
-- 2. BẢNG CATEGORIES (Loại thuốc)
-- =============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, description) VALUES
('Thuốc kháng sinh', 'Các loại thuốc kháng sinh điều trị nhiễm khuẩn'),
('Thuốc giảm đau - hạ sốt', 'Thuốc giảm đau, hạ sốt, chống viêm'),
('Vitamin và khoáng chất', 'Các loại vitamin, thực phẩm chức năng bổ sung'),
('Thuốc tiêu hóa', 'Thuốc hỗ trợ tiêu hóa, dạ dày, đường ruột'),
('Thuốc tim mạch', 'Thuốc điều trị các bệnh tim mạch, huyết áp'),
('Thuốc hô hấp', 'Thuốc điều trị ho, cảm cúm, viêm họng'),
('Thuốc da liễu', 'Thuốc bôi ngoài da, điều trị da liễu'),
('Thuốc mắt - tai - mũi', 'Thuốc nhỏ mắt, tai, mũi'),
('Thuốc thần kinh', 'Thuốc an thần, giảm stress, điều trị thần kinh'),
('Thuốc tiểu đường', 'Thuốc điều trị và kiểm soát tiểu đường');

-- =============================================
-- 3. BẢNG SUPPLIERS (Nhà cung cấp)
-- =============================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO suppliers (name, phone, email, address) VALUES
('Công ty Dược phẩm Hậu Giang', '0292 3891 433', 'contact@dhgpharma.com.vn', '288 Bis Nguyễn Văn Cừ, P. An Hòa, Q. Ninh Kiều, TP. Cần Thơ'),
('Công ty Dược Sài Gòn', '028 3829 7483', 'info@sapharco.com.vn', '18-20 Nguyễn Trường Tộ, Q.4, TP.HCM'),
('Công ty Traphaco', '024 3853 1802', 'traphaco@traphaco.com.vn', '75 Yên Ninh, Ba Đình, Hà Nội'),
('Công ty Dược phẩm Imexpharm', '0277 3862 698', 'contact@imexpharm.com', '04 Đường 30/4, P.1, TP. Cao Lãnh, Đồng Tháp'),
('Công ty Dược Domesco', '0277 3851 941', 'info@domesco.com', 'KCN Sóng Thần, Dĩ An, Bình Dương'),
('Công ty Pymepharco', '0257 3842 284', 'pymepharco@pymepharco.com.vn', '166-170 Nguyễn Huệ, TP. Tuy Hòa, Phú Yên'),
('Công ty Dược phẩm OPC', '028 3755 5995', 'opc@opcpharma.com', '1017 Hồng Bàng, Q.6, TP.HCM'),
('Công ty Dược Hà Tây', '024 3382 5041', 'hataphar@hataphar.com.vn', 'Số 1, Quang Trung, Hà Đông, Hà Nội');

-- =============================================
-- 4. BẢNG MEDICINES (Thuốc)
-- =============================================
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    supplier_id INT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'Viên',
    expiry_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO medicines (name, code, category_id, supplier_id, price, quantity, unit, expiry_date, description) VALUES
-- Thuốc kháng sinh
('Amoxicillin 500mg', 'AMO500', 1, 1, 5000, 500, 'Viên', '2026-06-15', 'Kháng sinh phổ rộng điều trị nhiễm khuẩn'),
('Augmentin 625mg', 'AUG625', 1, 2, 15000, 300, 'Viên', '2026-03-20', 'Kháng sinh kết hợp Amoxicillin + Clavulanic'),
('Azithromycin 250mg', 'AZI250', 1, 3, 8000, 200, 'Viên', '2025-12-10', 'Kháng sinh nhóm Macrolid'),
('Cefixime 200mg', 'CEF200', 1, 4, 12000, 150, 'Viên', '2026-08-25', 'Kháng sinh Cephalosporin thế hệ 3'),
('Ciprofloxacin 500mg', 'CIP500', 1, 1, 6000, 400, 'Viên', '2026-05-30', 'Kháng sinh nhóm Quinolon'),

-- Thuốc giảm đau - hạ sốt
('Paracetamol 500mg', 'PAR500', 2, 5, 1500, 1000, 'Viên', '2027-01-15', 'Thuốc hạ sốt, giảm đau thông dụng'),
('Ibuprofen 400mg', 'IBU400', 2, 6, 3000, 600, 'Viên', '2026-09-20', 'Thuốc giảm đau, chống viêm không steroid'),
('Efferalgan 500mg', 'EFF500', 2, 2, 4000, 400, 'Viên sủi', '2026-04-10', 'Paracetamol dạng sủi bọt'),
('Aspirin 81mg', 'ASP81', 2, 3, 2000, 300, 'Viên', '2026-11-05', 'Aspirin liều thấp phòng ngừa tim mạch'),
('Diclofenac 50mg', 'DIC50', 2, 7, 3500, 250, 'Viên', '2026-07-18', 'Thuốc chống viêm không steroid'),

-- Vitamin và khoáng chất
('Vitamin C 1000mg', 'VITC1000', 3, 1, 2500, 800, 'Viên', '2027-03-25', 'Vitamin C liều cao tăng đề kháng'),
('Vitamin E 400IU', 'VITE400', 3, 5, 5000, 300, 'Viên', '2026-12-30', 'Vitamin E chống oxy hóa'),
('Calcium + D3', 'CALD3', 3, 3, 4500, 400, 'Viên', '2027-02-14', 'Bổ sung canxi và vitamin D3'),
('Vitamin B Complex', 'VITB', 3, 4, 3000, 500, 'Viên', '2026-10-20', 'Phức hợp vitamin nhóm B'),
('Sắt Folic', 'SATFO', 3, 6, 2000, 350, 'Viên', '2026-08-15', 'Bổ sung sắt và acid folic'),

-- Thuốc tiêu hóa
('Omeprazole 20mg', 'OME20', 4, 2, 4000, 400, 'Viên', '2026-05-12', 'Thuốc ức chế bơm proton điều trị dạ dày'),
('Domperidone 10mg', 'DOM10', 4, 7, 2500, 300, 'Viên', '2026-09-08', 'Thuốc chống nôn, tăng nhu động ruột'),
('Smecta', 'SME3G', 4, 8, 6000, 200, 'Gói', '2026-11-22', 'Thuốc điều trị tiêu chảy'),
('Phosphalugel', 'PHO20', 4, 1, 5500, 250, 'Gói', '2026-07-30', 'Thuốc trung hòa acid dạ dày'),
('Duphalac', 'DUP15', 4, 3, 8000, 150, 'Chai', '2026-04-25', 'Thuốc nhuận tràng'),

-- Thuốc tim mạch
('Amlodipine 5mg', 'AML5', 5, 4, 3500, 350, 'Viên', '2026-08-18', 'Thuốc hạ huyết áp nhóm chẹn kênh calci'),
('Losartan 50mg', 'LOS50', 5, 5, 4500, 280, 'Viên', '2026-10-05', 'Thuốc hạ huyết áp nhóm ARB'),
('Atorvastatin 20mg', 'ATO20', 5, 6, 6000, 200, 'Viên', '2026-06-12', 'Thuốc giảm cholesterol'),
('Bisoprolol 5mg', 'BIS5', 5, 2, 5000, 180, 'Viên', '2026-12-20', 'Thuốc chẹn beta điều trị tim mạch'),
('Clopidogrel 75mg', 'CLO75', 5, 7, 7500, 150, 'Viên', '2026-09-15', 'Thuốc chống kết tập tiểu cầu'),

-- Thuốc hô hấp
('Acetylcystein 200mg', 'ACE200', 6, 1, 3000, 400, 'Gói', '2026-05-28', 'Thuốc long đờm, tiêu nhầy'),
('Salbutamol 4mg', 'SAL4', 6, 3, 2000, 300, 'Viên', '2026-11-10', 'Thuốc giãn phế quản'),
('Theophyllin 100mg', 'THE100', 6, 8, 2500, 250, 'Viên', '2026-07-22', 'Thuốc điều trị hen suyễn'),
('Dextromethorphan 15mg', 'DEX15', 6, 4, 1800, 500, 'Viên', '2026-08-30', 'Thuốc giảm ho'),
('Terpin Codein', 'TER10', 6, 2, 4000, 200, 'Viên', '2026-04-15', 'Thuốc trị ho có codein'),

-- Thuốc da liễu
('Betamethasone cream', 'BET15', 7, 5, 25000, 100, 'Tuýp', '2026-06-20', 'Kem bôi chống viêm da'),
('Clotrimazole cream', 'CLO1', 7, 6, 18000, 120, 'Tuýp', '2026-09-25', 'Kem trị nấm da'),
('Acyclovir cream', 'ACY5', 7, 7, 35000, 80, 'Tuýp', '2026-03-18', 'Kem trị herpes'),

-- Thuốc mắt - tai - mũi
('Natri Clorid 0.9%', 'NAC09', 8, 1, 8000, 200, 'Chai', '2026-10-30', 'Nước muối sinh lý nhỏ mắt mũi'),
('Tobramycin eye drops', 'TOB03', 8, 4, 45000, 100, 'Chai', '2025-12-25', 'Thuốc nhỏ mắt kháng sinh'),
('Ofloxacin eye drops', 'OFL03', 8, 3, 38000, 90, 'Chai', '2026-02-14', 'Thuốc nhỏ mắt kháng sinh'),

-- Thuốc thần kinh
('Diazepam 5mg', 'DIA5', 9, 8, 3000, 150, 'Viên', '2026-08-10', 'Thuốc an thần, giải lo âu'),
('Amitriptyline 25mg', 'AMI25', 9, 2, 2500, 180, 'Viên', '2026-11-28', 'Thuốc chống trầm cảm'),

-- Thuốc tiểu đường
('Metformin 500mg', 'MET500', 10, 1, 2000, 600, 'Viên', '2026-07-15', 'Thuốc điều trị tiểu đường type 2'),
('Gliclazide 80mg', 'GLI80', 10, 5, 4000, 300, 'Viên', '2026-09-20', 'Thuốc hạ đường huyết'),
('Glimepiride 2mg', 'GLM2', 10, 6, 5500, 200, 'Viên', '2026-05-10', 'Thuốc điều trị tiểu đường');

-- =============================================
-- 5. BẢNG INVOICES (Hóa đơn bán hàng)
-- =============================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_invoice_code (invoice_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO invoices (invoice_code, user_id, customer_name, customer_phone, total_amount, created_at) VALUES
('HD20241201001', 2, 'Nguyễn Văn A', '0901234567', 125000, '2024-12-01 08:30:00'),
('HD20241201002', 2, 'Trần Thị B', '0912345678', 89000, '2024-12-01 09:15:00'),
('HD20241201003', 3, 'Lê Văn C', '0923456789', 235000, '2024-12-01 10:00:00'),
('HD20241202001', 2, 'Phạm Thị D', '0934567890', 67500, '2024-12-02 08:45:00'),
('HD20241202002', 4, 'Hoàng Văn E', '0945678901', 178000, '2024-12-02 14:20:00'),
('HD20241202003', 3, 'Vũ Thị F', '0956789012', 312000, '2024-12-02 16:30:00'),
('HD20241203001', 2, 'Đặng Văn G', '0967890123', 45000, '2024-12-03 09:00:00'),
('HD20241203002', 4, 'Bùi Thị H', '0978901234', 156000, '2024-12-03 11:30:00'),
('HD20241203003', 3, 'Ngô Văn I', '0989012345', 289000, '2024-12-03 15:00:00'),
('HD20241203004', 2, 'Dương Thị K', '0990123456', 98000, '2024-12-03 17:45:00');

-- =============================================
-- 6. BẢNG INVOICE_DETAILS (Chi tiết hóa đơn)
-- =============================================
CREATE TABLE invoice_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    INDEX idx_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO invoice_details (invoice_id, medicine_id, quantity, price, subtotal) VALUES
-- Hóa đơn 1
(1, 6, 20, 1500, 30000),
(1, 1, 10, 5000, 50000),
(1, 16, 10, 4000, 40000),
(1, 11, 2, 2500, 5000),
-- Hóa đơn 2
(2, 6, 30, 1500, 45000),
(2, 26, 10, 3000, 30000),
(2, 14, 4, 3000, 12000),
(2, 15, 1, 2000, 2000),
-- Hóa đơn 3
(3, 2, 10, 15000, 150000),
(3, 21, 10, 3500, 35000),
(3, 6, 20, 1500, 30000),
(3, 11, 8, 2500, 20000),
-- Hóa đơn 4
(4, 6, 20, 1500, 30000),
(4, 17, 15, 2500, 37500),
-- Hóa đơn 5
(5, 22, 20, 4500, 90000),
(5, 7, 20, 3000, 60000),
(5, 14, 8, 3000, 24000),
(5, 15, 2, 2000, 4000),
-- Hóa đơn 6
(6, 3, 20, 8000, 160000),
(6, 16, 20, 4000, 80000),
(6, 11, 20, 2500, 50000),
(6, 26, 6, 3000, 18000),
(6, 15, 2, 2000, 4000),
-- Hóa đơn 7
(7, 6, 30, 1500, 45000),
-- Hóa đơn 8
(8, 23, 15, 6000, 90000),
(8, 21, 10, 3500, 35000),
(8, 28, 10, 1800, 18000),
(8, 14, 4, 3000, 12000),
(8, 15, 0.5, 2000, 1000),
-- Hóa đơn 9
(9, 4, 15, 12000, 180000),
(9, 16, 15, 4000, 60000),
(9, 6, 20, 1500, 30000),
(9, 26, 5, 3000, 15000),
(9, 15, 2, 2000, 4000),
-- Hóa đơn 10
(10, 6, 40, 1500, 60000),
(10, 11, 10, 2500, 25000),
(10, 14, 4, 3000, 12000),
(10, 15, 0.5, 2000, 1000);

-- =============================================
-- 7. BẢNG IMPORTS (Phiếu nhập hàng)
-- =============================================
CREATE TABLE imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    supplier_id INT NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    INDEX idx_import_code (import_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO imports (import_code, user_id, supplier_id, total_amount, note, created_at) VALUES
('PN20241115001', 1, 1, 5000000, 'Nhập hàng định kỳ tháng 11', '2024-11-15 09:00:00'),
('PN20241120001', 1, 2, 3500000, 'Nhập bổ sung thuốc kháng sinh', '2024-11-20 10:30:00'),
('PN20241125001', 1, 3, 2800000, 'Nhập vitamin và thực phẩm chức năng', '2024-11-25 14:00:00'),
('PN20241130001', 1, 4, 4200000, 'Nhập hàng cuối tháng', '2024-11-30 08:45:00'),
('PN20241201001', 1, 5, 6500000, 'Nhập hàng đầu tháng 12', '2024-12-01 09:15:00');

-- =============================================
-- 8. BẢNG IMPORT_DETAILS (Chi tiết phiếu nhập)
-- =============================================
CREATE TABLE import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    INDEX idx_import (import_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO import_details (import_id, medicine_id, quantity, price, subtotal) VALUES
-- Phiếu nhập 1
(1, 1, 200, 3500, 700000),
(1, 6, 500, 1000, 500000),
(1, 11, 300, 1800, 540000),
(1, 16, 200, 2800, 560000),
(1, 21, 150, 2500, 375000),
(1, 26, 200, 2000, 400000),
(1, 39, 300, 1400, 420000),
(1, 40, 200, 2800, 560000),
(1, 14, 250, 2000, 500000),
(1, 15, 200, 1400, 280000),
-- Phiếu nhập 2
(2, 2, 100, 10000, 1000000),
(2, 3, 100, 5500, 550000),
(2, 4, 80, 8000, 640000),
(2, 5, 150, 4000, 600000),
(2, 17, 150, 1800, 270000),
(2, 18, 100, 4000, 400000),
-- Phiếu nhập 3
(3, 11, 200, 1800, 360000),
(3, 12, 150, 3500, 525000),
(3, 13, 200, 3000, 600000),
(3, 14, 250, 2000, 500000),
(3, 15, 200, 1400, 280000),
(3, 31, 50, 17000, 850000),
-- Phiếu nhập 4
(4, 21, 150, 2500, 375000),
(4, 22, 120, 3200, 384000),
(4, 23, 100, 4200, 420000),
(4, 24, 100, 3500, 350000),
(4, 25, 80, 5200, 416000),
(4, 37, 80, 2000, 160000),
(4, 38, 100, 1800, 180000),
-- Phiếu nhập 5
(5, 6, 500, 1000, 500000),
(5, 7, 300, 2000, 600000),
(5, 8, 200, 2800, 560000),
(5, 9, 150, 1400, 210000),
(5, 10, 150, 2400, 360000),
(5, 26, 200, 2000, 400000),
(5, 27, 150, 1400, 210000),
(5, 28, 250, 1200, 300000),
(5, 29, 200, 2800, 560000),
(5, 30, 100, 1200, 120000);

-- =============================================
-- HOÀN THÀNH!
-- =============================================
SELECT 'Database đã được tạo thành công!' AS 'Thông báo';
SELECT CONCAT('Số users: ', COUNT(*)) AS 'Thống kê' FROM users
UNION ALL
SELECT CONCAT('Số categories: ', COUNT(*)) FROM categories
UNION ALL
SELECT CONCAT('Số suppliers: ', COUNT(*)) FROM suppliers
UNION ALL
SELECT CONCAT('Số medicines: ', COUNT(*)) FROM medicines
UNION ALL
SELECT CONCAT('Số invoices: ', COUNT(*)) FROM invoices
UNION ALL
SELECT CONCAT('Số imports: ', COUNT(*)) FROM imports;
