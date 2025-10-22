<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$subscription_id = intval($_GET['id'] ?? 0);
if (!$subscription_id) {
    echo "<p>Không có gói mua nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu gói mua, join với user và package
$stmt = $conn->prepare("
    SELECT us.*, u.fullname, u.email, u.avatar, p.name AS package_name, p.price, p.duration_days
    FROM User_Subscriptions us
    JOIN users u ON us.user_id=u.user_id
    JOIN Promotion_Packages p ON us.package_id=p.package_id
    WHERE us.subscription_id=?
");
$stmt->bind_param("i", $subscription_id);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

if (!$subscription) {
    echo "<p>Gói mua không tồn tại.</p>";
    exit;
}

$errors = [];
$success_message = '';

// Xử lý POST (chỉ cập nhật trạng thái và is_current)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
    $status = intval($_POST['status'] ?? 1);
    $is_current = intval($_POST['is_current'] ?? 1);

    $stmt = $conn->prepare("UPDATE User_Subscriptions SET status=?, is_current=? WHERE subscription_id=?");
    $stmt->bind_param("iii", $status, $is_current, $subscription_id);
    if ($stmt->execute()) {
        $success_message = "🎉 Cập nhật gói mua thành công!";
        $subscription['status'] = $status;
        $subscription['is_current'] = $is_current;
    } else {
        $errors[] = "Lỗi cập nhật: " . $conn->error;
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="../css/main.css">
<link rel="stylesheet" href="../css/detail_admin.css">

<div class="main-content">
    <div class="page-title"><h2>Chi tiết Gói Mua</h2></div>

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
                <span class="label">Người dùng:</span>
                <span class="value"><?php echo htmlspecialchars($subscription['fullname']); ?> (<?php echo htmlspecialchars($subscription['email']); ?>)</span>
            </div>
            <div class="detail-item">
                <span class="label">Gói:</span>
                <span class="value"><?php echo htmlspecialchars($subscription['package_name']); ?> - <?php echo number_format($subscription['price'],2); ?>₫ / <?php echo $subscription['duration_days']; ?> ngày</span>
            </div>
            <div class="detail-item">
                <span class="label">Transaction ID:</span>
                <span class="value"><?php echo $subscription['transaction_id']; ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Thời gian bắt đầu:</span>
                <span class="value"><?php echo $subscription['start_time']; ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Thời gian kết thúc:</span>
                <span class="value"><?php echo $subscription['end_time']; ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($subscription['status']==1) echo 'selected'; ?>>Hiệu lực</option>
                    <option value="0" <?php if($subscription['status']==0) echo 'selected'; ?>>Hết hạn</option>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Gói hiện tại:</span>
                <select name="is_current">
                    <option value="1" <?php if($subscription['is_current']==1) echo 'selected'; ?>>Có</option>
                    <option value="0" <?php if($subscription['is_current']==0) echo 'selected'; ?>>Không</option>
                </select>
            </div>
        </div>

        <div class="detail-right">
            <div class="detail-item">
                <span class="label">Avatar người dùng:</span>
                <img src="../<?php echo $subscription['avatar'] ?: 'upload/user/default.png'; ?>" alt="avatar" style="max-width:200px;">
            </div>
        </div>

        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_usersubscription.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
