<?php
declare(strict_types=1);

final class Product
{
    public const STATUSES = ['active', 'inactive', 'hidden'];
    private const MAX_STOCK = 1000000;

    private const SORTS = [
        'newest'     => 'p.created_at DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
    ];

    public static function find(string $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT p.*, c.name AS category_name, c.status AS category_status
             FROM products p JOIN product_categories c ON c.id = p.category_id WHERE p.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * $filters: q, category_id, brand, min_price, max_price, in_stock (1), sort, status (chỉ admin)
     * $publicOnly = true: chỉ sản phẩm 'active' thuộc danh mục 'active'.
     */
    public static function search(array $filters = [], int $page = 1, ?int $perPage = null, bool $publicOnly = true): array
    {
        $perPage = $perPage ?? (int) config('per_page', 12);
        $where = [];
        $p = [];

        if ($publicOnly) {
            $where[] = "p.status = 'active'";
            $where[] = "c.status = 'active'";
        } elseif (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'p.status = :status';
            $p['status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . addcslashes((string) $filters['q'], '%_\\') . '%';
            $where[] = '(p.name LIKE :q1 OR p.brand LIKE :q2)';
            $p['q1'] = $p['q2'] = $like;
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :cat';
            $p['cat'] = $filters['category_id'];
        }
        if (!empty($filters['brand'])) {
            $where[] = 'p.brand = :brand';
            $p['brand'] = $filters['brand'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && ($v = parse_money($filters['min_price'])) !== null) {
            $where[] = 'p.price >= :minp';
            $p['minp'] = $v;
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && ($v = parse_money($filters['max_price'])) !== null) {
            $where[] = 'p.price <= :maxp';
            $p['maxp'] = $v;
        }
        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock > 0';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = self::SORTS[$filters['sort'] ?? 'newest'] ?? self::SORTS['newest'];
        $from = 'FROM products p JOIN product_categories c ON c.id = p.category_id';

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT COUNT(*) $from $whereSql");
        $st->execute($p);
        $total = (int) $st->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $st = $pdo->prepare(
            "SELECT p.*, c.name AS category_name $from $whereSql
             ORDER BY $order, p.id DESC LIMIT $perPage OFFSET $offset"
        );
        $st->execute($p);

        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function brands(): array
    {
        return Database::pdo()
            ->query("SELECT DISTINCT brand FROM products WHERE status = 'active' AND brand IS NOT NULL AND brand <> '' ORDER BY brand")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array{0: array, 1: array} */
    public static function validate(array $in): array
    {
        $errors = [];
        $name   = trim((string) ($in['name'] ?? ''));
        $cat    = trim((string) ($in['category_id'] ?? ''));
        $brand  = trim((string) ($in['brand'] ?? ''));
        $desc   = trim((string) ($in['description'] ?? ''));
        $status = (string) ($in['status'] ?? 'active');
        $price  = parse_money($in['price'] ?? '');
        $stockRaw = trim((string) ($in['stock'] ?? '0'));

        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = 'Tên bắt buộc, tối đa 150 ký tự.';
        }
        if ($cat === '' || !ProductCategory::find($cat)) {
            $errors['category_id'] = 'Vui lòng chọn danh mục hợp lệ.';
        }
        if (mb_strlen($brand) > 100) {
            $errors['brand'] = 'Thương hiệu tối đa 100 ký tự.';
        }
        if ($price === null) {
            $errors['price'] = 'Giá phải là số nguyên không âm.';
        }
        $stock = 0;
        if (!ctype_digit($stockRaw) || (int) $stockRaw > self::MAX_STOCK) {
            $errors['stock'] = 'Tồn kho phải là số nguyên từ 0 đến ' . self::MAX_STOCK . '.';
        } else {
            $stock = (int) $stockRaw;
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }

        return [$errors, [
            'category_id' => $cat, 'name' => $name,
            'brand' => $brand !== '' ? $brand : null,
            'price' => $price ?? 0, 'stock' => $stock,
            'description' => $desc !== '' ? $desc : null, 'status' => $status,
        ]];
    }

    public static function create(array $d, ?string $image): string
    {
        $id = IdGenerator::next(IdGenerator::PRODUCT);
        Database::pdo()->prepare(
            'INSERT INTO products (id, category_id, name, brand, price, stock, description, image, status)
             VALUES (:id, :category_id, :name, :brand, :price, :stock, :description, :image, :status)'
        )->execute(['id' => $id, 'image' => $image] + $d);
        return $id;
    }

    public static function update(string $id, array $d, ?string $image = null): void
    {
        $sql = 'UPDATE products SET category_id = :category_id, name = :name, brand = :brand,
                price = :price, stock = :stock, description = :description, status = :status';
        $params = ['id' => $id] + $d;
        if ($image !== null) {
            $sql .= ', image = :image';
            $params['image'] = $image;
        }
        Database::pdo()->prepare($sql . ' WHERE id = :id')->execute($params);
    }

    public static function setStatus(string $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $st = Database::pdo()->prepare('UPDATE products SET status = :s WHERE id = :id');
        $st->execute(['s' => $status, 'id' => $id]);
        return $st->rowCount() > 0;
    }

    /* ---------- Hàm cho TV4 (giỏ hàng / đơn hàng) ---------- */

    /** Sản phẩm đang bán, danh mục hoạt động và đủ tồn kho cho $qty? */
    public static function isPurchasable(string $id, int $qty = 1): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.status = 'active' AND c.status = 'active' AND p.stock >= :q"
        );
        $st->execute(['id' => $id, 'q' => max(1, $qty)]);
        return (int) $st->fetchColumn() === 1;
    }

    /**
     * Trừ kho nguyên tử: chỉ thành công nếu còn đủ hàng và sản phẩm đang 'active'.
     * Gọi trong transaction tạo đơn; false => hết hàng, phải rollback.
     * Quy ước nhóm: CHỈ TV4 trừ/hoàn kho khi đặt/hủy đơn; TV3/TV5 chỉ sửa tồn kho thủ công ở trang admin.
     */
    public static function decreaseStock(string $id, int $qty): bool
    {
        if ($qty < 1) {
            return false;
        }
        $st = Database::pdo()->prepare(
            "UPDATE products SET stock = stock - :q1 WHERE id = :id AND status = 'active' AND stock >= :q2"
        );
        $st->execute(['q1' => $qty, 'q2' => $qty, 'id' => $id]);
        return $st->rowCount() === 1;
    }

    /** Hoàn kho khi hủy đơn / thanh toán thất bại. */
    public static function increaseStock(string $id, int $qty): void
    {
        if ($qty < 1) {
            return;
        }
        Database::pdo()->prepare('UPDATE products SET stock = stock + :q WHERE id = :id')
            ->execute(['q' => $qty, 'id' => $id]);
    }
}
