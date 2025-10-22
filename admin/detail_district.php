<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$district_id = intval($_GET['id'] ?? 0);
if (!$district_id) {
    echo "<p>Không có huyện nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu huyện
$stmt = $conn->prepare("SELECT * FROM Districts WHERE district_id=?");
$stmt->bind_param("i", $district_id);
$stmt->execute();
$result = $stmt->get_result();
$district = $result->fetch_assoc();
$stmt->close();

// Lấy danh sách tỉnh để chọn
$provinces = [];
$prov_res = mysqli_query($conn, "SELECT province_id, name FROM Provinces WHERE status=1");
while ($row = mysqli_fetch_assoc($prov_res)) $provinces[] = $row;

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $province_id = intval($_POST['province_id'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    if (!$name) $errors[] = "Tên huyện không được để trống.";
    if (!$province_id) $errors[] = "Phải chọn tỉnh.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Districts SET name=?, province_id=?, status=? WHERE district_id=?");
        $stmt->bind_param("siii", $name, $province_id, $status, $district_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật huyện thành công!";
            $district['name'] = $name;
            $district['province_id'] = $province_id;
            $district['status'] = $status;
        } else {
            $errors[] = "Lỗi cập nhật: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Chi tiết Huyện</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message" style="background-color:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="detail-wrapper">
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Tên Huyện:</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($district['name']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Thuộc Tỉnh:</span>
                <select name="province_id">
                    <option value="">Chọn Tỉnh</option>
                    <?php foreach($provinces as $p): ?>
                        <option value="<?php echo $p['province_id']; ?>" <?php if($district['province_id']==$p['province_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($district['status']==1) echo 'selected'; ?>>Hoạt động</option>
                    <option value="0" <?php if($district['status']==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>
        </div>

        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_district.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
