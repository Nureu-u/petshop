<?php
declare(strict_types=1);

final class CartController
{
    use JsonResponds;

    public function index(): void
    {
        Auth::requireLogin();
        $cartItems = Cart::items((string) Auth::id());
        $title = 'Giỏ hàng';
        view('cart/index', compact('cartItems', 'title'));
    }

    /** POST: item_type (pet|product), item_id, quantity, buy_now (1 = chuyển thẳng sang checkout) */
    public function add(): void
    {
        Auth::requireLogin();
        require_post();

        $err = Cart::add((string) Auth::id(), input('item_type'), input('item_id'), (int) input('quantity', '1'));
        if ($err !== null) {
            flash('error', $err);
            $this->backToItem();
        }
        if (input('buy_now') === '1') {
            redirect('checkout');
        }
        flash('success', 'Đã thêm vào giỏ hàng.');
        redirect('cart');
    }

    /** POST: item_id (cart_items.id), quantity. Hỗ trợ ajax=1 trả JSON. */
    public function update(): void
    {
        Auth::requireLogin();
        require_post();

        $uid = (string) Auth::id();
        $err = Cart::setQuantity($uid, input('item_id'), (int) input('quantity', '1'));

        if ($this->wantsJson()) {
            $items = Cart::items($uid);
            $this->json(['ok' => $err === null, 'error' => $err, 'subtotal' => Cart::subtotal($items), 'count' => count($items)]);
        }
        if ($err !== null) {
            flash('error', $err);
        }
        redirect('cart');
    }

    /** POST: item_id (cart_items.id). Hỗ trợ ajax=1 trả JSON. */
    public function remove(): void
    {
        Auth::requireLogin();
        require_post();

        $uid = (string) Auth::id();
        Cart::remove($uid, input('item_id'));

        if ($this->wantsJson()) {
            $items = Cart::items($uid);
            $this->json(['ok' => true, 'subtotal' => Cart::subtotal($items), 'count' => count($items)]);
        }
        flash('success', 'Đã xóa mặt hàng khỏi giỏ.');
        redirect('cart');
    }

    /** Thêm giỏ thất bại: quay lại trang chi tiết thú cưng / sản phẩm đang xem. */
    private function backToItem(): void
    {
        $type = input('item_type');
        $id = input('item_id');
        if ($id !== '' && in_array($type, ['pet', 'product'], true)) {
            redirect($type === 'pet' ? 'pets/show' : 'products/show', ['id' => $id]);
        }
        redirect('cart');
    }
}
