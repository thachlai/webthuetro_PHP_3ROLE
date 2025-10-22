INSERT INTO users (fullname, email, password, phone, address, gender, birthday, role, status)
VALUES
('Người dùng 1', 'user1@mail.com', MD5('123456Aa!'), '0900000001', 'Địa chỉ 1', 'male', '1990-01-01', 1, 1),
('Người dùng 2', 'user2@mail.com', MD5('123456Aa!'), '0900000002', 'Địa chỉ 2', 'female', '1990-02-01', 1, 1),
('Người dùng 3', 'user3@mail.com', MD5('123456Aa!'), '0900000003', 'Địa chỉ 3', 'other', '1990-03-01', 1, 1),
('Người dùng 4', 'user4@mail.com', MD5('123456Aa!'), '0900000004', 'Địa chỉ 4', 'male', '1990-04-01', 1, 1),
('Người dùng 5', 'user5@mail.com', MD5('123456Aa!'), '0900000005', 'Địa chỉ 5', 'female', '1990-05-01', 1, 1),
('Người dùng 6', 'user6@mail.com', MD5('123456Aa!'), '0900000006', 'Địa chỉ 6', 'other', '1990-06-01', 1, 1),
('Người dùng 7', 'user7@mail.com', MD5('123456Aa!'), '0900000007', 'Địa chỉ 7', 'male', '1990-07-01', 1, 1),
('Người dùng 8', 'user8@mail.com', MD5('123456Aa!'), '0900000008', 'Địa chỉ 8', 'female', '1990-08-01', 1, 1),
('Người dùng 9', 'user9@mail.com', MD5('123456Aa!'), '0900000009', 'Địa chỉ 9', 'other', '1990-09-01', 1, 1),
('Người dùng 10', 'user10@mail.com', MD5('123456Aa!'), '0900000010', 'Địa chỉ 10', 'male', '1990-10-01', 1, 1),
('Người dùng 11', 'user11@mail.com', MD5('123456Aa!'), '0900000011', 'Địa chỉ 11', 'female', '1990-11-01', 1, 1),
('Người dùng 12', 'user12@mail.com', MD5('123456Aa!'), '0900000012', 'Địa chỉ 12', 'other', '1990-12-01', 1, 1),
('Người dùng 13', 'user13@mail.com', MD5('123456Aa!'), '0900000013', 'Địa chỉ 13', 'male', '1991-01-01', 1, 1),
('Người dùng 14', 'user14@mail.com', MD5('123456Aa!'), '0900000014', 'Địa chỉ 14', 'female', '1991-02-01', 1, 1),
('Người dùng 15', 'user15@mail.com', MD5('123456Aa!'), '0900000015', 'Địa chỉ 15', 'other', '1991-03-01', 1, 1),
('Người dùng 16', 'user16@mail.com', MD5('123456Aa!'), '0900000016', 'Địa chỉ 16', 'male', '1991-04-01', 1, 1),
('Người dùng 17', 'user17@mail.com', MD5('123456Aa!'), '0900000017', 'Địa chỉ 17', 'female', '1991-05-01', 1, 1),
('Người dùng 18', 'user18@mail.com', MD5('123456Aa!'), '0900000018', 'Địa chỉ 18', 'other', '1991-06-01', 1, 1),
('Người dùng 19', 'user19@mail.com', MD5('123456Aa!'), '0900000019', 'Địa chỉ 19', 'male', '1991-07-01', 1, 1),
('Người dùng 20', 'user20@mail.com', MD5('123456Aa!'), '0900000020', 'Địa chỉ 20', 'female', '1991-08-01', 1, 1);
INSERT INTO Categories (name, status) VALUES
('Phòng trọ sinh viên', 1),
('Phòng trọ cao cấp', 1),
('Nhà nguyên căn', 1),
('Homestay', 1),
('Chung cư mini', 1),
('Kí túc xá', 1),
('Phòng trọ giá rẻ', 1),
('Studio', 1),
('Phòng trọ có nội thất', 1),
('Phòng trọ không nội thất', 1);


