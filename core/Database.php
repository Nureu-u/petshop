<?php
declare(strict_types=1);

/**
 * Kết nối PDO dùng chung cho toàn dự án (MariaDB).
 * Cách dùng: $pdo = Database::pdo();
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $c['host'], $c['port'], $c['name']
            );
            try {
                self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                exit(config('debug') ? 'DB error: ' . $e->getMessage() : 'Không thể kết nối cơ sở dữ liệu.');
            }
        }
        return self::$pdo;
    }
}
