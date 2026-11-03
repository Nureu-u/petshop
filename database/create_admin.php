<?php
declare(strict_types=1);

/**
 * Tạo tài khoản admin từ dòng lệnh (không để mật khẩu trong file SQL).
 * php database/create_admin.php admin@petshop.local "Quản trị viên" "MatKhau-It-Nhat-8-Ky-Tu"
 * 
 * Cách chạy: 
 * Vào CMD chạy cd /d "DÁN_ĐƯỜNG_DẪN_VÀO_ĐÂY", thư mục chứa petshop, ví dụ: cd /d "C:\xampp\htdocs\petshop"
 * "C:\xampp\php\php.exe" database/create_admin.php email "họ tên" "mật khẩu"
 */
if (PHP_SAPI !== 'cli') {
    exit('Chỉ chạy từ dòng lệnh.');
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/IdGenerator.php';
require_once BASE_PATH . '/models/User.php';

[, $email, $name, $password] = $argv + [null, null, null, null];
if (!$email || !$name || !$password) {
    exit("Cách dùng: php database/create_admin.php <email> \"<họ tên>\" <mật khẩu>\n");
}

$email = normalize_email($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Email không hợp lệ.\n");
}
if (strlen($password) < 8) {
    exit("Mật khẩu tối thiểu 8 ký tự.\n");
}
if (User::findByEmail($email)) {
    exit("Email đã tồn tại.\n");
}

$id = User::create(['full_name' => $name, 'email' => $email, 'password' => $password, 'phone' => null], 'admin');
echo "Đã tạo admin: $id ($email)\n";