INSERT INTO Provinces (name, status) VALUES
('Hà Nội', 1),
('Hồ Chí Minh', 1),
('Hải Phòng', 1),
('Đà Nẵng', 1),
('Cần Thơ', 1),
('An Giang', 1),
('Bà Rịa - Vũng Tàu', 1),
('Bắc Giang', 1),
('Bắc Kạn', 1),
('Bạc Liêu', 1),
('Bắc Ninh', 1),
('Bến Tre', 1),
('Bình Định', 1),
('Bình Dương', 1),
('Bình Phước', 1),
('Bình Thuận', 1),
('Cà Mau', 1),
('Cao Bằng', 1),
('Đắk Lắk', 1),
('Đắk Nông', 1),
('Điện Biên', 1),
('Đồng Nai', 1),
('Đồng Tháp', 1),
('Gia Lai', 1),
('Hà Giang', 1),
('Hà Nam', 1),
('Hà Tĩnh', 1),
('Hải Dương', 1),
('Hậu Giang', 1),
('Hòa Bình', 1),
('Hưng Yên', 1),
('Khánh Hòa', 1),
('Kiên Giang', 1),
('Kon Tum', 1),
('Lai Châu', 1),
('Lâm Đồng', 1),
('Lạng Sơn', 1),
('Lào Cai', 1),
('Long An', 1),
('Nam Định', 1),
('Nghệ An', 1),
('Ninh Bình', 1),
('Ninh Thuận', 1),
('Phú Thọ', 1),
('Quảng Bình', 1),
('Quảng Nam', 1),
('Quảng Ngãi', 1),
('Quảng Ninh', 1),
('Quảng Trị', 1),
('Sóc Trăng', 1),
('Sơn La', 1),
('Tây Ninh', 1),
('Thái Bình', 1),
('Thái Nguyên', 1),
('Thanh Hóa', 1),
('Thừa Thiên Huế', 1),
('Tiền Giang', 1),
('Trà Vinh', 1),
('Tuyên Quang', 1),
('Vĩnh Long', 1),
('Vĩnh Phúc', 1),
('Yên Bái', 1);

----------------------------------- cac huyen
-- Hà Nội (province_id = 1)
INSERT INTO Districts (province_id, name, status) VALUES
(1, 'Ba Đình', 1),
(1, 'Hoàn Kiếm', 1),
(1, 'Tây Hồ', 1),
(1, 'Long Biên', 1),
(1, 'Cầu Giấy', 1),
(1, 'Đống Đa', 1),
(1, 'Hai Bà Trưng', 1),
(1, 'Hoàng Mai', 1),
(1, 'Thanh Xuân', 1);

-- Hồ Chí Minh (province_id = 2)
INSERT INTO Districts (province_id, name, status) VALUES
(2, 'Quận 1', 1),
(2, 'Quận 2', 1),
(2, 'Quận 3', 1),
(2, 'Quận 4', 1),
(2, 'Quận 5', 1),
(2, 'Quận 6', 1),
(2, 'Quận 7', 1),
(2, 'Quận 8', 1),
(2, 'Quận 9', 1),
(2, 'Quận 10', 1),
(2, 'Quận 11', 1),
(2, 'Quận 12', 1),
(2, 'Bình Thạnh', 1),
(2, 'Gò Vấp', 1),
(2, 'Tân Bình', 1),
(2, 'Tân Phú', 1),
(2, 'Phú Nhuận', 1),
(2, 'Thủ Đức', 1);

-- Hải Phòng (province_id = 3)
INSERT INTO Districts (province_id, name, status) VALUES
(3, 'Hồng Bàng', 1),
(3, 'Ngô Quyền', 1),
(3, 'Lê Chân', 1),
(3, 'Kiến An', 1),
(3, 'Đồ Sơn', 1),
(3, 'Hải An', 1),
(3, 'An Dương', 1),
(3, 'An Lão', 1),
(3, 'Kiến Thụy', 1);

-- Đà Nẵng (province_id = 4)
INSERT INTO Districts (province_id, name, status) VALUES
(4, 'Hải Châu', 1),
(4, 'Thanh Khê', 1),
(4, 'Sơn Trà', 1),
(4, 'Ngũ Hành Sơn', 1),
(4, 'Liên Chiểu', 1),
(4, 'Cẩm Lệ', 1),
(4, 'Hòa Vang', 1);

-- Cần Thơ (province_id = 5)
INSERT INTO Districts (province_id, name, status) VALUES
(5, 'Ninh Kiều', 1),
(5, 'Bình Thủy', 1),
(5, 'Cái Răng', 1),
(5, 'Ô Môn', 1),
(5, 'Thốt Nốt', 1);

