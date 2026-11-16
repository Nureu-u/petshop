</main>

<?php require BASE_PATH . '/views/chatbot/widget.php'; ?>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="<?= e(url('home')) ?>"><?= ui_icon('paw', 24, '2') ?> Pet Shop</a>
                <p>Hệ thống quản lý và kinh doanh thú cưng trực tuyến — Cam kết thú cưng thuần chủng, kiểm tra sức khỏe và tiêm phòng đầy đủ kèm phụ kiện chính hãng.</p>
            </div>
            <div>
                <h4>Khám phá cửa hàng</h4>
                <ul>
                    <li><a href="<?= e(url('pets')) ?>">Danh sách thú cưng</a></li>
                    <li><a href="<?= e(url('products')) ?>">Thức ăn & Phụ kiện</a></li>
                    <li><a href="<?= e(url('posts')) ?>">Cẩm nang chăm sóc</a></li>
                    <li><a href="<?= e(url('orders')) ?>">Tra cứu đơn hàng</a></li>
                </ul>
            </div>
            <div>
                <h4>Hỗ trợ khách hàng</h4>
                <ul>
                    <li>Địa chỉ: TP. Hồ Chí Minh</li>
                    <li>Email: support@petshop.local</li>
                    <li>Hotline tư vấn: 1900 6868 (08:00 – 21:00)</li>
                    <li>Thanh toán: COD & Chuyển khoản QR (SePay)</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">© <?= date('Y') ?> Pet Shop — Hệ thống Quản lý & Kinh doanh Thú cưng Trực tuyến</div>
    </div>
</footer>

<?php if (Auth::check()): ?>
<dialog class="modal" id="logout-modal">
    <div class="modal-box">
        <h3>Đăng xuất tài khoản?</h3>
        <p class="muted">Bạn có chắc muốn kết thúc phiên đăng nhập hiện tại?</p>
        <form method="post" action="<?= e(url('auth/logout')) ?>">
            <?= csrf_field() ?>
            <div class="modal-actions">
                <button type="button" class="btn ghost" data-modal-close>Hủy</button>
                <button type="submit" class="btn">Đăng xuất</button>
            </div>
        </form>
    </div>
</dialog>
<?php endif; ?>

</body>
</html>
