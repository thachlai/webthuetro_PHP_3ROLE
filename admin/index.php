<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_login();
check_admin();
?>
<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/list_admin.css">
