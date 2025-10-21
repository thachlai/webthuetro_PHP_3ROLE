<?php
// include/conn.php - File kết nối cơ sở dữ liệu (PDO)

// 1. Cấu hình kết nối CSDL
$host = "localhost";
$db_name = "thuetro"; // ⚠️ THAY THẾ BẰNG TÊN CSDL THỰC TẾ CỦA BẠN
$username = "root";       // Mặc định của XAMPP
$password = "";           // Mặc định của XAMPP (thường là để trống)

try {
    // Tạo đối tượng kết nối PDO
    $conn = new PDO("mysql:host={$host};dbname={$db_name}", $username, $password);
    
    // Đặt chế độ báo lỗi và ném ngoại lệ (giúp phát hiện lỗi SQL dễ dàng)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Đặt chế độ fetch mặc định là mảng kết hợp (ASSOC)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Đặt encoding UTF8 để hiển thị tiếng Việt chính xác
    $conn->exec("set names utf8");

} catch(PDOException $exception) {
    // In ra thông báo lỗi nếu kết nối thất bại
    die("Lỗi kết nối cơ sở dữ liệu: " . $exception->getMessage());
}
?>