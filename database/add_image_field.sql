-- =============================================
-- PHARMAMANAGERNEW - ADD IMAGE FIELD TO MEDICINES
-- Chạy file này để thêm trường image vào bảng medicines
-- =============================================

USE pharmamanagernew;

-- Thêm trường image vào bảng medicines
ALTER TABLE medicines
ADD COLUMN image VARCHAR(500) NULL DEFAULT NULL AFTER description;

-- Cập nhật thông báo
SELECT 'Đã thêm trường image vào bảng medicines thành công!' AS 'Thông báo';
