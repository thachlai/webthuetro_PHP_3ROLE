<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';

$name = '';
$province_id = '';
$status = 1;
$errors = [];
$success_message = '';

// Lấy danh sách tỉnh/thành phố
$provinces = [];
$result = $conn->query("SELECT province_id, name FROM Provinces WHERE status=1 ORDER BY name ASC");
if ($result) {
    while($row = $result->fetch_assoc()) $provinces[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $province_id = intval($_POST['province_id'] ?? 0);
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    if (!$name) $errors[] = "Tên quận/huyện không được để trống.";
    if (!$province_id) $errors[] = "Vui lòng chọn tỉnh/thành phố.";

    // Kiểm tra trùng tên trong cùng tỉnh
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT district_id FROM Districts WHERE name=? AND province_id=?");
        if ($stmt) {
            $stmt->bind_param("si",$name,$province_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Tên quận/huyện đã tồn tại trong tỉnh/thành phố này.";
            $stmt->close();
        } else $errors[] = "Lỗi hệ thống (prepare check).";
    }

    // Thêm mới
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Districts (province_id,name,status) VALUES (?,?,?)");
        if ($stmt) {
            $stmt->bind_param("isi",$province_id,$name,$status);
            if ($stmt->execute()) {
                $success_message = "🎉 Thêm quận/huyện thành công!";
                $name = '';
                $province_id = '';
                $status = 1;
            } else $errors[] = "Lỗi khi thêm: ".$conn->error;
            $stmt->close();
        } else $errors[] = "Lỗi hệ thống (prepare insert).";
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/add_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Thêm Quận/Huyện</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif(!empty($success_message)): ?>
        <div class="message">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div class="add-form">
        <form method="POST" novalidate>
            <div class="form-group">
                <label>Tỉnh/Thành phố *</label>
                <select name="province_id">
                    <option value="">-- Chọn tỉnh/thành phố --</option>
                    <?php foreach($provinces as $prov): ?>
                        <option value="<?php echo $prov['province_id']; ?>" <?php if($prov['province_id']==$province_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($prov['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tên quận/huyện *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1" <?php if($status==1) echo 'selected'; ?>>Mở</option>
                    <option value="0" <?php if($status==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-add">Thêm</button>
                <button type="reset" class="btn-reset">Xóa form</button>
                <button type="button" class="btn-back" onclick="location.href='list_district.php'">Quay về danh sách</button>
            </div>
        </form>
    </div>
</div>
