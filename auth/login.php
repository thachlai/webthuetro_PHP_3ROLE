<?php
// auth/login.php - Đăng nhập (MD5 + toggle mật khẩu)

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
$email = '';
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Email và Mật khẩu là bắt buộc.";
    } else {
        $password_md5 = md5($password);
        $sql = "SELECT user_id, fullname, role, avatar FROM users WHERE email = ? AND password = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $password_md5);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $user_id, $fullname, $role, $avatar);
                mysqli_stmt_fetch($stmt);

                // Lưu session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['role'] = $role;
                $_SESSION['avatar'] = $avatar;

                // Chuyển hướng theo vai trò
                if ($role === 0) {
                    header("Location: ../admin/index.php");
                } elseif ($role === 1) {
                    header("Location: ../user/index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit; // quan trọng: dừng script ngay sau header
            } else {
                $errors[] = "Email hoặc mật khẩu không đúng.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Lỗi hệ thống (prepare).";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
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
            <h2>Đăng Nhập</h2>

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

            <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group password-toggle">
                    <label for="password">Mật khẩu *</label>
                    <input type="password" id="password" name="password" required>
                    <span class="toggle-icon" onclick="togglePassword('password', this)">👁️</span>
                </div>

                <button type="submit" class="auth-submit-btn">Đăng Nhập</button>
            </form>

            <div class="auth-links">
                Chưa có tài khoản? <a href="signup.php">Đăng ký ngay</a>
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
