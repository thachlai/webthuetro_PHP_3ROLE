<?php
// delete.php
session_start();
include 'conn.php';
include 'function.php';
check_admin(); // chỉ admin mới xóa

// Lấy thông tin
$id = $_GET['id'] ?? null;
$table = $_GET['table'] ?? null;
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php';

if (!$id || !$table) {
    die('Thiếu thông tin xóa!');
}

// Chuẩn hóa tên bảng
$table = strtolower($table);

// Mapping cột ID đúng theo bảng
switch($table) {
    case 'users':
        $id_col = 'user_id';
        break;
    case 'categories':
        $id_col = 'category_id';
        break;
    case 'provinces':
        $id_col = 'province_id';
        break;
    case 'districts':
        $id_col = 'district_id';
        break;
    case 'wards':
        $id_col = 'ward_id';
        break;
    case 'promotion_packages':
        $id_col = 'package_id';
        break;
    case 'posts':
        $id_col = 'id_post';
        break;
    default:
        die('Bảng không hợp lệ!');
}

// Thực hiện xóa
$stmt = mysqli_prepare($conn, "DELETE FROM $table WHERE $id_col = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (mysqli_stmt_execute($stmt)) {
    header("Location: $redirect");
    exit;
} else {
    die("Xóa thất bại!");
}
?>
