-- 1. CHÈN GIAO DỊCH ID 2 (User 25 mua Package 9 - Đã xác định là SUCCESS)
INSERT INTO Transactions (transaction_id, user_id, order_id, amount, package_id, status) VALUES
(2, 25, 'ORDER-TEST-USER25-P9', 10000.00, 9, 'success');

-- 2. CHÈN GÓI ĐĂNG KÝ ID 2 (Gói VIP đang hoạt động)
-- Bắt đầu TỪ BÂY GIỜ và hết hạn sau 1 ngày (1 day)
INSERT INTO User_Subscriptions (subscription_id, transaction_id, user_id, package_id, start_time, end_time, status, is_current) VALUES
(2, 2, 25, 9, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 1);
