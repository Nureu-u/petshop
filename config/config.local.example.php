<?php
// Copy file này thành config/config.local.php rồi sửa theo máy của bạn.
// config.local.php đã nằm trong .gitignore, KHÔNG commit mật khẩu/secret lên Git.
return [
    'db' => [
        'pass' => '',          // mật khẩu MariaDB của máy bạn
    ],
    // 'base_url' => '/petshop',
    // 'debug' => true,
    // 'vnpay' => ['tmn_code' => '...', 'hash_secret' => '...'],   // TV4
];
