<?php
declare(strict_types=1);

/** Chuyển tiếp dữ liệu phản hồi VNPAY về route chuẩn để kiểm tra chữ ký bảo mật. */
require_once __DIR__ . '/../core/bootstrap.php';
redirect('payment/vnpay_return', $_GET);
