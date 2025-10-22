<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$package_id = intval($_GET['id'] ?? 0);
if (!$package_id) {
    echo "<p>Không có gói nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu gói
$stmt = $conn->prepare("SELECT * FROM Promotion_Packages WHERE package_id=?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration_days'] ?? 0);
    $feature_video_allowed = isset($_POST['feature_video_allowed']) ? 1 : 0;
    $is_priority_display = isset($_POST['is_priority_display']) ? 1 : 0;
    $status = intval($_POST['status'] ?? 1);

    if (!$name) $errors[] = "Tên gói không được để trống.";
    if ($price <= 0) $errors[] = "Giá phải lớn hơn 0.";
    if ($duration <= 0) $errors[] = "Thời gian phải lớn hơn 0 ngày.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Promotion_Packages SET name=?, price=?, duration_days=?, feature_video_allowed=?, is_priority_display=?, status=? WHERE package_id=?");
        $stmt->bind_param("sdiiiii", $name, $price, $duration, $feature_video_allowed, $is_priority_display, $status, $package_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật gói thành công!";
            $package = array_merge($package, [
                'name'=>$name,
                'price'=>$price,
                'duration_days'=>$duration,
                'feature_video_allowed'=>$feature_video_allowed,
                'is_priority_display'=>$is_priority_display,
                'status'=>$status
            ]);
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
    <div class="page-title"><h2>Chi tiết Gói</h2></div>

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
                <span class="label">Tên gói:</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($package['name']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Giá:</span>
                <input type="number" name="price" step="0.01" value="<?php echo $package['price']; ?>">
            </div>
            <div class="detail-item">
                <span class="label">Thời gian (ngày):</span>
                <input type="number" name="duration_days" value="<?php echo $package['duration_days']; ?>">
            </div>
            <div class="detail-item">
                <span class="label">Cho phép video nổi bật:</span>
                <input type="checkbox" name="feature_video_allowed" <?php if($package['feature_video_allowed']) echo 'checked'; ?>>
            </div>
            <div class="detail-item">
                <span class="label">Ưu tiên hiển thị:</span>
                <input type="checkbox" name="is_priority_display" <?php if($package['is_priority_display']) echo 'checked'; ?>>
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($package['status']==1) echo 'selected'; ?>>Hoạt động</option>
                    <option value="0" <?php if($package['status']==0) echo 'selected'; ?>>Ẩn</option>
                </select>
            </div>
        </div>

        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_package.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
