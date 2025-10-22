<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_login();
check_admin();
$name = '';
$price = '';
$duration_days = '';
$feature_video_allowed = 0;
$is_priority_display = 0;
$status = 1;

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $duration_days = trim($_POST['duration_days'] ?? '');
    $feature_video_allowed = isset($_POST['feature_video_allowed']) ? 1 : 0;
    $is_priority_display = isset($_POST['is_priority_display']) ? 1 : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    // Validate
    if (!$name) $errors[] = "Tên gói không được để trống.";
    if (!is_numeric($price) || $price <= 0) $errors[] = "Giá phải là số lớn hơn 0.";
    if (!ctype_digit($duration_days) || $duration_days <= 0) $errors[] = "Số ngày thuê phải là số nguyên dương.";

    // Kiểm tra trùng tên
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT package_id FROM Promotion_Packages WHERE name=?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Tên gói đã tồn tại.";
        $stmt->close();
    }

    // Thêm mới
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Promotion_Packages (name, price, duration_days, feature_video_allowed, is_priority_display, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiiii", $name, $price, $duration_days, $feature_video_allowed, $is_priority_display, $status);
        if ($stmt->execute()) {
            $success_message = "🎉 Thêm gói thành công!";
            // reset form
            $name = $price = $duration_days = '';
            $feature_video_allowed = $is_priority_display = 0;
            $status = 1;
        } else {
            $errors[] = "Lỗi khi thêm: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/add_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Thêm Gói Khuyến Mãi</h2></div>

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
                <label>Tên gói *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="form-group">
                <label>Giá (VNĐ) *</label>
                <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>">
            </div>

            <div class="form-group">
                <label>Số ngày thuê *</label>
                <input type="text" name="duration_days" value="<?php echo htmlspecialchars($duration_days); ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="feature_video_allowed" value="1" <?php if($feature_video_allowed) echo 'checked'; ?>> Cho phép video nổi bật
                </label>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_priority_display" value="1" <?php if($is_priority_display) echo 'checked'; ?>> Ưu tiên hiển thị
                </label>
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1" <?php if($status==1) echo 'selected'; ?>>Mở</option>
                    <option value="0" <?php if($status==0) echo 'selected'; ?>>Ẩn</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-add">Thêm</button>
                <button type="reset" class="btn-reset">Xóa form</button>
                <button type="button" class="btn-back" onclick="location.href='list_package.php'">Quay về danh sách</button>
            </div>
        </form>
    </div>
</div>
