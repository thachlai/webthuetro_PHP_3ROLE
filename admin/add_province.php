<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';

$name = '';
$status = 1;
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    if (!$name) $errors[] = "Tên tỉnh/thành phố không được để trống.";

    // Kiểm tra trùng tên
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT province_id FROM Provinces WHERE name=?");
        if ($stmt) {
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Tên tỉnh/thành phố đã tồn tại.";
            $stmt->close();
        } else {
            $errors[] = "Lỗi hệ thống (prepare check).";
        }
    }

    // Thêm mới
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Provinces (name,status) VALUES (?,?)");
        if ($stmt) {
            $stmt->bind_param("si",$name,$status);
            if ($stmt->execute()) {
                $success_message = "🎉 Thêm tỉnh/thành phố thành công!";
                $name = '';
                $status = 1;
            } else {
                $errors[] = "Lỗi khi thêm: ".$conn->error;
            }
            $stmt->close();
        } else $errors[] = "Lỗi hệ thống (prepare insert).";
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/add_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Thêm Tỉnh/Thành Phố</h2></div>

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
                <label>Tên tỉnh/thành phố *</label>
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
                <button type="button" class="btn-back" onclick="location.href='list_province.php'">Quay về danh sách</button>
            </div>
        </form>
    </div>
</div>
