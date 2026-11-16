<?php
declare(strict_types=1);

/**
 * Giỏ hàng (TV4). Bảng: carts (1 user = 1 giỏ), cart_items.
 * Quy ước: thú cưng là cá thể duy nhất (quantity luôn = 1); sản phẩm có số lượng.
 * Giá & tồn kho LUÔN đọc lại từ bảng pets/products, không tin dữ liệu từ trình duyệt.
 */
final class Cart
{
    private const MAX_QTY_PER_ITEM = 99;

    public static function cartId(string $userId): string
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id FROM carts WHERE user_id = :u');
        $st->execute(['u' => $userId]);
        $id = $st->fetchColumn();
        if ($id) {
            return (string) $id;
        }
        try {
            $id = IdGenerator::next(IdGenerator::CART);
            $pdo->prepare('INSERT INTO carts (id, user_id) VALUES (:id, :u)')->execute(['id' => $id, 'u' => $userId]);
            return $id;
        } catch (PDOException $e) {
            // Hai request cùng tạo giỏ: lấy giỏ đã được tạo trước
            $st->execute(['u' => $userId]);
            $id = $st->fetchColumn();
            if ($id) {
                return (string) $id;
            }
            throw $e;
        }
    }

    /**
     * Danh sách mặt hàng trong giỏ, đã gắn giá/tồn kho hiện tại.
     * Khóa trả về khớp views/cart/index.php và views/checkout/index.php của TV2.
     */
    public static function items(string $userId): array
    {
        $st = Database::pdo()->prepare(
            "SELECT ci.id, ci.pet_id, ci.product_id, ci.quantity,
                    pe.name AS pet_name, pe.breed, pe.gender, pe.weight, pe.price AS pet_price,
                    pe.status AS pet_status, pe.image AS pet_image, pc.status AS pet_cat_status,
                    pr.name AS prd_name, pr.brand, pr.price AS prd_price, pr.stock,
                    pr.status AS prd_status, pr.image AS prd_image, rc.status AS prd_cat_status
             FROM cart_items ci
             JOIN carts c ON c.id = ci.cart_id
             LEFT JOIN pets pe ON pe.id = ci.pet_id
             LEFT JOIN pet_categories pc ON pc.id = pe.category_id
             LEFT JOIN products pr ON pr.id = ci.product_id
             LEFT JOIN product_categories rc ON rc.id = pr.category_id
             WHERE c.user_id = :u
             ORDER BY ci.created_at, ci.id"
        );
        $st->execute(['u' => $userId]);

        $out = [];
        foreach ($st->fetchAll() as $r) {
            if ($r['pet_id'] !== null) {
                $ok = $r['pet_status'] === 'available' && $r['pet_cat_status'] === 'active';
                $gender = $r['gender'] ? ' | ' . status_label((string) $r['gender']) : '';
                $weight = $r['weight'] !== null ? ' | ' . rtrim(rtrim((string) $r['weight'], '0'), '.') . ' kg' : '';
                $row = [
                    'item_type' => 'pet', 'item_id' => $r['pet_id'], 'name' => $r['pet_name'],
                    'subtitle'  => 'Giống: ' . ($r['breed'] ?: 'Không rõ') . $gender . $weight,
                    'unit_price' => (int) $r['pet_price'], 'quantity' => 1, 'max_stock' => 1,
                    'image' => $r['pet_image'], 'available' => $ok,
                    'warning' => $ok ? null : 'Thú cưng này đã có chủ hoặc ngừng bán.',
                ];
            } else {
                $stock = (int) $r['stock'];
                $ok = $r['prd_status'] === 'active' && $r['prd_cat_status'] === 'active' && $stock >= (int) $r['quantity'];
                $row = [
                    'item_type' => 'product', 'item_id' => $r['product_id'], 'name' => $r['prd_name'],
                    'subtitle'  => 'Thương hiệu: ' . ($r['brand'] ?: 'Không rõ') . ' | Kho còn: ' . $stock,
                    'unit_price' => (int) $r['prd_price'], 'quantity' => (int) $r['quantity'], 'max_stock' => max(1, $stock),
                    'image' => $r['prd_image'], 'available' => $ok,
                    'warning' => $ok ? null : 'Sản phẩm không đủ hàng hoặc ngừng bán.',
                ];
            }
            $row['id'] = $r['id'];
            $row['item_name'] = $row['name'];                              // alias cho view checkout
            $row['line_total'] = $row['unit_price'] * $row['quantity'];
            $row['image'] = $row['image'] ? asset($row['image']) : '';
            $out[] = $row;
        }
        return $out;
    }

    public static function subtotal(array $items): int
    {
        return (int) array_sum(array_column($items, 'line_total'));
    }

    public static function count(string $userId): int
    {
        $st = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = :u'
        );
        $st->execute(['u' => $userId]);
        return (int) $st->fetchColumn();
    }

    /** @return ?string null nếu thành công, ngược lại là thông báo lỗi */
    public static function add(string $userId, string $type, string $itemId, int $qty): ?string
    {
        $pdo = Database::pdo();
        $cartId = self::cartId($userId);

        if ($type === 'pet') {
            if (!Pet::isPurchasable($itemId)) {
                return 'Thú cưng này không còn bán.';
            }
            $pet = Pet::find($itemId);
            $st = $pdo->prepare('SELECT 1 FROM cart_items WHERE cart_id = :c AND pet_id = :i');
            $st->execute(['c' => $cartId, 'i' => $itemId]);
            if ($st->fetchColumn()) {
                return 'Thú cưng này đã có trong giỏ hàng của bạn.';
            }
            $pdo->prepare(
                'INSERT INTO cart_items (id, cart_id, pet_id, quantity, unit_price) VALUES (:id, :c, :i, 1, :p)'
            )->execute([
                'id' => IdGenerator::next(IdGenerator::CART_ITEM), 'c' => $cartId,
                'i' => $itemId, 'p' => $pet['price'],
            ]);
            return null;
        }

        if ($type === 'product') {
            $qty = max(1, $qty);
            $st = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :c AND product_id = :i');
            $st->execute(['c' => $cartId, 'i' => $itemId]);
            $existing = $st->fetch();
            $newQty = $qty + (int) ($existing['quantity'] ?? 0);

            if ($newQty > self::MAX_QTY_PER_ITEM) {
                return 'Mỗi sản phẩm chỉ được mua tối đa ' . self::MAX_QTY_PER_ITEM . ' trong một đơn.';
            }
            if (!Product::isPurchasable($itemId, $newQty)) {
                return 'Sản phẩm không đủ số lượng tồn kho hoặc đã ngừng bán.';
            }
            $product = Product::find($itemId);
            if ($existing) {
                $pdo->prepare('UPDATE cart_items SET quantity = :q, unit_price = :p WHERE id = :id')
                    ->execute(['q' => $newQty, 'p' => $product['price'], 'id' => $existing['id']]);
            } else {
                $pdo->prepare(
                    'INSERT INTO cart_items (id, cart_id, product_id, quantity, unit_price) VALUES (:id, :c, :i, :q, :p)'
                )->execute([
                    'id' => IdGenerator::next(IdGenerator::CART_ITEM), 'c' => $cartId,
                    'i' => $itemId, 'q' => $newQty, 'p' => $product['price'],
                ]);
            }
            return null;
        }

        return 'Loại mặt hàng không hợp lệ.';
    }

    /** Cập nhật số lượng sản phẩm (thú cưng luôn = 1). @return ?string lỗi */
    public static function setQuantity(string $userId, string $cartItemId, int $qty): ?string
    {
        $row = self::ownedItem($userId, $cartItemId);
        if (!$row) {
            return 'Không tìm thấy mặt hàng trong giỏ.';
        }
        if ($row['product_id'] === null) {
            return null; // thú cưng: không đổi số lượng
        }
        $qty = max(1, $qty);
        if ($qty > self::MAX_QTY_PER_ITEM || !Product::isPurchasable((string) $row['product_id'], $qty)) {
            return 'EF1 - Số lượng vượt quá tồn kho hiện có.';
        }
        Database::pdo()->prepare('UPDATE cart_items SET quantity = :q WHERE id = :id')
            ->execute(['q' => $qty, 'id' => $cartItemId]);
        return null;
    }

    public static function remove(string $userId, string $cartItemId): void
    {
        if (self::ownedItem($userId, $cartItemId)) {
            Database::pdo()->prepare('DELETE FROM cart_items WHERE id = :id')->execute(['id' => $cartItemId]);
        }
    }

    /** Xóa toàn bộ giỏ (gọi trong transaction tạo đơn). */
    public static function clear(string $userId): void
    {
        Database::pdo()->prepare(
            'DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = :u'
        )->execute(['u' => $userId]);
    }

    private static function ownedItem(string $userId, string $cartItemId): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT ci.* FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE ci.id = :id AND c.user_id = :u'
        );
        $st->execute(['id' => $cartItemId, 'u' => $userId]);
        return $st->fetch() ?: null;
    }
}
