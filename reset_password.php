<?php
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $password = '123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 1. Reset Admin Password
    $sqlAdmin = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = $pdo->prepare($sqlAdmin);
    $stmt->execute([$hashed_password]);
    echo "✅ Đã reset mật khẩu ADMIN thành công (Pass: 123)<br>";

    // 2. Reset Staff Password
    $sqlStaff = "UPDATE users SET password = ? WHERE username = 'staff'";
    $stmt = $pdo->prepare($sqlStaff);
    $stmt->execute([$hashed_password]);
    echo "✅ Đã reset mật khẩu STAFF thành công (Pass: 123)<br>";

    // 3. Reset Customer Password
    $sqlCustomer = "UPDATE customers SET password = ? WHERE email = 'customer@gmail.com'";
    $stmt = $pdo->prepare($sqlCustomer);
    $stmt->execute([$hashed_password]);
    echo "✅ Đã reset mật khẩu CUSTOMER thành công (Pass: 123)<br>";

    echo "<hr>";
    echo "<h3>🎉 Xong! Bây giờ bạn có thể đăng nhập lại được rồi.</h3>";
    echo "<a href='login.php'>Bấm vào đây để Đăng nhập</a>";

} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
