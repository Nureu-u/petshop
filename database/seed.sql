-- Dữ liệu mẫu cho demo. Chạy SAU schema.sql và patch_id_sequences.sql.
-- Tài khoản admin tạo bằng:  php database/create_admin.php
USE petshop_db;

INSERT INTO pet_categories (id, name, slug, description) VALUES
('PCT000000001', 'Chó',  'cho',  'Các giống chó cảnh'),
('PCT000000002', 'Mèo',  'meo',  'Các giống mèo cảnh'),
('PCT000000003', 'Thú cưng nhỏ', 'thu-cung-nho', 'Hamster, thỏ, ...');

INSERT INTO pets (id, category_id, name, species, breed, gender, birth_date, weight, price, health_status, description, status) VALUES
('PET000000001', 'PCT000000001', 'Corgi Bơ',          'Chó',     'Corgi',             'male',   '2026-03-10', 3.20, 12000000, 'Đã tiêm phòng mũi 1', 'Bé Corgi năng động, thân thiện.', 'available'),
('PET000000002', 'PCT000000001', 'Poodle Nâu',        'Chó',     'Poodle',            'female', '2026-02-01', 2.50,  9500000, 'Khỏe mạnh',           'Poodle nâu lông xoăn, thông minh.', 'available'),
('PET000000003', 'PCT000000002', 'Mèo Anh lông ngắn', 'Mèo',     'British Shorthair', 'male',   '2026-01-15', 2.80,  8000000, 'Đã tẩy giun',         'Mặt tròn, tính cách hiền.', 'available'),
('PET000000004', 'PCT000000002', 'Mèo Ba Tư',         'Mèo',     'Persian',           'female', '2025-12-20', 2.60,  7000000, 'Khỏe mạnh',           'Lông dài mượt, cần chải thường xuyên.', 'available'),
('PET000000005', 'PCT000000003', 'Hamster Winter White', 'Hamster', 'Winter White',   'male',   '2026-05-01', 0.05,   150000, 'Khỏe mạnh',           'Nhỏ nhắn, dễ nuôi.', 'available');

INSERT INTO product_categories (id, name, slug, description) VALUES
('PDC000000001', 'Thức ăn', 'thuc-an', 'Thức ăn cho thú cưng'),
('PDC000000002', 'Đồ chơi', 'do-choi', 'Đồ chơi giải trí'),
('PDC000000003', 'Phụ kiện', 'phu-kien', 'Vòng cổ, lồng, giường, ...');

INSERT INTO products (id, category_id, name, brand, price, stock, description, status) VALUES
('PRD000000001', 'PDC000000001', 'Hạt khô cho chó trưởng thành 1.5kg', 'Royal Canin', 320000, 50,  'Dinh dưỡng cân bằng cho chó trưởng thành.', 'active'),
('PRD000000002', 'PDC000000001', 'Pate mèo vị cá ngừ 85g',            'Whiskas',       18000, 200, 'Pate mềm mịn cho mèo.', 'active'),
('PRD000000003', 'PDC000000002', 'Bóng cao su cho chó',               'PetToy',        45000, 80,  'Bóng cao su bền, an toàn.', 'active'),
('PRD000000004', 'PDC000000003', 'Vòng cổ có chuông',                 'PetStyle',      60000, 120, 'Vòng cổ điều chỉnh được.', 'active'),
('PRD000000005', 'PDC000000003', 'Giường ngủ cho mèo',                'PetStyle',     250000, 0,   'Giường êm, giặt được.', 'active');

-- Đồng bộ bộ sinh ID để các bản ghi mới không trùng ID mẫu
INSERT INTO id_sequences (prefix, last_value) VALUES
('PCT', 3), ('PET', 5), ('PDC', 3), ('PRD', 5)
ON DUPLICATE KEY UPDATE last_value = GREATEST(last_value, VALUES(last_value));
