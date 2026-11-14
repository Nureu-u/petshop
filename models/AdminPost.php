<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: BAI VIET VA DANH MUC BAI VIET
   File    : models/AdminPost.php
   Bang doc / ghi: post_categories, posts, users
   Duoc dung boi: controllers/AdminPostController.php, views/admin/posts/form.php,
               views/admin/posts/index.php, views/admin/search/index.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Quan ly bai viet va danh muc bai viet cho khu quan tri (TV5). */
final class AdminPost
{
    public const STATUSES = ['draft', 'published', 'hidden'];

    public static function statusClass(string $s): string
    {
        return ['draft' => 'y', 'published' => 'gr', 'hidden' => 'gy'][$s] ?? 'gy';
    }

    /* ---------- Danh muc bai viet ---------- */

    public static function categories(): array
    {
        return Database::pdo()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id) AS post_count
             FROM post_categories c ORDER BY c.name'
        )->fetchAll();
    }

    public static function categoryFind(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM post_categories WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    private static function categoryBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM post_categories WHERE slug = :s');
        $st->execute(['s' => $slug]);
        return $st->fetch() ?: null;
    }

    /** @return array{0: array, 1: array} */
    public static function validateCategory(array $in, ?string $exceptId = null): array
    {
        $errors = [];
        $name = trim((string) ($in['name'] ?? ''));
        $slug = vn_slug(trim((string) ($in['slug'] ?? '')) ?: $name);
        $desc = trim((string) ($in['description'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = t('pcat.err.name');
        }
        if ($slug === '' || strlen($slug) > 120) {
            $errors['slug'] = t('pcat.err.slug');
        } else {
            $o = self::categoryBySlug($slug);
            if ($o && $o['id'] !== $exceptId) {
                $errors['slug'] = t('pcat.err.slug_dup');
            }
        }
        if (mb_strlen($desc) > 255) {
            $errors['description'] = t('pcat.err.desc');
        }
        return [$errors, ['name' => $name, 'slug' => $slug, 'description' => $desc !== '' ? $desc : null]];
    }

    public static function categoryCreate(array $d): string
    {
        $id = IdGenerator::next(IdGenerator::POST_CATEGORY);
        Database::pdo()->prepare(
            'INSERT INTO post_categories (id, name, slug, description) VALUES (:id, :name, :slug, :description)'
        )->execute(['id' => $id] + $d);
        return $id;
    }

    public static function categoryUpdate(string $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE post_categories SET name = :name, slug = :slug, description = :description WHERE id = :id'
        )->execute(['id' => $id] + $d);
    }

    /** Chi xoa duoc khi chua co bai viet nao (khoa ngoai RESTRICT). */
    public static function categoryDelete(string $id): string
    {
        if (!self::categoryFind($id)) {
            return 'pcat.msg.notfound';
        }
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM posts WHERE category_id = :id');
        $st->execute(['id' => $id]);
        if ((int) $st->fetchColumn() > 0) {
            return 'pcat.msg.in_use';
        }
        Database::pdo()->prepare('DELETE FROM post_categories WHERE id = :id')->execute(['id' => $id]);
        return 'pcat.msg.deleted';
    }

    /* ---------- Bai viet ---------- */

    public static function search(array $f, int $page = 1, int $perPage = 10): array
    {
        $where = [];
        $p = [];
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'p.title LIKE :q';
            $p['q'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $status = (string) ($f['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'p.status = :st';
            $p['st'] = $status;
        }
        $cat = (string) ($f['category'] ?? '');
        if ($cat !== '') {
            $where[] = 'p.category_id = :cat';
            $p['cat'] = $cat;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM posts p $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT p.id, p.title, p.slug, p.image, p.status, p.published_at, p.created_at,
                    c.name AS category_name, u.full_name AS author_name
             FROM posts p
             JOIN post_categories c ON c.id = p.category_id
             JOIN users u ON u.id = p.author_id
             $whereSql ORDER BY p.created_at DESC, p.id DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);
        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $perPage];
    }

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM posts WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    private static function findBySlug(string $slug): ?array
    {
        $st = Database::pdo()->prepare('SELECT id FROM posts WHERE slug = :s');
        $st->execute(['s' => $slug]);
        return $st->fetch() ?: null;
    }

    /** @return array{0: array, 1: array} */
    public static function validate(array $in, ?string $exceptId = null): array
    {
        $errors = [];
        $title = trim((string) ($in['title'] ?? ''));
        $slugIn = trim((string) ($in['slug'] ?? ''));
        $cat = trim((string) ($in['category_id'] ?? ''));
        $excerpt = trim((string) ($in['excerpt'] ?? ''));
        $content = trim((string) ($in['content'] ?? ''));
        $status = (string) ($in['status'] ?? 'draft');

        if ($title === '' || mb_strlen($title) > 200) {
            $errors['title'] = t('post.err.title');
        }
        $slug = vn_slug($slugIn !== '' ? $slugIn : $title);
        if ($slug === '' || strlen($slug) > 220) {
            $errors['slug'] = t('post.err.slug');
        } else {
            $o = self::findBySlug($slug);
            if ($o && $o['id'] !== $exceptId) {
                $errors['slug'] = t('post.err.slug_dup');
            }
        }
        if ($cat === '' || !self::categoryFind($cat)) {
            $errors['category_id'] = t('post.err.cat');
        }
        if (mb_strlen($excerpt) > 500) {
            $errors['excerpt'] = t('post.err.excerpt');
        }
        if ($content === '') {
            $errors['content'] = t('post.err.content');
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = t('post.err.status');
        }

        return [$errors, [
            'category_id' => $cat, 'title' => $title, 'slug' => $slug,
            'excerpt' => $excerpt !== '' ? $excerpt : null, 'content' => $content, 'status' => $status,
        ]];
    }

    /** published: co ngay dang; draft: khong co; hidden: giu ngay dang cu (neu co). */
    private static function publishedAt(string $status, ?string $existing): ?string
    {
        if ($status === 'published') {
            return $existing ?: date('Y-m-d H:i:s');
        }
        if ($status === 'draft') {
            return null;
        }
        return $existing ?: null;
    }

    public static function create(array $d, ?string $image, string $authorId): string
    {
        $id = IdGenerator::next(IdGenerator::POST);
        Database::pdo()->prepare(
            'INSERT INTO posts (id, category_id, author_id, title, slug, excerpt, content, image, status, published_at)
             VALUES (:id, :category_id, :author_id, :title, :slug, :excerpt, :content, :image, :status, :published_at)'
        )->execute([
            'id' => $id, 'category_id' => $d['category_id'], 'author_id' => $authorId,
            'title' => $d['title'], 'slug' => $d['slug'], 'excerpt' => $d['excerpt'], 'content' => $d['content'],
            'image' => $image, 'status' => $d['status'],
            'published_at' => self::publishedAt($d['status'], null),
        ]);
        return $id;
    }

    /** $image = null: giu anh cu. */
    public static function update(string $id, array $d, ?string $image = null): void
    {
        $old = self::find($id);
        $sql = 'UPDATE posts SET category_id = :category_id, title = :title, slug = :slug, excerpt = :excerpt,
                content = :content, status = :status, published_at = :published_at';
        $params = [
            'id' => $id, 'category_id' => $d['category_id'], 'title' => $d['title'], 'slug' => $d['slug'],
            'excerpt' => $d['excerpt'], 'content' => $d['content'], 'status' => $d['status'],
            'published_at' => self::publishedAt($d['status'], $old['published_at'] ?? null),
        ];
        if ($image !== null) {
            $sql .= ', image = :image';
            $params['image'] = $image;
        }
        Database::pdo()->prepare($sql . ' WHERE id = :id')->execute($params);
    }

    /** Dang / an / dang lai nhanh tu danh sach. */
    public static function setStatus(string $id, string $status): bool
    {
        $post = self::find($id);
        if (!$post || !in_array($status, self::STATUSES, true)) {
            return false;
        }
        Database::pdo()->prepare('UPDATE posts SET status = :s, published_at = :pa WHERE id = :id')->execute([
            's' => $status, 'pa' => self::publishedAt($status, $post['published_at']), 'id' => $id,
        ]);
        return true;
    }
}
