<?php
declare(strict_types=1);

/** Trả JSON cho các request AJAX (cập nhật giỏ, kiểm tra mã giảm giá). */
trait JsonResponds
{
    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function wantsJson(): bool
    {
        return input('ajax') === '1';
    }
}
