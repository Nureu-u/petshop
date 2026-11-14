<?php
declare(strict_types=1);

final class Pet
{
    public const STATUSES = ['available', 'sold', 'hidden'];
    public const GENDERS  = ['male', 'female'];

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
             FROM pets p JOIN pet_categories c ON c.id = p.category_id WHERE p.id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Tìm kiếm / lọc / phân trang.
     * $filters: q, category_id, species, gender, min_price, max_price, sort, status (chỉ admin)
     * $publicOnly = true: chỉ thú cưng 'available' thuộc danh mục 'active'.
     */
    public static function search(array $filters = [], int $page = 1, ?int $perPage = null, bool $publicOnly = true): array
    {
        $perPage = $perPage ?? (int) config('per_page', 12);
        $where = [];
        $p = [];

        if ($publicOnly) {
            $where[] = "p.status = 'available'";
            $where[] = "c.status = 'active'";
        } elseif (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'p.status = :status';
            $p['status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . addcslashes((string) $filters['q'], '%_\\') . '%';
            $where[] = '(p.name LIKE :q1 OR p.breed LIKE :q2 OR p.species LIKE :q3)';
            $p['q1'] = $p['q2'] = $p['q3'] = $like;
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :cat';
            $p['cat'] = $filters['category_id'];
        }
        if (!empty($filters['species'])) {
            $where[] = 'p.species = :species';
            $p['species'] = $filters['species'];
        }
        if (!empty($filters['gender']) && in_array($filters['gender'], self::GENDERS, true)) {
            $where[] = 'p.gender = :gender';
            $p['gender'] = $filters['gender'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && ($v = parse_money($filters['min_price'])) !== null) {
            $where[] = 'p.price >= :minp';
            $p['minp'] = $v;
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && ($v = parse_money($filters['max_price'])) !== null) {
            $where[] = 'p.price <= :maxp';
            $p['maxp'] = $v;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = self::SORTS[$filters['sort'] ?? 'newest'] ?? self::SORTS['newest'];
        $from = 'FROM pets p JOIN pet_categories c ON c.id = p.category_id';

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

    public static function species(): array
    {
        return Database::pdo()
            ->query("SELECT DISTINCT species FROM pets WHERE status = 'available' ORDER BY species")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array{0: array, 1: array} [lỗi, dữ liệu sạch] */
    public static function validate(array $in): array
    {
        $errors = [];
        $name    = trim((string) ($in['name'] ?? ''));
        $cat     = trim((string) ($in['category_id'] ?? ''));
        $species = trim((string) ($in['species'] ?? ''));
        $breed   = trim((string) ($in['breed'] ?? ''));
        $gender  = trim((string) ($in['gender'] ?? ''));
        $birth   = trim((string) ($in['birth_date'] ?? ''));
        $weight  = trim((string) ($in['weight'] ?? ''));
        $health  = trim((string) ($in['health_status'] ?? ''));
        $desc    = trim((string) ($in['description'] ?? ''));
        $status  = (string) ($in['status'] ?? 'available');
        $price   = parse_money($in['price'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Tên bắt buộc, tối đa 100 ký tự.';
        }
        if ($cat === '' || !PetCategory::find($cat)) {
            $errors['category_id'] = 'Vui lòng chọn danh mục hợp lệ.';
        }
        if ($species === '' || mb_strlen($species) > 50) {
            $errors['species'] = 'Loài bắt buộc, tối đa 50 ký tự.';
        }
        if (mb_strlen($breed) > 100) {
            $errors['breed'] = 'Giống tối đa 100 ký tự.';
        }
        if ($gender !== '' && !in_array($gender, self::GENDERS, true)) {
            $errors['gender'] = 'Giới tính không hợp lệ.';
        }
        if ($birth !== '') {
            if (!is_valid_date($birth) || $birth > date('Y-m-d')) {
                $errors['birth_date'] = 'Ngày sinh không hợp lệ hoặc ở tương lai.';
            }
        }
        $weightVal = null;
        if ($weight !== '') {
            if (!is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 9999.99) {
                $errors['weight'] = 'Cân nặng phải lớn hơn 0 và nhỏ hơn 10000.';
            } else {
                $weightVal = round((float) $weight, 2);
            }
        }
        if ($price === null) {
            $errors['price'] = 'Giá phải là số nguyên không âm.';
        }
        if (mb_strlen($health) > 100) {
            $errors['health_status'] = 'Tình trạng sức khỏe tối đa 100 ký tự.';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }

        return [$errors, [
            'category_id' => $cat, 'name' => $name, 'species' => $species,
            'breed' => $breed !== '' ? $breed : null,
            'gender' => $gender !== '' ? $gender : null,
            'birth_date' => $birth !== '' ? $birth : null,
            'weight' => $weightVal, 'price' => $price ?? 0,
            'health_status' => $health !== '' ? $health : null,
            'description' => $desc !== '' ? $desc : null,
            'status' => $status,
        ]];
    }

    public static function create(array $d, ?string $image): string
    {
        $id = IdGenerator::next(IdGenerator::PET);
        Database::pdo()->prepare(
            'INSERT INTO pets (id, category_id, name, species, breed, gender, birth_date, weight,
                               price, health_status, description, image, status)
             VALUES (:id, :category_id, :name, :species, :breed, :gender, :birth_date, :weight,
                     :price, :health_status, :description, :image, :status)'
        )->execute(['id' => $id, 'image' => $image] + $d);
        return $id;
    }

    /** $image = null: giữ ảnh cũ. */
    public static function update(string $id, array $d, ?string $image = null): void
    {
        $sql = 'UPDATE pets SET category_id = :category_id, name = :name, species = :species,
                breed = :breed, gender = :gender, birth_date = :birth_date, weight = :weight,
                price = :price, health_status = :health_status, description = :description,
                status = :status';
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
        $st = Database::pdo()->prepare('UPDATE pets SET status = :s WHERE id = :id');
        $st->execute(['s' => $status, 'id' => $id]);
        return $st->rowCount() > 0;
    }

    /* ---------- Hàm cho TV4 (giỏ hàng / đơn hàng) ---------- */

    /** Thú cưng có thể mua không (còn bán + danh mục đang hoạt động)? */
    public static function isPurchasable(string $id): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM pets p JOIN pet_categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.status = 'available' AND c.status = 'active'"
        );
        $st->execute(['id' => $id]);
        return (int) $st->fetchColumn() === 1;
    }

    /**
     * Đánh dấu đã bán một cách nguyên tử: chỉ thành công nếu đang 'available'.
     * Gọi trong transaction tạo đơn; trả false => có người mua trước, phải rollback.
     */
    public static function markSold(string $id): bool
    {
        $st = Database::pdo()->prepare("UPDATE pets SET status = 'sold' WHERE id = :id AND status = 'available'");
        $st->execute(['id' => $id]);
        return $st->rowCount() === 1;
    }

    /** Hoàn lại khi hủy đơn / thanh toán thất bại. */
    public static function markAvailable(string $id): bool
    {
        $st = Database::pdo()->prepare("UPDATE pets SET status = 'available' WHERE id = :id AND status = 'sold'");
        $st->execute(['id' => $id]);
        return $st->rowCount() === 1;
    }
}
