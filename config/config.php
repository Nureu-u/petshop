<?php
// Cấu hình chung. Không commit mật khẩu thật lên Git.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'petshop_db',
        'user' => 'root',
        'pass' => '',
    ],
    // Đường dẫn web tới thư mục project (không có dấu "/" cuối)
    'base_url' => '/petshop',
    // Bật khi phát triển; tắt khi nộp/demo thật
    'debug' => true,
    'upload' => [
        'max_size' => 2 * 1024 * 1024, // 2MB
        'allowed'  => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
    ],
    'per_page' => 12,
    'session_name' => 'PETSHOPSESSID',
];
