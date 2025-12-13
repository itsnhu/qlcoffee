<?php
// =============================================
// SMTP Email Configuration - Brevo (SendinBlue)
// =============================================

// Kích hoạt SMTP (true) hoặc dùng PHP mail() (false)
define('SMTP_ENABLED', true);

// Thông tin SMTP Server - Brevo
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Xác thực SMTP
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'h5studiogl@gmail.com');
define('SMTP_PASSWORD', 'fScdnZ4WmEDqjBA1');

// Thông tin người gửi
define('MAIL_FROM_EMAIL', 'h5studiogl@gmail.com');
define('MAIL_FROM_NAME', 'PharmaManager');

?>
