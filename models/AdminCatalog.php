<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: DANH SACH THU CUNG / SAN PHAM (loc, sap xep theo ma)
   File    : models/AdminCatalog.php
   Bang doc / ghi: pets, products, pet_categories, product_categories
   Duoc dung boi: controllers/AdminPetController.php, controllers/AdminProductController.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Danh sach thu cung / san pham cho trang quan tri, co them cach sap xep theo ma (cu nhat truoc). Doc dung bang pets, products cua TV3. */
final class AdminCatalog
{
    public const SORTS = [
        'oldest'     => 'p.created_at ASC, p.id ASC',
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'p.price ASC, p.id ASC',
        'price_desc' => 'p.price DESC, p.id ASC',
        'name'       => 'p.name ASC, p.id ASC',
    ];

    /** Thu cung: loc q, category_id, status; sap xep sort (mac dinh oldest = ma tang dan). */
    public static function pets(array $f, int $page = 1, int $perPage = 12): array
    {
        return self::run('pets', 'pet_categories', ['p.name', 'p.breed', 'p.species'], Pet::STATUSES, $f, $page, $perPage);
    }

    /** San pham: loc q, category_id, status; sap xep sort. */
    public static function products(array $f, int $page = 1, int $perPage = 12): array
    {
        return self::run('products', 'product_categories', ['p.name', 'p.brand'], Product::STATUSES, $f, $page, $perPage);
    }

    /** Cac loai dang co trong bang pets (de loc o trang Thu cung). */
    public static function species(): array
    {
        return Database::pdo()->query("SELECT DISTINCT species FROM pets WHERE species <> '' ORDER BY species")->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Cac thuong hieu dang co trong bang products (de loc o trang San pham). */
    public static function brands(): array
    {
        return Database::pdo()->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function run(string $table, string $catTable, array $searchCols, array $statuses, array $f, int $page, int $perPage): array
    {
        $where = [];
        $p = [];
        $status = (string) ($f['status'] ?? '');
        if (in_array($status, $statuses, true)) {
            $where[] = 'p.status = :status';
            $p['status'] = $status;
        }
        if ($table === 'pets') {
            $species = trim((string) ($f['species'] ?? ''));
            if ($species !== '') {
                $where[] = 'p.species = :species';
                $p['species'] = $species;
            }
        }
        if ($table === 'products') {
            $brand = trim((string) ($f['brand'] ?? ''));
            if ($brand !== '') {
                $where[] = 'p.brand = :brand';
                $p['brand'] = $brand;
            }
            $stock = (string) ($f['stock'] ?? '');
            if ($stock === 'in') {
                $where[] = 'p.stock > 0';
            } elseif ($stock === 'low') {
                $where[] = 'p.stock <= 5';
            } elseif ($stock === 'out') {
                $where[] = 'p.stock = 0';
            }
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $parts = [];
            foreach ($searchCols as $i => $col) {
                $parts[] = $col . ' LIKE :q' . $i;
                $p['q' . $i] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        $cat = (string) ($f['category_id'] ?? '');
        if ($cat !== '') {
            $where[] = 'p.category_id = :cat';
            $p['cat'] = $cat;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sort = (string) ($f['sort'] ?? '');
        $order = self::SORTS[$sort] ?? self::SORTS['oldest'];
        $from = "FROM $table p JOIN $catTable c ON c.id = p.category_id";

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) $from $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare("SELECT p.*, c.name AS category_name $from $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset");
        $st->execute($p);
        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $perPage];
    }
}
