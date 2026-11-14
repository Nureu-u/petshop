<?php
declare(strict_types=1);

/* ==============================================================================
   MODEL: TIM KIEM CHUNG
   File    : models/AdminSearch.php
   Bang doc / ghi: coupons, orders, pets, posts, products, users
   Duoc dung boi: controllers/AdminSearchController.php
   Ten bang va ten cot lay dung tu database/schema.sql cua TV3.
   ============================================================================== */
/** Tim kiem nhanh trong khu quan tri: don hang, nguoi dung, thu cung, san pham, bai viet, ma giam gia. */
final class AdminSearch
{
    public static function run(string $q): array
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $pdo = Database::pdo();
        $run = function (string $sql, int $n, bool $phoneThird = false) use ($pdo, $like, $q): array {
            $params = [];
            for ($i = 1; $i <= $n; $i++) {
                $params['p' . $i] = $like;
            }
            if ($phoneThird) {
                $params['p3'] = phone_like($q);
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        };
        return [
            'orders' => $run('SELECT id, order_code, customer_name, customer_phone, total_amount, status FROM orders
                              WHERE order_code LIKE :p1 OR customer_name LIKE :p2 OR customer_phone LIKE :p3
                              ORDER BY created_at DESC LIMIT 5', 3, true),
            'users' => $run('SELECT id, full_name, email, role FROM users
                             WHERE full_name LIKE :p1 OR email LIKE :p2 OR phone LIKE :p3
                             ORDER BY created_at DESC LIMIT 5', 3, true),
            'pets' => $run('SELECT id, name, species, breed, price, status FROM pets
                            WHERE name LIKE :p1 OR species LIKE :p2 OR breed LIKE :p3
                            ORDER BY created_at DESC LIMIT 5', 3),
            'products' => $run('SELECT id, name, price, stock, status FROM products WHERE name LIKE :p1 ORDER BY name LIMIT 5', 1),
            'posts' => $run('SELECT id, title, status FROM posts WHERE title LIKE :p1 ORDER BY created_at DESC LIMIT 5', 1),
            'coupons' => $run('SELECT id, code, status FROM coupons WHERE code LIKE :p1 ORDER BY code LIMIT 5', 1),
        ];
    }
}
