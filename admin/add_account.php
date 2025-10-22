<?php
session_start();
include __DIR__ . '/../include/conn.php';
include __DIR__ . '/../include/admin_header.php';
include __DIR__ . '/../include/admin_sidebar.php';

$fullname = $email = $password = $phone = $address = $birthday = '';
$gender = 'other';
$role = 1;
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $role = intval($_POST['role'] ?? 1);

    // Validate cơ bản
    if (!$fullname || !$email || !$password) $errors[] = "Họ tên, Email, Mật khẩu là bắt buộc.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp.";
    if (!in_array($gender, ['male','female','other'])) $errors[] = "Giới tính không hợp lệ.";
    if (!in_array($role,[0,1,2])) $errors[] = "Vai trò không hợp lệ.";

    // Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows>0) $errors[] = "Email đã tồn tại.";
            $stmt->close();
        } else $errors[] = "Lỗi hệ thống (prepare check email).";
    }

    // Nếu không lỗi -> insert
    if (empty($errors)) {
        $password_md5 = md5($password);
        $stmt = $conn->prepare("INSERT INTO users (fullname,email,password,phone,address,gender,birthday,role) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param("sssssssi",$fullname,$email,$password_md5,$phone,$address,$gender,$birthday,$role);
            if ($stmt->execute()) {
                $success_message = "🎉 Thêm tài khoản thành công!";
                // Xóa dữ liệu form
                $fullname = $email = $password = $confirm_password = $phone = $address = $birthday = '';
                $gender = 'other'; $role = 1;
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
    <div class="page-title"><h2>Thêm Tài Khoản</h2></div>

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
                <label>Họ và tên *</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-group">
                <label>Mật khẩu *</label>
                <input type="password" name="password" value="">
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu *</label>
                <input type="password" name="confirm_password" value="">
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            </div>

            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">
            </div>

            <div class="form-group">
                <label>Ngày sinh</label>
                <input type="date" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>">
            </div>

            <div class="form-group">
                <label>Giới tính</label>
                <select name="gender">
                    <option value="male" <?php if($gender=='male') echo 'selected'; ?>>Nam</option>
                    <option value="female" <?php if($gender=='female') echo 'selected'; ?>>Nữ</option>
                    <option value="other" <?php if($gender=='other') echo 'selected'; ?>>Khác</option>
                </select>
            </div>

            <div class="form-group">
                <label>Vai trò</label>
                <select name="role">
                    <option value="1" <?php if($role==1) echo 'selected'; ?>>Người dùng</option>
                    <option value="2" <?php if($role==2) echo 'selected'; ?>>Chủ trọ</option>
                    <option value="0" <?php if($role==0) echo 'selected'; ?>>Admin</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-add">Thêm</button>
                <button type="reset" class="btn-reset">Xóa form</button>
                <button type="button" class="btn-back" onclick="location.href='list_account.php'">Quay về danh sách</button>
            </div>
        </form>
    </div>
</div>
tui 