<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/header.php';
include __DIR__ . '/../include/function.php';
check_login(); // hàm kiểm tra user đã login chưa

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "<p>Bạn chưa đăng nhập.</p>";
    exit;
}

// Lấy dữ liệu tài khoản
$stmt = $conn->prepare("SELECT fullname, email, phone, address, gender, birthday, avatar FROM users WHERE user_id=?");
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success_message = '';

// Xử lý POST (update thông tin + upload avatar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $birthday = $_POST['birthday'] ?? '';

    if (!$fullname) $errors[] = "Tên không được để trống.";

    // Xử lý upload avatar nếu có
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Chỉ cho phép upload ảnh JPG, PNG, GIF.";
        } else {
            $avatar_name = 'upload/avatar/' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../' . $avatar_name)) {
                $errors[] = "Không thể upload ảnh.";
            } else {
                // Xóa ảnh cũ nếu khác default
                if ($user['avatar'] && $user['avatar'] !== 'upload/avatar/default.png' && file_exists(__DIR__ . '/../' . $user['avatar'])) {
                    unlink(__DIR__ . '/../' . $user['avatar']);
                }
                $user['avatar'] = $avatar_name; // cập nhật avatar mới
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, address=?, gender=?, birthday=?, avatar=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $fullname, $phone, $address, $gender, $birthday, $user['avatar'], $user_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật thông tin thành công!";
            // Cập nhật $user để hiển thị lại
            $user = array_merge($user, [
                'fullname'=>$fullname,
                'phone'=>$phone,
                'address'=>$address,
                'gender'=>$gender,
                'birthday'=>$birthday
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
    <div class="page-title"><h2>Hồ sơ của tôi</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message error">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="detail-wrapper">
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Họ và tên:</span>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Email:</span>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div class="detail-item">
                <span class="label">SĐT:</span>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Địa chỉ:</span>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Giới tính:</span>
                <select name="gender">
                    <option value="male" <?php if($user['gender']=='male') echo 'selected'; ?>>Nam</option>
                    <option value="female" <?php if($user['gender']=='female') echo 'selected'; ?>>Nữ</option>
                    <option value="other" <?php if($user['gender']=='other') echo 'selected'; ?>>Khác</option>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Ngày sinh:</span>
                <input type="date" name="birthday" value="<?php echo $user['birthday']; ?>">
            </div>
            <div class="detail-item">
                <span class="label">Avatar:</span>
                <input type="file" name="avatar">
            </div>
        </div>

        <div class="detail-right">
            <div class="detail-item">
                <span class="label">Ảnh hiện tại:</span>
                <img src="../<?php echo $user['avatar'] ?: 'upload/avatar/default.png'; ?>" alt="avatar" style="max-width:200px; border-radius:8px;">
            </div>
        </div>
        
        <div>
        <div class="detail-actions">
            <button type="submit" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-password" onclick="location.href='edit_password.php'">Cập nhật mật khẩu</button>
        </div>
        </div>
    </form>
</div>
