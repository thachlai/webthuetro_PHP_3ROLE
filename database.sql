-- ********** BẢNG CƠ BẢN **********

-- 1. Bảng users / người dùng (ĐÃ SỬA LỖI CÚ PHÁP VÀ TỐI ƯU HÓA)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY, 
    fullname VARCHAR(255) NOT NULL, 
    email VARCHAR(100) UNIQUE NOT NULL, -- Đã sửa lỗi cú pháp ở đây
    password VARCHAR(255) NOT NULL, 
    phone VARCHAR(15), 
    address VARCHAR(255),
    gender ENUM('male', 'female', 'other') DEFAULT 'other', -- Tối ưu: Dùng ENUM thay vì VARCHAR
    avatar VARCHAR(255) DEFAULT 'upload/user/default.png', 
    birthday DATE, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role TINYINT DEFAULT 1, -- 0=admin, 1=người dùng (tìm trọ), 2=chủ trọ
    status TINYINT DEFAULT 1 -- 0=khóa, 1=mở
);

-- 2. Bảng Categories / danh mục
CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL, 
    status TINYINT(1) DEFAULT 1
);

-- 3. Bảng Provinces / Tỉnh 
CREATE TABLE Provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status TINYINT(1) DEFAULT 1 
);

-- 4. Bảng Districts / Huyện
CREATE TABLE Districts (
    district_id INT AUTO_INCREMENT PRIMARY KEY,
    province_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    FOREIGN KEY (province_id) REFERENCES Provinces(province_id) ON DELETE CASCADE
);

-- 5. Bảng Wards / Xã phường
CREATE TABLE Wards (
    ward_id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES Districts(district_id) ON DELETE CASCADE
);

-- ********** BẢNG GÓI VIP VÀ THANH TOÁN **********

-- 6. Bảng Promotion_Packages / gói (ĐÃ THÊM is_priority_display)
CREATE TABLE Promotion_Packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL, 
    duration_days INT NOT NULL,
    feature_video_allowed TINYINT(1) DEFAULT 0,
    is_priority_display TINYINT(1) DEFAULT 0, -- Tối ưu: Quyền ưu tiên hiển thị
    status TINYINT(1) DEFAULT 1 comment "măc đinh là 1 mở , 0 là ẩn"
);

-- 7. Bảng Transactions / xử lý thanh toán
CREATE TABLE Transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(255) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    package_id INT NOT NULL,
    trans_id VARCHAR(255),
    transaction_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pay_time DATETIME,
    error_code VARCHAR(10),
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES Promotion_Packages(package_id)
);

-- 8. Bảng User_Subscriptions / người dùng đăng ký gói (ĐÃ THÊM is_current)
CREATE TABLE User_Subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNIQUE NOT NULL,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status TINYINT(1) DEFAULT 1 comment "măc đinh là 1 hiệu lực , 0 là hết hạn",
    is_current TINYINT(1) DEFAULT 1, -- Tối ưu: Đánh dấu gói đang có hiệu lực HIỆN TẠI
    
    FOREIGN KEY (transaction_id) REFERENCES Transactions(transaction_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES Promotion_Packages(package_id)
);

-- ********** BẢNG TIN ĐĂNG (POSTS) ĐÃ CẬP NHẬT **********

-- 9. Bảng posts (ĐÃ SỬA LỖI CÚ PHÁP, THÊM GIÁ, CỌC VÀ ƯU TIÊN)
CREATE TABLE posts (
    id_post INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    category_id INT, 
    
    province_id INT,
    district_id INT,
    ward_id INT,
    
    title VARCHAR(255) NOT NULL, 
    description TEXT, 
    price DECIMAL(10, 2) NOT NULL, -- Bổ sung: Giá thuê (Bắt buộc)
    deposit DECIMAL(10, 2), -- Bổ sung: Tiền cọc
    area DECIMAL(6,2), -- Đã sửa lỗi cú pháp kiểu dữ liệu
    detailed_address VARCHAR(255) NOT NULL, -- Tối ưu: Địa chỉ chi tiết (Bắt buộc)
    is_priority_post TINYINT(1) DEFAULT 0, -- Tối ưu: Bài đăng có được ưu tiên không
    status ENUM('pending', 'active', 'rented', 'expired') DEFAULT 'pending', -- Tối ưu: Dùng ENUM cho trạng thái rõ ràng
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (province_id) REFERENCES Provinces(province_id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES Districts(district_id) ON DELETE SET NULL,
    FOREIGN KEY (ward_id) REFERENCES Wards(ward_id) ON DELETE SET NULL
);

-- ********** BẢNG MEDIA VÀ BÌNH LUẬN **********

-- 10. Bảng image_post
CREATE TABLE image_post (
    id_image INT AUTO_INCREMENT PRIMARY KEY,
    id_post INT NOT NULL,
    link VARCHAR(255), 
    FOREIGN KEY (id_post) REFERENCES posts(id_post) ON DELETE CASCADE
);

-- 11. Bảng video_post
CREATE TABLE video_post (
    id_video INT AUTO_INCREMENT PRIMARY KEY,
    id_post INT NOT NULL,
    link VARCHAR(255),
    FOREIGN KEY (id_post) REFERENCES posts(id_post) ON DELETE CASCADE
);


-- 12. Bảng comments (Đã có parent_comment_id cho bình luận cây)
CREATE TABLE comments (
    id_comment INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    id_post INT NOT NULL,
    
    parent_comment_id INT DEFAULT NULL, -- Liên kết đến bình luận cha
    
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (id_post) REFERENCES posts(id_post) ON DELETE CASCADE,
    
    -- Tự tham chiếu
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id_comment) ON DELETE CASCADE
);

