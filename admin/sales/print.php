<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$invoice_id = $_GET['id'] ?? 0;

if (!is_numeric($invoice_id) || $invoice_id <= 0) {
    die('ID đơn hàng không hợp lệ.');
}

try {
    
    $sql = "SELECT i.*, u.full_name as staff_name, u.username as staff_username, t.name as table_name
            FROM orders i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN tables t ON i.table_id = t.id
            WHERE i.id = ?";

    $invoice = fetchOne($pdo, $sql, [$invoice_id]);

    if (!$invoice) {
        die('Không tìm thấy đơn hàng.');
    }

    
    $sql = "SELECT id.*, m.name as product_name, m.code as product_code, m.unit
            FROM order_details id
            LEFT JOIN products m ON id.product_id = m.id
            WHERE id.order_id = ?
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
    <title>Hóa đơn <?= htmlspecialchars($invoice['order_code']) ?> - TNT Coffee</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            padding: 0;
            margin: 0;
        }
        .receipt {
            width: 80mm; /* Standard K80 width */
            margin: 0 auto;
            padding: 10px;
            box-sizing: border-box;
        }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 20px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; }
        .header p { font-size: 11px; margin-top: 2px; line-height: 1.3; }
        
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        
        .info-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; align-items: flex-start; }
        .info-label { font-weight: 600; white-space: nowrap; margin-right: 10px; }
        .info-value { text-align: right; word-break: break-word; }
        
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th { text-align: left; font-size: 11px; border-bottom: 1px solid #000; padding: 5px 0; text-transform: uppercase; }
        table td { font-size: 11px; padding: 6px 0; vertical-align: top; border-bottom: 1px solid #eee; }
        
        .item-name { font-weight: 600; margin-bottom: 2px; }
        .item-meta { font-size: 10px; color: #444; }
        
        .total-section { margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; font-size: 12px; padding: 3px 0; }
        .grand-total { font-size: 16px; font-weight: 800; margin-top: 8px; border-top: 1px solid #000; padding-top: 8px; }
        
        .footer { text-align: center; margin-top: 25px; font-size: 11px; line-height: 1.5; border-top: 1px dashed #000; padding-top: 15px; }
        
        @media print {
            body { width: 80mm; }
            .no-print { display: none; }
            @page { margin: 0; }
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @page { margin: 0; }
        }
        
        .btn-print {
            position: fixed; top: 10px; right: 10px; background: #14b8a6; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-family: sans-serif; font-weight: bold; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 9999;
        }
        .btn-back {
            position: fixed; top: 10px; left: 10px; background: #64748b; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-family: sans-serif; font-weight: bold; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); text-decoration: none; z-index: 9999;
        }
    </style>
</head>
<body>
    <a href="javascript:window.close()" class="btn-back no-print">QUAY LẠI</a>
    <button onclick="window.print()" class="btn-print no-print">IN HÓA ĐƠN</button>

    <div class="receipt">
        <div class="header">
            <h1>TNT COFFEE</h1>
            <p>Hệ thống Chuỗi Cà Phê Nguyên Chất</p>
            <p>Địa chỉ: TP. Cao Lãnh, Đồng Tháp</p>
            <p>SĐT: 1900 123 456</p>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span class="info-label">Mã HĐ:</span>
            <span class="info-value"><?= htmlspecialchars($invoice['order_code']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Ngày:</span>
            <span class="info-value"><?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Khách:</span>
            <span class="info-value"><?= !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'Khách lẻ' ?></span>
        </div>
        <?php if (!empty($invoice['table_name'])): ?>
        <div class="info-row">
            <span class="info-label">Bàn:</span>
            <span class="info-value"><?= htmlspecialchars($invoice['table_name']) ?></span>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th width="50%">Món</th>
                    <th width="15%" style="text-align: center;">SL</th>
                    <th width="35%" style="text-align: right;">Tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_qty = 0;
                if (!empty($details)):
                    foreach ($details as $item): 
                        $total_qty += $item['quantity'];
                ?>
                <tr>
                    <td>
                        <div><?= htmlspecialchars($item['product_name']) ?></div>
                        <div style="font-size: 10px; color: #444;">
                            Size: <?= htmlspecialchars($item['size'] ?? 'M') ?> | <?= number_format($item['price']) ?>đ
                        </div>
                    </td>
                    <td style="text-align: center;"><?= number_format($item['quantity']) ?></td>
                    <td style="text-align: right;"><?= number_format($item['subtotal']) ?></td>
                </tr>
                <?php 
                    endforeach;
                else:
                    // Fallback for Quick Orders (no order_details)
                ?>
                <tr>
                    <td colspan="3">
                        <div style="font-style: italic; color: #666;">Chi tiết món:</div>
                        <div style="font-weight: bold; white-space: pre-wrap;"><?= htmlspecialchars($invoice['note']) ?></div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="total-section">
            <div class="total-row">
                <span>Tổng số lượng:</span>
                <span><?= number_format($total_qty) ?></span>
            </div>
            <div class="total-row">
                <span>PT Thanh toán:</span>
                <span><?= ($invoice['payment_method'] === 'transfer' ? 'Chuyển khoản' : 'Tiền mặt') ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TỔNG CỘNG:</span>
                <span><?= number_format($invoice['total_amount']) ?>đ</span>
            </div>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách!</p>
            <p>Hẹn gặp lại quý khách lần sau</p>
            <p style="font-size: 9px; margin-top: 5px;">Bản in lúc: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
