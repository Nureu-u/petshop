<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: CAI DAT GIAO DIEN (luu file JSON, khong dung database)
   File    : models/AdminSettings.php
   Bang doc / ghi: (khong dung database)
   Duoc dung boi: controllers/AdminSettingsController.php, views/admin/auth/login.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Cai dat giao dien khu quan tri (luu trong file JSON, khong can them bang DB). */
final class AdminSettings
{
    private static function file(): string
    {
        return BASE_PATH . '/uploads/admin/settings.json';
    }

    public static function load(): array
    {
        $f = self::file();
        if (!is_file($f)) {
            return [];
        }
        $d = json_decode((string) file_get_contents($f), true);
        return is_array($d) ? $d : [];
    }

    public static function save(array $cfg): void
    {
        $dir = dirname(self::file());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(self::file(), json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /** Chi xoa anh do trang cai dat tai len (nam trong uploads/admin/). */
    public static function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/admin/')) {
            Uploader::delete($path);
        }
    }
}
