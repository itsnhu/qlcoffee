<?php

require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/employee/dashboard.php');
    }
}

redirect('/login.php');
?>
