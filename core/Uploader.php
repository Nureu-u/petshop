<?php
declare(strict_types=1);

/**
 * Upload ảnh an toàn: kiểm tra lỗi, dung lượng, MIME thật (finfo), kích thước ảnh,
 * đặt tên ngẫu nhiên. DB lưu đường dẫn tương đối, ví dụ "uploads/pets/ab12.jpg".
 */
final class Uploader
{
    /** @return array{0: ?string, 1: ?string} [đường dẫn tương đối, lỗi]; không chọn file => [null, null] */
    public static function image(?array $file, string $subDir): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Tải ảnh lên thất bại (mã lỗi ' . $file['error'] . ').'];
        }
        if ($file['size'] > config('upload.max_size')) {
            return [null, 'Ảnh vượt quá dung lượng cho phép (tối đa 2MB).'];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return [null, 'File upload không hợp lệ.'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = config('upload.allowed');
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return [null, 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'];
        }

        $dir = BASE_PATH . '/uploads/' . $subDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return [null, 'Không tạo được thư mục lưu ảnh.'];
        }

        $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            return [null, 'Không lưu được ảnh.'];
        }
        return ["uploads/$subDir/$name", null];
    }

    /** Xóa file ảnh cũ (chỉ xóa trong thư mục uploads). */
    public static function delete(?string $relPath): void
    {
        if (!$relPath || strpos($relPath, 'uploads/') !== 0 || strpos($relPath, '..') !== false) {
            return;
        }
        $full = BASE_PATH . '/' . $relPath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
