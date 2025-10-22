<?php
session_start();

// Hủy tất cả session
$_SESSION = [];
session_destroy();

// Chuyển hướng về trang login
header('Location: ../auth/login.php');
exit;
