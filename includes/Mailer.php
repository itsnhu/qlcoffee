<?php
require_once dirname(__DIR__) . '/config/mail.php';

class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_secure;
    private $from_email;
    private $from_name;
    private $socket;
    private $debug = false;

    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_user = SMTP_USERNAME;
        $this->smtp_pass = SMTP_PASSWORD;
        $this->smtp_secure = SMTP_SECURE;
        $this->from_email = MAIL_FROM_EMAIL;
        $this->from_name = MAIL_FROM_NAME;
    }

    public function send($to, $subject, $body, $isHtml = true) {
        if (!SMTP_ENABLED) {
            return $this->sendViaMail($to, $subject, $body, $isHtml);
        }

        try {
            return $this->sendViaSMTP($to, $subject, $body, $isHtml);
        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    private function sendViaMail($to, $subject, $body, $isHtml) {
        $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        if ($isHtml) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        }
        return mail($to, $subject, $body, $headers);
    }

    private function sendViaSMTP($to, $subject, $body, $isHtml) {
        $host = ($this->smtp_secure === 'tls') ? $this->smtp_host : "ssl://" . $this->smtp_host;

        $this->socket = @fsockopen($host, $this->smtp_port, $errno, $errstr, 30);

        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        $this->getResponse();

        $this->sendCommand("EHLO " . gethostname());

        if ($this->smtp_secure === 'tls') {
            $this->sendCommand("STARTTLS");
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand("EHLO " . gethostname());
        }

        if (SMTP_AUTH) {
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->smtp_user));
            $this->sendCommand(base64_encode($this->smtp_pass));
        }

        $this->sendCommand("MAIL FROM: <{$this->from_email}>");
        $this->sendCommand("RCPT TO: <{$to}>");
        $this->sendCommand("DATA");

        $contentType = $isHtml ? "text/html" : "text/plain";
        $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $headers .= "\r\n";

        $this->sendCommand($headers . $body . "\r\n.");
        $this->sendCommand("QUIT");

        fclose($this->socket);

        return true;
    }

    private function sendCommand($command) {
        fputs($this->socket, $command . "\r\n");
        return $this->getResponse();
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        if ($this->debug) {
            echo "SERVER: " . $response . "<br>";
        }
        return $response;
    }

    public function sendOrderConfirmation($order, $orderDetails) {
        $subject = "Xác nhận đơn hàng #{$order['order_code']} - PharmaManager";

        $itemsHtml = "";
        foreach ($orderDetails as $item) {
            $itemsHtml .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['medicine_name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . number_format($item['price'], 0, ',', '.') . " ₫</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . number_format($item['subtotal'], 0, ',', '.') . " ₫</td>
            </tr>";
        }

        $statusText = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã hoàn thành',
            'cancelled' => 'Đã hủy'
        ];

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; padding: 20px; background: #0d6efd; color: white;'>
                    <h1 style='margin: 0;'>PharmaManager</h1>
                    <p style='margin: 5px 0 0 0;'>Nhà thuốc trực tuyến</p>
                </div>

                <div style='padding: 30px; background: #f8f9fa;'>
                    <h2 style='color: #0d6efd;'>Xác nhận đơn hàng</h2>
                    <p>Xin chào <strong>{$order['customer_name']}</strong>,</p>
                    <p>Cảm ơn bạn đã đặt hàng tại PharmaManager. Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>

                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #333;'>Thông tin đơn hàng</h3>
                        <p><strong>Mã đơn hàng:</strong> {$order['order_code']}</p>
                        <p><strong>Ngày đặt:</strong> " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
                        <p><strong>Trạng thái:</strong> {$statusText[$order['status']]}</p>
                        <p><strong>Phương thức thanh toán:</strong> Thanh toán khi nhận hàng (COD)</p>
                    </div>

                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #333;'>Địa chỉ giao hàng</h3>
                        <p><strong>{$order['customer_name']}</strong></p>
                        <p>{$order['customer_phone']}</p>
                        <p>{$order['shipping_address']}</p>
                    </div>

                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #333;'>Chi tiết đơn hàng</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <thead>
                                <tr style='background: #f8f9fa;'>
                                    <th style='padding: 10px; text-align: left;'>Sản phẩm</th>
                                    <th style='padding: 10px; text-align: center;'>SL</th>
                                    <th style='padding: 10px; text-align: right;'>Đơn giá</th>
                                    <th style='padding: 10px; text-align: right;'>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$itemsHtml}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='3' style='padding: 15px; text-align: right; font-weight: bold; font-size: 16px;'>Tổng cộng:</td>
                                    <td style='padding: 15px; text-align: right; font-weight: bold; font-size: 18px; color: #dc3545;'>" . number_format($order['total_amount'], 0, ',', '.') . " ₫</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    " . (!empty($order['note']) ? "<div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #333;'>Ghi chú</h3>
                        <p>{$order['note']}</p>
                    </div>" : "") . "

                    <p style='color: #666; font-size: 14px;'>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
                </div>

                <div style='text-align: center; padding: 20px; color: #666; font-size: 12px;'>
                    <p>© " . date('Y') . " PharmaManager. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        return $this->send($order['customer_email'], $subject, $body, true);
    }
}
?>
