<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';

// Chỉ cho người dùng đã đăng nhập
check_login(); 

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p>Vui lòng đăng nhập để thay đổi mật khẩu.</p>";
    exit;
}

// Lấy dữ liệu user
$stmt = $conn->prepare("SELECT fullname, password FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success_message = '';

// Xử lý form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $errors[] = "Vui lòng điền đầy đủ các trường.";
    } elseif (md5($current_password) !== $user['password']) {
        $errors[] = "Mật khẩu hiện tại không đúng.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Mật khẩu mới và xác nhận không khớp.";
    } else {
        // Cập nhật mật khẩu mới
        $new_password_md5 = md5($new_password);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $new_password_md5, $user_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Mật khẩu đã được cập nhật thành công!";
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
    <div class="page-title"><h2>Thay đổi mật khẩu</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message" style="background-color:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="detail-wrapper" style="max-width:500px;">
        <div class="detail-item">
            <span class="label">Mật khẩu hiện tại:</span>
            <input type="password" name="current_password" required>
        </div>
        <div class="detail-item">
            <span class="label">Mật khẩu mới:</span>
            <input type="password" name="new_password" required>
        </div>
        <div class="detail-item">
            <span class="label">Xác nhận mật khẩu mới:</span>
            <input type="password" name="confirm_password" required>
        </div>

        <div class="detail-actions">
            <button type="submit" class="btn-edit">Cập nhật mật khẩu</button>
            <button type="button" class="btn-back" onclick="location.href='edit_profile.php'">Quay lại hồ sơ</button>
        </div>
    </form>
</div>
