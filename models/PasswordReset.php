<?php
declare(strict_types=1);

/**
 * Token đặt lại mật khẩu: sinh ngẫu nhiên bằng random_bytes, DB chỉ lưu SHA-256 của token,
 * hết hạn sau 30 phút, dùng một lần.
 */
final class PasswordReset
{
    private const TTL_MINUTES = 30;

    /** @return string token gốc (chỉ dùng để gửi cho người dùng, không lưu) */
    public static function create(string $userId): string
    {
        $pdo = Database::pdo();
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :u')->execute(['u' => $userId]);
        $pdo->prepare(
            'INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at)
             VALUES (:id, :u, :h, DATE_ADD(NOW(), INTERVAL ' . self::TTL_MINUTES . ' MINUTE))'
        )->execute([
            'id' => IdGenerator::next(IdGenerator::RESET_TOKEN),
            'u' => $userId, 'h' => hash('sha256', $token),
        ]);
        return $token;
    }

    public static function findValid(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $st = Database::pdo()->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()'
        );
        $st->execute(['h' => hash('sha256', $token)]);
        return $st->fetch() ?: null;
    }

    public static function resetPassword(string $token, string $newPassword): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'UPDATE password_reset_tokens SET used_at = NOW()
                 WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()'
            );
            $st->execute(['h' => hash('sha256', $token)]);
            if ($st->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }
            $st = $pdo->prepare('SELECT user_id FROM password_reset_tokens WHERE token_hash = :h');
            $st->execute(['h' => hash('sha256', $token)]);
            $userId = $st->fetchColumn();
            User::updatePassword((string) $userId, password_hash($newPassword, PASSWORD_DEFAULT));
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