-- An Giang (province_id = 6)
INSERT INTO Districts (province_id, name, status) VALUES
(6, 'Châu Đốc', 1),
(6, 'Long Xuyên', 1),
(6, 'An Phú', 1),
(6, 'Tân Châu', 1),
(6, 'Châu Phú', 1),
(6, 'Chợ Mới', 1),
(6, 'Thoại Sơn', 1),
(6, 'Châu Thành', 1),
(6, 'An Giang', 1);

-- Bà Rịa - Vũng Tàu (province_id = 7)
INSERT INTO Districts (province_id, name, status) VALUES
(7, 'Bà Rịa', 1),
(7, 'Vũng Tàu', 1),
(7, 'Đất Đỏ', 1),
(7, 'Long Điền', 1),
(7, 'Xuyên Mộc', 1),
(7, 'Châu Đức', 1),
(7, 'Côn Đảo', 1);


----nhap lieu xa cua hanoi
-- Lấy ID Hà Nội
-- Giả sử Hà Nội có province_id = 1

-- Ví dụ các huyện/quận Hà Nội
INSERT INTO Districts (province_id, name, status) VALUES
(1, 'Ba Đình', 1),
(1, 'Hoàn Kiếm', 1),
(1, 'Tây Hồ', 1),
(1, 'Long Biên', 1),
(1, 'Cầu Giấy', 1),
(1, 'Đống Đa', 1),
(1, 'Hai Bà Trưng', 1),
(1, 'Hoàng Mai', 1),
(1, 'Thanh Xuân', 1),
(1, 'Hà Đông', 1),
(1, 'Sóc Sơn', 1),
(1, 'Đông Anh', 1),
(1, 'Gia Lâm', 1),
(1, 'Mê Linh', 1),
(1, 'Thanh Trì', 1),
(1, 'Ba Vì', 1),
(1, 'Chương Mỹ', 1),
(1, 'Đan Phượng', 1),
(1, 'Đông Anh', 1),
(1, 'Hoài Đức', 1),
(1, 'Mỹ Đức', 1),
(1, 'Phú Xuyên', 1),
(1, 'Phúc Thọ', 1),
(1, 'Quốc Oai', 1),
(1, 'Sóc Sơn', 1),
(1, 'Thạch Thất', 1),
(1, 'Thanh Oai', 1),
(1, 'Thường Tín', 1),
(1, 'Ứng Hòa', 1),
(1, 'Sơn Tây', 1);

-- Ví dụ xã/phường của quận Ba Đình
INSERT INTO Wards (district_id, name, status) VALUES
(1, 'Phúc Xá', 1),
(1, 'Trúc Bạch', 1),
(1, 'Vĩnh Phúc', 1),
(1, 'Cống Vị', 1),
(1, 'Ngọc Hà', 1),
(1, 'Điện Biên', 1),
(1, 'Đội Cấn', 1),
(1, 'Nguyễn Trung Trực', 1),
(1, 'Quán Thánh', 1),
(1, 'Giảng Võ', 1);

-- Ví dụ xã/phường quận Hoàn Kiếm
INSERT INTO Wards (district_id, name, status) VALUES
(2, 'Phúc Tân', 1),
(2, 'Hàng Bạc', 1),
(2, 'Hàng Buồm', 1),
(2, 'Hàng Bài', 1),
(2, 'Cửa Đông', 1),
(2, 'Hàng Trống', 1),
(2, 'Cửa Nam', 1),
(2, 'Hàng Đào', 1),
(2, 'Hàng Gai', 1),
(2, 'Lý Thái Tổ', 1);



INSERT INTO Promotion_Packages (name, price, duration_days, feature_video_allowed, is_priority_display, status) VALUES
('Gói Thường 1', 100000.00, 7, 0, 0, 1),
('Gói Thường 2', 150000.00, 15, 0, 1, 1),
('Gói VIP 1', 300000.00, 30, 1, 1, 1),
('Gói VIP 2', 500000.00, 60, 1, 1, 1),
('Gói Khuyến Mãi', 50000.00, 3, 0, 0, 0),
('Gói Thử Nghiệm', 0.00, 1, 0, 0, 1),
('Gói Đặc Biệt', 1000000.00, 90, 1, 1, 1);
