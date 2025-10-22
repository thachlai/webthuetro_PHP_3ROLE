<?php
// auth/signup.php - Đăng ký (MD5 + toggle mật khẩu)

include '../include/conn.php';
session_start();

// Lấy danh mục cho header (nếu cần)
$user = null;
$categories = [];
$category_sql = "SELECT category_id, name FROM categories WHERE status = 1";
$category_result = mysqli_query($conn, $category_sql);
if ($category_result && mysqli_num_rows($category_result) > 0) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row;
    }
}

// Biến form
$fullname = $email = $phone = $address = $birthday = '';
$role = 1;
$gender = 'other';
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
    if (empty($fullname) || empty($email) || empty($password)) {
        $errors[] = "Các trường Họ tên, Email, Mật khẩu là bắt buộc.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp.";
    if (!in_array($role, [1, 2, 0])) $errors[] = "Vai trò không hợp lệ.";
    if (!in_array($gender, ['male', 'female', 'other'])) $errors[] = "Giới tính không hợp lệ.";

    // Kiểm tra mật khẩu theo yêu cầu (ít nhất 6 ký tự, 1 hoa, 1 thường, 1 số, 1 ký tự đặc biệt, không chứa khoảng trắng)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[^\s]{6,}$/', $password)) {
        $errors[] = "Mật khẩu phải ít nhất 6 ký tự, gồm 1 chữ thường, 1 chữ hoa, 1 số, 1 ký tự đặc biệt và không chứa khoảng trắng.";
    }

    // Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "Email đã tồn tại.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Lỗi hệ thống (prepare check email).";
        }
    }

    // Xử lý upload avatar (nếu có)
    $avatar_path = 'upload/avatar/default.png'; // đường dẫn lưu vào DB (relative)
    if (empty($errors) && !empty($_FILES['avatar']['name'])) {
        $target_dir = '../upload/avatar/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['avatar']['name']));
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $valid_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $valid_types)) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                // lưu vào DB đường dẫn relative (không có ../)
                $avatar_path = 'upload/avatar/' . $file_name;
            } else {
                $errors[] = "Không thể tải lên ảnh đại diện.";
            }
        } else {
            $errors[] = "Chỉ chấp nhận định dạng ảnh JPG, JPEG, PNG, GIF.";
        }
    }

    // Nếu không có lỗi -> insert (MD5)
    if (empty($errors)) {
        $password_md5 = md5($password); // <- LƯU MD5 theo yêu cầu

        $sql = "INSERT INTO users (fullname, email, password, phone, address, gender, birthday, role, avatar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            // types: s s s s s s s i s  => "sssssssis"
            mysqli_stmt_bind_param($stmt, "sssssssis",
                $fullname,
                $email,
                $password_md5,
                $phone,
                $address,
                $gender,
                $birthday,
                $role,
                $avatar_path
            );

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "🎉 Đăng ký thành công! Bạn có thể <a href='login.php'>đăng nhập ngay</a>.";
                // reset form
                $fullname = $email = $phone = $address = $birthday = '';
                $role = 1; $gender = 'other';
            } else {
                $errors[] = "Lỗi khi đăng ký: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Lỗi hệ thống (prepare insert).";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng Ký</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
        /* nhỏ cho icon toggle mật khẩu */
        .password-toggle { position: relative; }
        .password-toggle .toggle-icon {
            position: absolute;
            right: 12px;
            top: 36px;
            cursor: pointer;
            user-select: none;
            font-size: 18px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?>

    <div class="main-content">
        <div class="auth-page">
            <div class="auth-box">
                <h2>Đăng Ký Tài Khoản</h2>

                <?php if (!empty($errors)): ?>
                    <div class="notification-box notification-error">
                        <?php foreach ($errors as $error): ?>
                            <p>⚠️ <?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($success_message)): ?>
                    <div class="notification-box notification-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="signup.php" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="fullname">Họ và Tên *</label>
                        <input type="text" id="fullname" name="fullname" required value="<?php echo htmlspecialchars($fullname); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>

                    <div class="form-group password-toggle">
                        <label for="password">Mật khẩu *</label>
                        <input type="password" id="password" name="password" required
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])(?!.*\s).{6,}$"
                               title="Ít nhất 6 ký tự, 1 chữ hoa, 1 chữ thường, 1 số, 1 ký tự đặc biệt, không chứa khoảng trắng.">
                        <span class="toggle-icon" onclick="togglePassword('password', this)">👁️</span>
                    </div>

                    <div class="form-group password-toggle">
                        <label for="confirm_password">Xác nhận mật khẩu *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <span class="toggle-icon" onclick="togglePassword('confirm_password', this)">👁️</span>
                    </div>

                    <div class="form-group">
                        <label for="avatar">Ảnh đại diện</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Địa chỉ</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>

                    <div class="form-group">
                        <label for="birthday">Ngày sinh</label>
                        <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>">
                    </div>

                    <div class="form-group">
                        <label>Giới tính:</label><br>
                        <label style="margin-right:12px;"><input type="radio" name="gender" value="male" <?php echo ($gender == 'male') ? 'checked' : ''; ?>> Nam</label>
                        <label style="margin-right:12px;"><input type="radio" name="gender" value="female" <?php echo ($gender == 'female') ? 'checked' : ''; ?>> Nữ</label>
                        <label><input type="radio" name="gender" value="other" <?php echo ($gender == 'other') ? 'checked' : ''; ?>> Khác</label>
                    </div>

                    <div class="form-group">
                        <label>Vai trò:</label><br>
                        <label style="margin-right:12px;"><input type="radio" name="role" value="1" <?php echo ($role == 1) ? 'checked' : ''; ?>> Người dùng (Tìm trọ)</label>
                        <label><input type="radio" name="role" value="2" <?php echo ($role == 2) ? 'checked' : ''; ?>> Chủ trọ (Đăng trọ)</label>
                    </div>

                    <button type="submit" class="auth-submit-btn">Đăng ký</button>
                </form>

                <div class="auth-links">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>

<script>
function togglePassword(id, el) {
    const input = document.getElementById(id);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        el.textContent = '🙈';
    } else {
        input.type = 'password';
        el.textContent = '👁️';
    }
}
</script>
</body>
</html>
