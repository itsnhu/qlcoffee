<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$type = $_GET['type'] ?? 'recent';
$response = [
    'title' => 'Hoạt động mới nhất',
    'html' => ''
];

try {
    switch ($type) {
        case 'revenue':
        case 'orders':
            $response['title'] = ($type == 'revenue' ? 'Doanh thu' : 'Đơn hàng') . ' hôm nay';
            $data = fetchAll($pdo, "
                SELECT i.*, u.full_name as employee_name, t.name as table_name
                FROM orders i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN tables t ON i.table_id = t.id
                WHERE DATE(i.created_at) = CURDATE()
                ORDER BY i.created_at DESC
            ");
            
            // If no orders today, get the most recent ones as fallback for demo
            if (empty($data)) {
                 $data = fetchAll($pdo, "
                    SELECT i.*, u.full_name as employee_name, t.name as table_name
                    FROM orders i
                    LEFT JOIN users u ON i.user_id = u.id
                    LEFT JOIN tables t ON i.table_id = t.id
                    WHERE DATE(i.created_at) = (SELECT MAX(DATE(created_at)) FROM orders)
                    ORDER BY i.created_at DESC
                    LIMIT 10
                ");
                $response['title'] .= ' (Mới nhất)';
            }

            foreach ($data as $order) {
                $statusColor = $order['status'] == 'paid' ? '#10b981' : '#f59e0b';
                $tableName = $order['table_name'] ?: 'Mang đi';
                $time = date('H:i - d/m/Y', strtotime($order['created_at']));
                $amount = formatCurrency($order['total_amount']);
                
                $response['html'] .= "
                    <div class='activity-item'>
                        <div class='activity-info'>
                            <span class='activity-text'>
                                <span class='status-dot' style='background: $statusColor'></span>
                                Đơn hàng #{$order['order_code']} - $amount ($tableName)
                            </span>
                            <span class='activity-time'>$time</span>
                        </div>
                        <a href='sales/view.php?id={$order['id']}' class='btn btn-sm btn-outline-primary rounded-pill px-3'>Chi tiết</a>
                    </div>";
            }
            break;

        case 'customers':
            $response['title'] = 'Khách hàng hoạt động';
            $data = fetchAll($pdo, "SELECT * FROM customers WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10");
            foreach ($data as $customer) {
                $time = date('d/m/Y', strtotime($customer['created_at']));
                $response['html'] .= "
                    <div class='activity-item'>
                        <div class='activity-info'>
                            <span class='activity-text'>
                                <span class='status-dot' style='background: #3b82f6'></span>
                                {$customer['full_name']} - {$customer['phone']}
                            </span>
                            <span class='activity-time'>Ngày tham gia: $time</span>
                        </div>
                        <a href='customers/edit.php?id={$customer['id']}' class='btn btn-sm btn-outline-primary rounded-pill px-3'>Sửa</a>
                    </div>";
            }
            break;

        case 'products':
            $response['title'] = 'Sản phẩm trên Menu';
            $data = fetchAll($pdo, "SELECT * FROM products ORDER BY id DESC LIMIT 10");
            foreach ($data as $product) {
                $price = formatCurrency($product['price']);
                $response['html'] .= "
                    <div class='activity-item'>
                        <div class='activity-info'>
                            <span class='activity-text'>
                                <span class='status-dot' style='background: #f59e0b'></span>
                                {$product['name']} - $price
                            </span>
                            <span class='activity-time'>Mã: {$product['code']}</span>
                        </div>
                        <a href='products/index.php?id={$product['id']}' class='btn btn-sm btn-outline-primary rounded-pill px-3'>Sửa</a>
                    </div>";
            }
            break;

        default:
            $response['title'] = 'Hoạt động mới nhất';
            $data = fetchAll($pdo, "
                SELECT i.*, u.full_name as employee_name, t.name as table_name
                FROM orders i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN tables t ON i.table_id = t.id
                ORDER BY i.created_at DESC
                LIMIT 6
            ");
            foreach ($data as $order) {
                $statusColor = $order['status'] == 'paid' ? '#10b981' : '#f59e0b';
                $tableName = $order['table_name'] ?: 'Mang đi';
                $time = date('H:i - d/m/Y', strtotime($order['created_at']));
                $amount = formatCurrency($order['total_amount']);
                
                $response['html'] .= "
                    <div class='activity-item'>
                        <div class='activity-info'>
                            <span class='activity-text'>
                                <span class='status-dot' style='background: $statusColor'></span>
                                Đơn hàng #{$order['order_code']} - $amount ($tableName)
                            </span>
                            <span class='activity-time'>$time</span>
                        </div>
                        <a href='sales/view.php?id={$order['id']}' class='btn btn-sm btn-outline-primary rounded-pill px-3'>Chi tiết</a>
                    </div>";
            }
            break;
    }

    if (empty($response['html'])) {
        $response['html'] = "<div class='text-center py-5 text-muted'>Chưa có dữ liệu nào.</div>";
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');
echo json_encode($response);
