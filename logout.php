<?php

require_once 'config/config.php';

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

header("Location: " . BASE_URL . "/login.php");
exit();
?>
