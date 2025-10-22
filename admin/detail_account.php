<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';
include __DIR__ . '/../include/function.php';
check_admin();

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    echo "<p>Không có tài khoản nào được chọn.</p>";
    exit;
}

// Lấy dữ liệu tài khoản
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success_message = '';

// Xử lý POST (update thông tin + upload avatar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $birthday = $_POST['birthday'] ?? '';
    $role = intval($_POST['role'] ?? 1);
    $status = intval($_POST['status'] ?? 1);

    if (!$fullname) $errors[] = "Tên không được để trống.";
    if (!$email) $errors[] = "Email không được để trống.";

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
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, address=?, gender=?, birthday=?, role=?, status=?, avatar=? WHERE user_id=?");
        $stmt->bind_param("ssssssisii",$fullname,$email,$phone,$address,$gender,$birthday,$role,$status,$user['avatar'],$user_id);
        if ($stmt->execute()) {
            $success_message = "🎉 Cập nhật thông tin thành công!";
            // Cập nhật $user để hiển thị lại
            $user = array_merge($user, [
                'fullname'=>$fullname,
                'email'=>$email,
                'phone'=>$phone,
                'address'=>$address,
                'gender'=>$gender,
                'birthday'=>$birthday,
                'role'=>$role,
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
    <div class="page-title"><h2>Chi tiết Tài khoản</h2></div>

    <?php if(!empty($errors)): ?>
        <div class="message" style="background-color:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">
            <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
        </div>
    <?php elseif($success_message): ?>
        <div class="message" style="background-color:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="detail-wrapper">
        <!-- Bên trái: thông tin form -->
        <div class="detail-left">
            <div class="detail-item">
                <span class="label">Họ và tên:</span>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>">
            </div>
            <div class="detail-item">
                <span class="label">Email:</span>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
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
                <span class="label">Vai trò:</span>
                <select name="role">
                    <option value="0" <?php if($user['role']==0) echo 'selected'; ?>>Admin</option>
                    <option value="1" <?php if($user['role']==1) echo 'selected'; ?>>Người dùng</option>
                    <option value="2" <?php if($user['role']==2) echo 'selected'; ?>>Chủ trọ</option>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Trạng thái:</span>
                <select name="status">
                    <option value="1" <?php if($user['status']==1) echo 'selected'; ?>>Mở</option>
                    <option value="0" <?php if($user['status']==0) echo 'selected'; ?>>Khóa</option>
                </select>
            </div>
            <div class="detail-item">
                <span class="label">Avatar:</span>
                <input type="file" name="avatar">
            </div>
        </div>

        <!-- Bên phải: ảnh hiện tại -->
        <div class="detail-right">
            <div class="detail-item">
                <span class="label">Ảnh hiện tại:</span>
                <img src="../<?php echo $user['avatar'] ?: 'upload/avatar/default.png'; ?>" alt="avatar" style="max-width:200px; border-radius:8px;">
            </div>
        </div>

        <!-- Nút hành động cuối cùng -->
        <div class="detail-actions">
            <button type="submit" name="update" class="btn-edit">Cập nhật</button>
            <button type="button" class="btn-reload" onclick="location.reload()">Load lại</button>
            <button type="button" class="btn-back" onclick="location.href='list_account.php'">Quay về danh sách</button>
        </div>
    </form>
</div>
