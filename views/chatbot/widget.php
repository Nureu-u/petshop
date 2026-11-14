<?php
/* ==============================================================================
   VIEW: KHUNG CHAT NOI CHO KHACH HANG
   File    : views/chatbot/widget.php
   Cach dung: dat DUNG 1 DONG nay vao footer trang khach (views/layout/footer.php), truoc </body>:
              <?php require BASE_PATH . '/views/chatbot/widget.php'; ?>
   Controller: controllers/ChatbotController.php   (tuyen chatbot/ask, chatbot/reset)
   CSS / JS : public/css/chatbot.css, public/js/chatbot.js
   File nay tu day du: khong dung ham cua khu admin nen chay duoc o moi trang khach.
   ============================================================================== */
$cbVer = static function (string $p): string {
    $f = BASE_PATH . '/' . $p;
    return asset($p) . '?v=' . (is_file($f) ? (int) filemtime($f) : 0);
};
$cbLoggedIn = Auth::check();
?>
<link rel="stylesheet" href="<?= e($cbVer('public/css/chatbot.css')) ?>">
<div id="pcb" class="pcb" data-ask="<?= e(url('chatbot/ask')) ?>" data-reset="<?= e(url('chatbot/reset')) ?>"
     data-csrf="<?= e(csrf_token()) ?>" data-login="<?= $cbLoggedIn ? '1' : '0' ?>">
    <button type="button" class="pcb-fab" id="pcbOpen" aria-label="Mở trợ lý PetShop" aria-expanded="false" aria-controls="pcbPanel">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5z"/><path d="M9 10h.01M15 10h.01"/></svg>
    </button>
    <section class="pcb-panel" id="pcbPanel" role="dialog" aria-label="Trợ lý PetShop" hidden>
        <header class="pcb-head">
            <div><b>Trợ lý PetShop</b><small>Hỏi về thú cưng, sản phẩm, giá, đơn hàng</small></div>
            <div class="pcb-tools">
                <button type="button" id="pcbNew" aria-label="Cuộc trò chuyện mới" title="Cuộc trò chuyện mới">↺</button>
                <button type="button" id="pcbClose" aria-label="Đóng">✕</button>
            </div>
        </header>
        <div class="pcb-body" id="pcbBody" aria-live="polite"></div>
        <div class="pcb-chips" id="pcbChips">
            <button type="button">Giá thú cưng đang bán</button>
            <button type="button">Cách chăm sóc mèo con</button>
            <button type="button">Chính sách đổi trả</button>
            <button type="button">Đơn hàng của tôi</button>
        </div>
        <form class="pcb-form" id="pcbForm" autocomplete="off">
            <input id="pcbInput" name="message" maxlength="500" placeholder="Nhập câu hỏi của bạn…" aria-label="Câu hỏi" required>
            <button type="submit" id="pcbSend" aria-label="Gửi">Gửi</button>
        </form>
    </section>
</div>
<script src="<?= e($cbVer('public/js/chatbot.js')) ?>" defer></script>
