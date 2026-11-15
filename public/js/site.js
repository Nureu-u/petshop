/* PetShop - JS giao diện khách (TV1): modal, menu mobile, dropdown, đóng thông báo. */
document.addEventListener('click', function (e) {
    var open = e.target.closest('[data-modal-open]');
    if (open) {
        var dlg = document.getElementById(open.getAttribute('data-modal-open'));
        if (dlg && dlg.showModal) { dlg.showModal(); }
        return;
    }
    var close = e.target.closest('[data-modal-close]');
    if (close) { var d = close.closest('dialog'); if (d) { d.close(); } return; }
    // bấm vào nền mờ (chính thẻ dialog) thì đóng
    if (e.target.tagName === 'DIALOG') { e.target.close(); return; }

    var toggle = e.target.closest('[data-nav-toggle]');
    if (toggle) {
        var isOpen = document.body.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        return;
    }
    var dismiss = e.target.closest('[data-dismiss]');
    if (dismiss) { dismiss.closest('.alert').remove(); return; }

    // bấm ra ngoài thì đóng menu người dùng
    document.querySelectorAll('details.dropdown[open]').forEach(function (dd) {
        if (!dd.contains(e.target)) { dd.removeAttribute('open'); }
    });
});
