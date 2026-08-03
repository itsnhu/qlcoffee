<?php
require_once dirname(__DIR__) . '/config/config.php';
// Redirect to unified login page
header('Location: ' . BASE_URL . '/login.php');
exit;
?>
