/* ==============================================================================
   JS: KHUNG CHAT NOI CHO KHACH HANG
   File    : public/js/chatbot.js
   Duoc nap boi: views/chatbot/widget.php
   Gui cau hoi bang fetch toi ?r=chatbot/ask (kem ma CSRF), hien cau tra loi va cac duong dan lien quan.
   Chu tu may chu luon hien bang textContent (khong chen HTML) nen khong bi tan cong XSS.
   ============================================================================== */
(function () {
    'use strict';
    var root = document.getElementById('pcb');
    if (!root) { return; }

    var panel = document.getElementById('pcbPanel');
    var openBtn = document.getElementById('pcbOpen');
    var body = document.getElementById('pcbBody');
    var form = document.getElementById('pcbForm');
    var input = document.getElementById('pcbInput');
    var send = document.getElementById('pcbSend');
    var chips = document.getElementById('pcbChips');
    var busy = false;
    var GREETING = 'Xin chào, mình là trợ lý của PetShop. Bạn cần tìm thú cưng, sản phẩm hay hỏi về đơn hàng?';

    function scrollDown() { body.scrollTop = body.scrollHeight; }

    function addMsg(text, who) {
        var d = document.createElement('div');
        d.className = 'pcb-msg ' + who;
        d.textContent = text;
        body.appendChild(d);
        scrollDown();
        return d;
    }

    function addSources(list) {
        if (!list || !list.length) { return; }
        var box = document.createElement('div');
        box.className = 'pcb-src';
        list.forEach(function (s) {
            var a = document.createElement('a');
            a.href = s.link;
            a.textContent = s.title;
            box.appendChild(a);
        });
        body.appendChild(box);
        scrollDown();
    }

    var LABEL = { breed: 'Giống', gender: 'Giới tính', age: 'Tuổi', weight: 'Cân nặng', brand: 'Thương hiệu', category: 'Danh mục', status: 'Tình trạng' };

    /** Hien moi thu cung / san pham thanh mot the: ten, cac thuoc tinh, gia, nut xem chi tiet. */
    function addItems(items) {
        if (!items || !items.length) { return; }
        var box = document.createElement('div');
        box.className = 'pcb-items';
        items.forEach(function (it) {
            var card = document.createElement('div'); card.className = 'pcb-item' + (it.big ? ' big' : '');
            if (it.image) {
                var pic = document.createElement('img');
                pic.className = 'pcb-pic'; pic.src = it.image; pic.alt = it.name; pic.loading = 'lazy';
                pic.addEventListener('load', scrollDown);
                pic.addEventListener('error', function () { var n = document.createElement('div'); n.className = 'pcb-nopic'; n.textContent = 'Không tải được ảnh'; n.title = it.image; pic.replaceWith(n); });   // anh hong thi bo qua, the van hien
                pic.style.cursor = 'zoom-in';
                pic.addEventListener('click', function () { window.open(it.image, '_blank', 'noopener'); });   // bam xem anh goc
                card.appendChild(pic);
            }
            if (it.big) {   // chi xem anh: chi hien anh + ten, khong hien bang thong tin
                var cap = document.createElement('div'); cap.className = 'pcb-item-head';
                var cn = document.createElement('b'); cn.textContent = it.name; cap.appendChild(cn);
                card.appendChild(cap); box.appendChild(card); return;
            }
            var head = document.createElement('div'); head.className = 'pcb-item-head';
            var name = document.createElement('b'); name.textContent = it.name;
            var price = document.createElement('span'); price.className = 'pcb-price'; price.textContent = it.cols.price;
            head.appendChild(name); head.appendChild(price); card.appendChild(head);
            var dl = document.createElement('dl');
            Object.keys(it.cols).forEach(function (k) {
                if (k === 'price' || !LABEL[k]) { return; }
                var dt = document.createElement('dt'); dt.textContent = LABEL[k];
                var dd = document.createElement('dd'); dd.textContent = it.cols[k];
                dl.appendChild(dt); dl.appendChild(dd);
            });
            card.appendChild(dl);
            if (it.link) {
                var a = document.createElement('a'); a.href = it.link; a.textContent = 'Xem chi tiết'; card.appendChild(a);
            }
            box.appendChild(card);
        });
        body.appendChild(box);
        scrollDown();
    }

    function typing(on) {
        var t = document.getElementById('pcbTyping');
        if (on && !t) {
            t = document.createElement('div');
            t.id = 'pcbTyping';
            t.className = 'pcb-typing';
            t.innerHTML = '<i></i><i></i><i></i>';
            body.appendChild(t);
            scrollDown();
        } else if (!on && t) {
            t.remove();
        }
    }

    function open(show) {
        panel.hidden = !show;
        openBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show) {
            if (!body.children.length) { addMsg(GREETING, 'bot'); }
            input.focus();
        } else {
            openBtn.focus();
        }
    }

    function ask(text) {
        text = (text || '').trim();
        if (!text || busy) { return; }
        busy = true;
        send.disabled = true;
        chips.classList.add('off');
        addMsg(text, 'me');
        input.value = '';
        typing(true);

        var fd = new FormData();
        fd.append('message', text);
        fd.append('_csrf', root.dataset.csrf);
        fetch(root.dataset.ask, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().catch(function () { return { error: 'Trợ lý đang gặp sự cố, bạn thử lại sau nhé.' }; }); })
            .then(function (d) {
                typing(false);
                if (d.error) { addMsg(d.error, 'bot'); return; }
                addMsg(d.answer, 'bot');
                addItems(d.items);
                addSources(d.sources);
            })
            .catch(function () {
                typing(false);
                addMsg('Không kết nối được với trợ lý. Bạn kiểm tra mạng rồi thử lại nhé.', 'bot');
            })
            .then(function () {
                busy = false;
                send.disabled = false;
                input.focus();
            });
    }

    openBtn.addEventListener('click', function () { open(panel.hidden); });
    document.getElementById('pcbClose').addEventListener('click', function () { open(false); });
    document.getElementById('pcbNew').addEventListener('click', function () {
        if (busy) { return; }
        var fd = new FormData();
        fd.append('_csrf', root.dataset.csrf);
        fetch(root.dataset.reset, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
        body.innerHTML = '';
        addMsg(GREETING, 'bot');
        chips.classList.remove('off');
        input.focus();
    });
    form.addEventListener('submit', function (ev) { ev.preventDefault(); ask(input.value); });
    chips.addEventListener('click', function (ev) {
        if (ev.target.tagName === 'BUTTON') { ask(ev.target.textContent); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !panel.hidden) { open(false); }
    });
})();
