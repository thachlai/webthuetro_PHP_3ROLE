<?php
// auth/signup.php - Trang Đăng ký người dùng

// Cần lùi 1 cấp để đến include/ và css/
$base_path = '../'; 

// 1. Nhúng file kết nối CSDL và khởi động session
include $base_path . 'include/conn.php'; 

// --- KHỞI TẠO BIẾN DỮ LIỆU CẦN THIẾT CHO HEADER/SIDEBAR ---
// Do đây là trang đăng ký (chưa đăng nhập), $user sẽ là NULL.
$user = null; 
// Lấy danh mục cho Header (giả định có bảng Categories)
try {
    $stmt = $conn->query("SELECT category_id, name FROM Categories WHERE status = 1");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Nếu bảng Categories chưa tồn tại hoặc lỗi, đặt mảng rỗng
    $categories = []; 
}
// -----------------------------------------------------------

// Khởi tạo biến để giữ lại dữ liệu form (nếu có lỗi)
$fullname = $email = $phone = $address = $birthday = '';
$role = 1; 
$gender = 'other'; 

$errors = [];
$success_message = '';

// --- XỬ LÝ LOGIC FORM KHI NGƯỜI DÙNG NHẤN NÚT ĐĂNG KÝ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ form 
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthday = trim($_POST['birthday'] ?? ''); 
    $gender = $_POST['gender'] ?? 'other'; 
    $role = $_POST['role'] ?? 1; 

    // 2. Kiểm tra lỗi (Validation cơ bản SERVER-SIDE)
    if (empty($fullname) || empty($email) || empty($password)) {
        $errors[] = "Các trường Họ tên, Email, Mật khẩu là bắt buộc.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Địa chỉ email không hợp lệ.";
    if ($password !== $confirm_password) $errors[] = "Xác nhận mật khẩu không khớp.";
    if (!in_array($role, [1, 2])) $errors[] = "Vai trò không hợp lệ.";
    if (!in_array($gender, ['male', 'female', 'other'])) $errors[] = "Giới tính không hợp lệ.";
    if (!empty($birthday) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday)) {
        $errors[] = "Ngày sinh không đúng định dạng YYYY-MM-DD.";
    }
    // Ghi chú: Ràng buộc mật khẩu mạnh đã được kiểm tra ở CLIENT-SIDE bằng thuộc tính 'pattern'

    // 3. Kiểm tra Email đã tồn tại chưa
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = "Email này đã được đăng ký.";
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi CSDL: " . $e->getMessage();
        }
    }

    // 4. Nếu không có lỗi, tiến hành Đăng ký
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
        
        // CÁC TRƯỜNG ĐƯỢC ĐIỀN BẰNG GIÁ TRỊ MẶC ĐỊNH HOẶC LOGIC:
        $default_avatar = 'uploads/avatar/default.png'; // ⬅️ Trường AVATAR
        // $status = 1; // ⬅️ Trường STATUS (Có thể bỏ qua vì CSDL có DEFAULT 1)
        // created_at sẽ tự động điền (DEFAULT CURRENT_TIMESTAMP)
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO users (fullname, email, password, phone, address, gender, birthday, role, avatar)
                VALUES (:fullname, :email, :password, :phone, :address, :gender, :birthday, :role, :avatar)
            ");
            
            $stmt->bindParam(':fullname', $fullname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            
            // Các trường có thể NULL
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':gender', $gender);
            
            if (empty($birthday)) {
                $stmt->bindValue(':birthday', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':birthday', $birthday);
            }

            $stmt->bindParam(':role', $role, PDO::PARAM_INT);
            $stmt->bindParam(':avatar', $default_avatar); // ⬅️ Gán giá trị AVATAR mặc định

            if ($stmt->execute()) {
                $success_message = "Đăng ký thành công! Bạn có thể <a href='login.php'>Đăng nhập</a> ngay bây giờ.";
                $fullname = $email = $phone = $address = $birthday = '';
                $role = 1; $gender = 'other';
            } else {
                $errors[] = "Đăng ký thất bại, vui lòng thử lại.";
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản</title>
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/main.css"> 
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/auth.css"> 
</head>
<body class="auth-page"> 

    <?php include '../include/header.php'; ?>
    <?php include '../include/sidebar.php'; ?> 

    <div class="auth-box">
        <h2>Đăng Ký Tài Khoản Mới</h2>

        <?php if (!empty($errors)): ?>
            <div class="notification-box notification-error">
                <?php foreach ($errors as $error): ?>
                    <p>⚠️ <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success_message): ?>
            <div class="notification-box notification-success">
                <p>✅ <?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            
            <div class="form-group">
                <label for="fullname">Họ và Tên (*)</label>
                <input type="text" id="fullname" name="fullname" required value="<?php echo htmlspecialchars($fullname); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email (*)</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu (*)</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])(?!.*\s).{6,}$"
                    title="Mật khẩu phải: 1) Dài ít nhất 6 ký tự. 2) Có ít nhất 1 chữ thường, 1 chữ hoa, 1 số, 1 ký tự đặc biệt (!@#$%^&*...). 3) Không có dấu cách."
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận Mật khẩu (*)</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
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
                <label>Giới tính:</label>
                <label style="display: inline-block; font-weight: normal; margin-right: 15px;">
                    <input type="radio" name="gender" value="male" <?php echo ($gender == 'male') ? 'checked' : ''; ?>> Nam
                </label>
                <label style="display: inline-block; font-weight: normal; margin-right: 15px;">
                    <input type="radio" name="gender" value="female" <?php echo ($gender == 'female') ? 'checked' : ''; ?>> Nữ
                </label>
                <label style="display: inline-block; font-weight: normal;">
                    <input type="radio" name="gender" value="other" <?php echo ($gender == 'other') ? 'checked' : ''; ?>> Khác
                </label>
            </div>

            <div class="form-group">
                <label>Đăng ký với vai trò (*):</label>
                <div>
                    <label style="display: inline-block; font-weight: normal; margin-right: 15px;">
                        <input type="radio" name="role" value="1" <?php echo ($role == 1) ? 'checked' : ''; ?> required> Người dùng (Tìm trọ)
                    </label>
                    <label style="display: inline-block; font-weight: normal;">
                        <input type="radio" name="role" value="2" <?php echo ($role == 2) ? 'checked' : ''; ?> required> Chủ trọ (Đăng trọ)
                    </label>
                </div>
            </div>

            <button type="submit" class="auth-submit-btn">ĐĂNG KÝ</button>
        </form>

        <div class="auth-links">
            Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>
        </div>
    </div>
    
    </body>
</html>