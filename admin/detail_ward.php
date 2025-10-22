<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$ward_id = intval($_GET['id'] ?? 0);
if (!$ward_id) {
    echo "<p>Không có xã/phường nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu xã/phường
$stmt = $conn->prepare("SELECT * FROM Wards WHERE ward_id=?");
$stmt->bind_param("i", $ward_id);
$stmt->execute();
$result = $stmt->get_result();
$ward = $result->fetch_assoc();
$stmt->close();

// Lấy danh sách huyện để chọn
$districts = [];
$dist_res = mysqli_query($conn, "SELECT district_id, name FROM Districts WHERE status=1");
while ($row = mysqli_fetch_assoc($dist_res)) $districts[] = $row;

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $district_id = intval($_POST['district_id'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    if (!$name) $errors[] = "Tên xã/phường không được để trống.";
    if (!$district_id) $errors[] = "Phải chọn huyện.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Wards SET name=?, district_id=?, status=? WHERE ward_id=?");
        $stmt->bind_param("siii", $name, $district_id, $status, $ward_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật xã/phường thành công!";
            $ward['name'] = $name;
            $ward['district_id'] = $district_id;
            $ward['status'] = $status;
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
    <div class="page-title"><h2>Chi tiết Xã/Phường</h2></div>

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
                <span class="label">Tên Xã/Phường:</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($ward['name']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Thuộc Huyện:</span>
                <select name="district_id">
                    <option value="">Chọn Huyện</option>
                    <?php foreach($districts as $d): ?>
                        <option value="<?php echo $d['district_id']; ?>" <?php if($ward['district_id']==$d['district_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($ward['status']==1) echo 'selected'; ?>>Hoạt động</option>
                    <option value="0" <?php if($ward['status']==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>
        </div>

        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_ward.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
