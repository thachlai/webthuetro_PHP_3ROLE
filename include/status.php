<?php
// status.php
session_start();
include 'conn.php';
include 'function.php'; // nếu bạn có check quyền admin/staff
check_admin(); // chỉ admin mới đổi trạng thái

// Lấy thông tin
$id = $_GET['id'] ?? null;
$table = $_GET['table'] ?? null;
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php';

if (!$id || !$table) {
    die('Thiếu thông tin!');
}

// Chuẩn hóa bảng
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

// Lấy trạng thái hiện tại
$stmt = mysqli_prepare($conn, "SELECT status FROM $table WHERE $id_col = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $status);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Nếu bảng dùng ENUM (như posts) thì cần xử lý ENUM riêng
if ($table == 'posts') {
    $enum = ['pending', 'active', 'rented', 'expired'];
    $current_index = array_search($status, $enum);
    $new_status = $enum[($current_index + 1) % count($enum)];
} else {
    // Bảng thường 0-1
    $new_status = ($status + 1) % 2; // 0 → 1 → 0
}

// Cập nhật trạng thái mới
$stmt = mysqli_prepare($conn, "UPDATE $table SET status = ? WHERE $id_col = ?");
if ($table == 'posts') {
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Quay về trang list
header("Location: $redirect");
exit;
?>
