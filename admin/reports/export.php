<?php



require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';


requireAdmin();


$exportType = isset($_GET['type']) ? $_GET['type'] : 'inventory'; 
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');


if (strtotime($dateFrom) > strtotime($dateTo)) {
    $temp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $temp;
}


function exportCSV($filename, $headers, $data) {
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    
    $output = fopen('php://output', 'w');

    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    
    fputcsv($output, $headers);

    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

try {
    if ($exportType === 'inventory') {
        
        
        

        
        $products = fetchAll($pdo, "
            SELECT m.code, m.name, c.name as category_name, s.name as supplier_name,
                   m.quantity, m.unit, m.price,
                   (m.quantity * m.price) as inventory_value,
                   m.expiry_date,
                   CASE
                       WHEN m.expiry_date IS NULL THEN 'Không có'
                       WHEN m.expiry_date <= CURDATE() THEN 'Đã hết hạn'
                       WHEN DATEDIFF(m.expiry_date, CURDATE()) <= 30 THEN 'Sắp hết hạn'
                       ELSE 'Còn hạn'
                   END as expiry_status,
                   CASE
                       WHEN m.quantity = 0 THEN 'Hết hàng'
                       WHEN m.quantity < 10 THEN 'Sắp hết'
                       ELSE 'Còn hàng'
                   END as stock_status
            FROM products m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN suppliers s ON m.supplier_id = s.id
            ORDER BY m.quantity ASC, m.name ASC
        ");

        
        $totalValue = 0;
        foreach ($products as $product) {
            $totalValue += $product['inventory_value'];
        }

        
        $filename = 'Bao_Cao_Ton_Kho_' . date('Ymd_His') . '.csv';
        $headers = [
            'Mã món',
            'Tên món',
            'Loại',
            'Nhà cung cấp',
            'Số lượng',
            'Đơn vị',
            'Giá bán',
            'Giá trị tồn',
            'Hạn sử dụng',
            'Tình trạng hạn',
            'Tình trạng tồn'
        ];

        $data = [];

        
        $data[] = ['THỐNG KÊ TỒN KHO'];
        $data[] = ['Ngày xuất: ' . date('d/m/Y H:i:s')];
        $data[] = ['Tổng số món: ' . count($products)];
        $data[] = ['Tổng giá trị tồn kho: ' . number_format($totalValue, 0, ',', '.') . ' VND'];
        $data[] = []; 

        
        $data[] = $headers;

        
        foreach ($products as $product) {
            $data[] = [
                $product['code'],
                $product['name'],
                $product['category_name'] ?? '-',
                $product['supplier_name'] ?? '-',
                $product['quantity'],
                $product['unit'],
                number_format($product['price'], 0, ',', '.'),
                number_format($product['inventory_value'], 0, ',', '.'),
                $product['expiry_date'] ? date('d/m/Y', strtotime($product['expiry_date'])) : '-',
                $product['expiry_status'],
                $product['stock_status']
            ];
        }

        
        $data[] = [];
        $data[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            'TỔNG GIÁ TRỊ:',
            number_format($totalValue, 0, ',', '.') . ' VND',
            '',
            '',
            ''
        ];

        exportCSV($filename, [], $data);

    } elseif ($exportType === 'sales') {
        
        
        

        
        $totalRevenue = fetchOne($pdo, "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$dateFrom, $dateTo])['total'] ?? 0;

        
        $totalInvoices = fetchOne($pdo, "
            SELECT COUNT(*) as total
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$dateFrom, $dateTo])['total'] ?? 0;

        
        $dailyRevenue = fetchAll($pdo, "
            SELECT DATE(created_at) as sale_date,
                   COUNT(*) as invoice_count,
                   COALESCE(SUM(total_amount), 0) as revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY sale_date ASC
        ", [$dateFrom, $dateTo]);

        
        $topProducts = fetchAll($pdo, "
            SELECT p.code, p.name, p.unit,
                   SUM(od.quantity) as total_sold,
                   AVG(od.price) as avg_price,
                   SUM(od.subtotal) as revenue
            FROM order_details od
            JOIN products p ON od.product_id = p.id
            JOIN orders o ON od.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY p.id, p.code, p.name, p.unit
            ORDER BY total_sold DESC
        ", [$dateFrom, $dateTo]);

        
        $revenueByEmployee = fetchAll($pdo, "
            SELECT u.username, u.full_name,
                   COUNT(o.id) as invoice_count,
                   COALESCE(SUM(o.total_amount), 0) as revenue
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY u.id, u.username, u.full_name
            ORDER BY revenue DESC
        ", [$dateFrom, $dateTo]);

        
        $filename = 'Bao_Cao_Doanh_Thu_' . date('Ymd', strtotime($dateFrom)) . '_' . date('Ymd', strtotime($dateTo)) . '.csv';

        $data = [];

        
        $data[] = ['THỐNG KÊ DOANH THU'];
        $data[] = ['Từ ngày: ' . date('d/m/Y', strtotime($dateFrom)) . ' - Đến ngày: ' . date('d/m/Y', strtotime($dateTo))];
        $data[] = ['Ngày xuất: ' . date('d/m/Y H:i:s')];
        $data[] = [];

        
        $data[] = ['TỔNG QUAN'];
        $data[] = ['Tổng doanh thu:', number_format($totalRevenue, 0, ',', '.') . ' VND'];
        $data[] = ['Tổng số đơn hàng:', $totalInvoices];
        $data[] = ['Giá trị TB/đơn:', $totalInvoices > 0 ? number_format($totalRevenue / $totalInvoices, 0, ',', '.') . ' VND' : '0 VND'];
        $data[] = [];

        
        $data[] = ['DOANH THU THEO NGÀY'];
        $data[] = ['Ngày', 'Số đơn hàng', 'Doanh thu (VND)'];
        foreach ($dailyRevenue as $daily) {
            $data[] = [
                date('d/m/Y', strtotime($daily['sale_date'])),
                $daily['invoice_count'],
                number_format($daily['revenue'], 0, ',', '.')
            ];
        }
        $data[] = [];

        
        $data[] = ['TOP MÓN BÁN CHẠY'];
        $data[] = ['Mã món', 'Tên món', 'Đơn vị', 'Số lượng bán', 'Giá TB', 'Doanh thu (VND)'];
        foreach ($topProducts as $product) {
            $data[] = [
                $product['code'],
                $product['name'],
                $product['unit'],
                number_format($product['total_sold'], 0, ',', '.'),
                number_format($product['avg_price'], 0, ',', '.'),
                number_format($product['revenue'], 0, ',', '.')
            ];
        }
        $data[] = [];

        
        $data[] = ['DOANH THU THEO NHÂN VIÊN'];
        $data[] = ['Tên đăng nhập', 'Họ tên', 'Số đơn hàng', 'Doanh thu (VND)'];
        foreach ($revenueByEmployee as $employee) {
            $data[] = [
                $employee['username'],
                $employee['full_name'],
                $employee['invoice_count'],
                number_format($employee['revenue'], 0, ',', '.')
            ];
        }

        exportCSV($filename, [], $data);

    } else {
        
        setMessage('danger', 'Loại thống kê không hợp lệ.');
        redirect('/admin/reports/inventory.php');
    }

} catch (PDOException $e) {
    error_log("Export Report Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi xảy ra khi xuất thống kê: ' . $e->getMessage());

    if ($exportType === 'sales') {
        redirect('/admin/reports/sales.php');
    } else {
        redirect('/admin/reports/inventory.php');
    }
}
?>
