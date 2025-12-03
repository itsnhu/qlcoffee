<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$invoice_id = $_GET['id'] ?? 0;

if (!is_numeric($invoice_id) || $invoice_id <= 0) {
    die('ID hóa đơn không hợp lệ.');
}

try {
    
    $sql = "SELECT i.*, u.full_name as staff_name, u.username as staff_username
            FROM invoices i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?";

    
    if ($_SESSION['role'] === 'employee') {
        $sql .= " AND i.user_id = ?";
        $invoice = fetchOne($pdo, $sql, [$invoice_id, $_SESSION['user_id']]);
    } else {
        $invoice = fetchOne($pdo, $sql, [$invoice_id]);
    }

    if (!$invoice) {
        die('Không tìm thấy hóa đơn hoặc bạn không có quyền truy cập.');
    }

    
    $sql = "SELECT id.*, m.name as medicine_name, m.code as medicine_code, m.unit
            FROM invoice_details id
            LEFT JOIN medicines m ON id.medicine_id = m.id
            WHERE id.invoice_id = ?
            ORDER BY id.id ASC";

    $details = fetchAll($pdo, $sql, [$invoice_id]);

} catch (PDOException $e) {
    error_log("Load Invoice Error: " . $e->getMessage());
    die('Có lỗi khi tải thông tin hóa đơn.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn <?php echo htmlspecialchars($invoice['invoice_code']); ?> - PharmaManager</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            border: 2px solid #000;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #000;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #0d6efd;
            margin-bottom: 5px;
        }

        .header .shop-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .invoice-title {
            text-align: center;
            margin: 20px 0;
        }

        .invoice-title h2 {
            font-size: 24px;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .invoice-info .left,
        .invoice-info .right {
            flex: 1;
        }

        .invoice-info p {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .invoice-info strong {
            display: inline-block;
            min-width: 120px;
        }

        .customer-info {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
        }

        .customer-info h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #0d6efd;
        }

        .customer-info p {
            margin-bottom: 5px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background: #0d6efd;
            color: white;
        }

        table th,
        table td {
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        table th {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: bold;
        }

        table td {
            font-size: 13px;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }

        table tfoot td {
            font-size: 16px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .total-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #0d6efd;
        }

        .total-section .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .total-section .total-row.grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #198754;
            border-top: 2px solid #000;
            padding-top: 15px;
            margin-top: 15px;
        }

        .total-in-words {
            margin-top: 15px;
            font-style: italic;
            font-size: 14px;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .footer .signature {
            text-align: center;
            flex: 1;
        }

        .footer .signature p {
            margin-bottom: 60px;
            font-weight: bold;
        }

        .footer .signature .name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .thank-you {
            text-align: center;
            margin-top: 40px;
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
        }

        @media print {
            .container {
                border: none;
                margin: 0;
                padding: 20px;
            }

            @page {
                margin: 10mm;
            }

            .no-print {
                display: none;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .print-button:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button onclick="window.print()" class="print-button no-print">
        🖨️ In hóa đơn
    </button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏥 PHARMAMANAGER</h1>
            <div class="shop-info">
                Hệ thống quản lý nhà thuốc<br>
                Địa chỉ: 123 Đường ABC, Quận XYZ, TP. HCM<br>
                Hotline: 1900-xxxx | Email: info@pharmamanager.com
            </div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">
            <h2>HÓA ĐƠN BÁN HÀNG</h2>
            <p><strong>Mã hóa đơn: <?php echo htmlspecialchars($invoice['invoice_code']); ?></strong></p>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="left">
                <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></p>
                <p><strong>Giờ tạo:</strong> <?php echo date('H:i:s', strtotime($invoice['created_at'])); ?></p>
            </div>
            <div class="right">
                <p><strong>Nhân viên:</strong> <?php echo htmlspecialchars($invoice['staff_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($invoice['staff_username']); ?></p>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="customer-info">
            <h3>THÔNG TIN KHÁCH HÀNG</h3>
            <p>
                <strong>Tên khách hàng:</strong>
                <?php echo !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'Khách lẻ'; ?>
            </p>
            <p>
                <strong>Số điện thoại:</strong>
                <?php echo !empty($invoice['customer_phone']) ? htmlspecialchars($invoice['customer_phone']) : '-'; ?>
            </p>
        </div>

        <!-- Invoice Details Table -->
        <table>
            <thead>
                <tr>
                    <th width="5%">STT</th>
                    <th width="15%">Mã thuốc</th>
                    <th width="35%">Tên thuốc</th>
                    <th width="8%" class="text-center">ĐVT</th>
                    <th width="10%" class="text-right">SL</th>
                    <th width="12%" class="text-right">Đơn giá</th>
                    <th width="15%" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                $total_qty = 0;
                foreach ($details as $detail):
                    $total_qty += $detail['quantity'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $stt++; ?></td>
                    <td><?php echo htmlspecialchars($detail['medicine_code']); ?></td>
                    <td><?php echo htmlspecialchars($detail['medicine_name']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($detail['unit']); ?></td>
                    <td class="text-right"><?php echo number_format($detail['quantity']); ?></td>
                    <td class="text-right"><?php echo number_format($detail['price']); ?></td>
                    <td class="text-right"><?php echo number_format($detail['subtotal']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">Tổng số lượng:</td>
                    <td class="text-right"><?php echo number_format($total_qty); ?></td>
                    <td class="text-right">TỔNG CỘNG:</td>
                    <td class="text-right" style="color: #198754; font-size: 18px;">
                        <?php echo number_format($invoice['total_amount']); ?>đ
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row grand-total">
                <span>TỔNG TIỀN THANH TOÁN:</span>
                <span><?php echo number_format($invoice['total_amount']); ?>đ</span>
            </div>
            <div class="total-in-words">
                <strong>Bằng chữ:</strong>
                <?php
                
                function numberToVietnameseWords($number) {
                    $ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
                    $tens = ['', 'mười', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];

                    if ($number == 0) return 'không đồng';

                    $result = '';

                    
                    $billion = floor($number / 1000000000);
                    if ($billion > 0) {
                        $result .= numberToVietnameseWords($billion) . ' tỷ ';
                        $number %= 1000000000;
                    }

                    
                    $million = floor($number / 1000000);
                    if ($million > 0) {
                        $result .= numberToVietnameseWords($million) . ' triệu ';
                        $number %= 1000000;
                    }

                    
                    $thousand = floor($number / 1000);
                    if ($thousand > 0) {
                        $result .= numberToVietnameseWords($thousand) . ' nghìn ';
                        $number %= 1000;
                    }

                    
                    $hundred = floor($number / 100);
                    if ($hundred > 0) {
                        $result .= $ones[$hundred] . ' trăm ';
                        $number %= 100;
                    }

                    
                    if ($number >= 10) {
                        $ten = floor($number / 10);
                        $one = $number % 10;

                        if ($ten == 1) {
                            $result .= 'mười ';
                        } else {
                            $result .= $tens[$ten] . ' ';
                        }

                        if ($one > 0) {
                            if ($one == 5 && $ten >= 1) {
                                $result .= 'lăm ';
                            } elseif ($one == 1 && $ten >= 2) {
                                $result .= 'mốt ';
                            } else {
                                $result .= $ones[$one] . ' ';
                            }
                        }
                    } elseif ($number > 0) {
                        $result .= $ones[$number] . ' ';
                    }

                    return trim($result);
                }

                $words = ucfirst(numberToVietnameseWords($invoice['total_amount'])) . ' đồng chẵn.';
                echo $words;
                ?>
            </div>
        </div>

        <!-- Footer / Signature -->
        <div class="footer">
            <div class="signature">
                <p>Khách hàng</p>
                <div class="name">(Ký và ghi rõ họ tên)</div>
            </div>
            <div class="signature">
                <p>Nhân viên bán hàng</p>
                <div class="name"><?php echo htmlspecialchars($invoice['staff_name']); ?></div>
            </div>
        </div>

        <!-- Thank You -->
        <div class="thank-you">
            Cảm ơn quý khách! Hẹn gặp lại!
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
