<?php
// Cần đảm bảo file này có thể truy cập kết nối CSDL và các hàm cần thiết
include __DIR__ . '/../include/conn.php';
// Không cần session_start() hoặc kiểm tra role nếu đây là dữ liệu công khai (status=1)

header('Content-Type: application/json');

$parent_id = intval($_GET['parent_id'] ?? 0);
$type = strtolower($_GET['type'] ?? '');

$results = [];

if ($parent_id > 0) {
    $sql = "";
    $id_col = "";
    $name_col = "name";

    if ($type === 'district') {
        // Lấy danh sách Huyện (Districts) theo Tỉnh (Province)
        $sql = "SELECT district_id, name FROM Districts WHERE province_id = ? AND status = 1 ORDER BY name ASC";
        $id_col = "district_id";
    } elseif ($type === 'ward') {
        // Lấy danh sách Xã (Wards) theo Huyện (District)
        $sql = "SELECT ward_id, name FROM Wards WHERE district_id = ? AND status = 1 ORDER BY name ASC";
        $id_col = "ward_id";
    }

    if ($sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($r = $res->fetch_assoc()) {
            $results[] = $r;
        }
        $stmt->close();
    }
}

// Trả về kết quả dưới dạng JSON
echo json_encode($results);

// Đảm bảo không có output nào khác ngoài JSON
exit;
?>
