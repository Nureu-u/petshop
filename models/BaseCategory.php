<?php
declare(strict_types=1);

/** Logic chung cho pet_categories và product_categories. */
abstract class BaseCategory
{
    protected const TABLE = '';       // tên bảng danh mục
    protected const ITEM_TABLE = '';  // bảng chứa mặt hàng thuộc danh mục
    protected const PREFIX = '';      // tiền tố ID

    public static function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT c.*, (SELECT COUNT(*) FROM ' . static::ITEM_TABLE . ' i WHERE i.category_id = c.id) AS item_count
                FROM ' . static::TABLE . ' c'
             . ($onlyActive ? " WHERE c.status = 'active'" : '') . ' ORDER BY c.name';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM ' . static::TABLE . ' WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM ' . static::TABLE . ' WHERE slug = :s');
        $st->execute(['s' => $slug]);
        return $st->fetch() ?: null;
    }

    /** @return array{0: array, 1: array} [lỗi, dữ liệu sạch] */
    public static function validate(array $in, ?string $exceptId = null): array
    {
        $errors = [];
        $name = trim((string) ($in['name'] ?? ''));
        $slug = slugify(trim((string) ($in['slug'] ?? '')) ?: $name);
        $desc = trim((string) ($in['description'] ?? ''));
        $status = (string) ($in['status'] ?? 'active');

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Tên danh mục bắt buộc, tối đa 100 ký tự.';
        }
        if ($slug === '' || strlen($slug) > 120) {
            $errors['slug'] = 'Slug không hợp lệ.';
        } else {
            $other = static::findBySlug($slug);
            if ($other && $other['id'] !== $exceptId) {
                $errors['slug'] = 'Slug đã tồn tại.';
            }
        }
        if (mb_strlen($desc) > 255) {
            $errors['description'] = 'Mô tả tối đa 255 ký tự.';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        return [$errors, [
            'name' => $name, 'slug' => $slug,
            'description' => $desc !== '' ? $desc : null, 'status' => $status,
        ]];
    }

    public static function create(array $d): string
    {
        $id = IdGenerator::next(static::PREFIX);
        Database::pdo()->prepare(
            'INSERT INTO ' . static::TABLE . ' (id, name, slug, description, status)
             VALUES (:id, :name, :slug, :description, :status)'
        )->execute(['id' => $id] + $d);
        return $id;
    }

    public static function update(string $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE ' . static::TABLE . ' SET name = :name, slug = :slug,
             description = :description, status = :status WHERE id = :id'
        )->execute(['id' => $id] + $d);
    }

    public static function setStatus(string $id, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return;
        }
        Database::pdo()->prepare('UPDATE ' . static::TABLE . ' SET status = :s WHERE id = :id')
            ->execute(['s' => $status, 'id' => $id]);
    }
}
