<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'thuetro';

// Kết nối MySQLi
$conn = new mysqli($host, $user, $pass, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập UTF-8 để không lỗi tiếng Việt
$conn->set_charset("utf8");
?>
