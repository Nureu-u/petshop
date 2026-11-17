# Pet Shop

## Cài đặt
1. Đặt thư mục vào `htdocs/petshop` hoặc thư mục đã đổi.
2. phpMyAdmin → chạy theo thứ tự: `database/schema.sql` → `database/patch_id_sequences.sql` → `database/seed.sql`.
3. Sửa `config/config.php` nếu DB có mật khẩu hoặc project không nằm ở `/petshop`.
4. Tạo admin: `php database/create_admin.php admin@petshop.local "Quản trị viên" Matkhau123`
5. Mở `http://localhost/petshop/`.

## API nội bộ cho các thành viên khác
- Kết nối DB: `Database::pdo()`
- Sinh ID: `IdGenerator::next(IdGenerator::ORDER)` (xem hằng số trong `core/IdGenerator.php`)
- Đăng nhập: `Auth::requireLogin()`, `Auth::requireAdmin()`, `Auth::id()`, `Auth::user()`
- Form POST: luôn có `<?= csrf_field() ?>` và gọi `csrf_verify()` / `require_post()`
- Thú cưng: `Pet::find()`, `Pet::isPurchasable()`, `Pet::markSold()`, `Pet::markAvailable()`
- Sản phẩm: `Product::find()`, `Product::isPurchasable($id,$qty)`, `Product::decreaseStock()`, `Product::increaseStock()`
- Giá/tồn kho luôn đọc lại từ DB ở backend, không tin dữ liệu từ trình duyệt.
- Thêm route mới: sửa mảng `$routes` trong `index.php`.
