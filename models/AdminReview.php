<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: DANH GIA
   File    : models/AdminReview.php
   Bang doc / ghi: pets, products, review_images, reviews, users
   Duoc dung boi: controllers/AdminReviewController.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Quan ly danh gia cho khu quan tri (TV5). Danh gia chi co 2 trang thai: visible / hidden. */
final class AdminReview
{
    public const STATUSES = ['visible', 'hidden'];

    public static function stats(): array
    {
        $pdo = Database::pdo();
        $row = $pdo->query("SELECT COUNT(*) AS c, COALESCE(AVG(rating), 0) AS a FROM reviews WHERE status = 'visible'")->fetch();
        $dist = $pdo->query("SELECT rating, COUNT(*) FROM reviews WHERE status = 'visible' GROUP BY rating")->fetchAll(PDO::FETCH_KEY_PAIR);
        return ['count' => (int) $row['c'], 'avg' => (float) $row['a'], 'dist' => array_map('intval', $dist)];
    }

    public static function search(array $f, int $page = 1, int $perPage = 10): array
    {
        $where = [];
        $p = [];
        $type = (string) ($f['type'] ?? '');
        if ($type === 'pet') {
            $where[] = 'r.pet_id IS NOT NULL';
        } elseif ($type === 'product') {
            $where[] = 'r.product_id IS NOT NULL';
        }
        $rating = (int) ($f['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $where[] = 'r.rating = :rt';
            $p['rt'] = $rating;
        }
        $status = (string) ($f['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'r.status = :st';
            $p['st'] = $status;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) FROM reviews r $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT r.*, u.full_name, COALESCE(pe.name, pr.name) AS target_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             LEFT JOIN pets pe ON pe.id = r.pet_id
             LEFT JOIN products pr ON pr.id = r.product_id
             $whereSql ORDER BY r.created_at DESC, r.id DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);
        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $perPage];
    }

    /** @param string[] $ids @return array<string,string[]> review_id => danh sach anh */
    public static function imagesFor(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = Database::pdo()->prepare("SELECT review_id, image FROM review_images WHERE review_id IN ($in) ORDER BY created_at");
        $st->execute(array_values($ids));
        $map = [];
        foreach ($st->fetchAll() as $r) {
            $map[$r['review_id']][] = $r['image'];
        }
        return $map;
    }

    /** @return array{ok:bool,msg:string} */
    public static function setStatus(string $id, string $status): array
    {
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'msg' => 'review.msg.notfound'];
        }
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM reviews WHERE id = :id');
        $st->execute(['id' => $id]);
        if ((int) $st->fetchColumn() === 0) {
            return ['ok' => false, 'msg' => 'review.msg.notfound'];
        }
        Database::pdo()->prepare('UPDATE reviews SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
        return ['ok' => true, 'msg' => $status === 'hidden' ? 'review.msg.hidden' : 'review.msg.shown'];
    }
}
