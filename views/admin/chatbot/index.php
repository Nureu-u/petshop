<?php
/* ==============================================================================
   VIEW: QUAN LY CHATBOT
   File    : views/admin/chatbot/index.php
   Dia chi  : ?r=admin/chatbot
   Controller: controllers/AdminChatbotController.php  (ham index)
   Model   : models/Chatbot.php
   CSS      : public/css/admin-ui.css, nhom [5] HANG TIEU DE CUA TRANG; [7] THE TRANG; [10] NUT
           BAM; [12] FORM NHAP LIEU; [13] BANG DANH SACH, NHAN TRANG THAI VA PHAN TRANG.
   Cac khoi: 5 khoi, danh dau bang cac dong "----- KHOI n: ... -----" ben duoi.
   ============================================================================== */
?>
<?php
/** @var array $stats @var array $faqs @var array $recent @var bool $aiOn @var ?array $edit @var string $model */
$fmt = fn($d) => date('d/m H:i', strtotime((string) $d));
$modeLabel = ['ai' => 'chatbot.test.mode.ai', 'fallback' => 'chatbot.test.mode.fallback', 'blocked' => 'chatbot.test.mode.blocked'];
$modeClass = ['ai' => 'gr', 'fallback' => 'y', 'blocked' => 'gy'];
?>
<?php /* ----- KHOI 1: Tieu de trang va nut cap nhat tri thuc ----- */ ?>
<div class="top">
    <div>
        <h1><?= e(t('nav.chatbot')) ?></h1>
        <div class="sub"><?= e(t('chatbot.sub')) ?></div>
    </div>
    <form method="post" action="<?= e(url('admin/chatbot/reindex')) ?>" style="margin:0">
        <?= csrf_field() ?>
        <button class="b1" type="submit"><?= e(t('chatbot.reindex')) ?></button>
    </form>
</div>

<?php /* ----- KHOI 2: The tom tat ----- */ ?>
<div class="c sum">
    <div>
        <div class="v"><span class="p <?= $aiOn ? 'gr' : 'y' ?>"><?= e(t($aiOn ? 'chatbot.ai.on' : 'chatbot.ai.off')) ?></span></div>
        <div class="hint"><?= e($aiOn ? t('chatbot.ai.model', ['m' => $model]) : t('chatbot.ai.fallback_note')) ?></div>
    </div>
    <div>
        <div class="v"><?= (int) $stats['knowledge'] ?></div>
        <div class="hint"><?= e(t('chatbot.stat.knowledge', ['n' => $stats['embedded']])) ?></div>
    </div>
    <div>
        <div class="v"><?= (int) $stats['week'] ?></div>
        <div class="hint"><?= e(t('chatbot.stat.week', ['a' => $stats['week_ai'], 'f' => $stats['week_fallback']])) ?></div>
    </div>
</div>
<?php if (!$aiOn): ?>
    <div class="warn" style="margin-bottom:14px"><?= e(t('chatbot.ai_off_note')) ?></div>
<?php endif; ?>

<?php /* ----- KHOI 3: Hoi thu chatbot (khung tro chuyen, hoi tiep duoc, nho 6 luot gan nhat) ----- */ ?>
<div class="c">
    <div class="cbhead">
        <h2><?= e(t('chatbot.test.title')) ?></h2>
        <button type="button" class="b2 sm" id="cbNew"><?= e(t('chatbot.chat.new')) ?></button>
    </div>
    <div class="cbchat" id="cbChat" aria-live="polite">
        <div class="cbempty" id="cbEmpty"><?= e(t('chatbot.chat.empty')) ?></div>
    </div>
    <form class="fl" id="cbTest" autocomplete="off" style="margin-top:12px">
        <input class="grow" name="message" maxlength="500" placeholder="<?= e(t('chatbot.test.ph')) ?>" aria-label="<?= e(t('chatbot.test.title')) ?>" required>
        <button class="b1" type="submit" id="cbSend"><?= e(t('chatbot.test.btn')) ?></button>
    </form>
</div>
<script>
(function () {
    var f = document.getElementById('cbTest'); if (!f) return;
    var chat = document.getElementById('cbChat'), empty = document.getElementById('cbEmpty');
    var input = f.elements.message, sendBtn = document.getElementById('cbSend');
    var T = <?= json_encode([
        'csrf' => csrf_token(), 'url' => url('admin/chatbot/test'),
        'ai' => t('chatbot.test.mode.ai'), 'fb' => t('chatbot.test.mode.fallback'), 'bl' => t('chatbot.test.mode.blocked'),
        'col' => [
            'name' => t('chatbot.col.name'), 'breed' => t('chatbot.col.breed'), 'gender' => t('chatbot.col.gender'),
            'age' => t('chatbot.col.age'), 'weight' => t('chatbot.col.weight'), 'price' => t('chatbot.col.price'),
            'status' => t('chatbot.col.status'), 'brand' => t('chatbot.col.brand'), 'category' => t('chatbot.col.category'),
        ],
        'tpet' => t('chatbot.tbl.pets'), 'tprod' => t('chatbot.tbl.products'),
        'wait' => t('chatbot.test.wait'), 'fail' => t('chatbot.test.fail'), 'none' => t('chatbot.test.none'), 'err' => t('chatbot.test.err'),
        'hits' => t('chatbot.test.hits'), 'detail' => t('chatbot.chat.detail'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var busy = false, hist = [], shown = [];

    function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }
    function down() { chat.scrollTop = chat.scrollHeight; }

    // Ve bang ket qua: mot bang cho thu cung, mot bang cho san pham (chi dung textContent, khong chen HTML)
    function renderTables(box, items) {
        // Khach xin xem anh: hien anh lon (bam vao de mo anh goc)
        var big = items.filter(function (i) { return i.big && i.image; });
        if (big.length) {
            var wrap = el('div'); wrap.style.cssText = 'display:flex;flex-wrap:wrap;gap:10px;margin:10px 0';
            big.forEach(function (i) {
                var f = el('figure'); f.style.cssText = 'margin:0;max-width:320px';
                var im = el('img'); im.src = i.image; im.alt = i.name; im.title = 'Bấm để xem ảnh gốc';
                im.style.cssText = 'width:100%;border-radius:12px;cursor:zoom-in;display:block';
                im.onclick = function () { window.open(i.image, '_blank', 'noopener'); };
                im.onerror = function () { im.replaceWith(el('div', 'hint', 'Không tải được ảnh: ' + i.image)); };
                f.appendChild(im); f.appendChild(el('figcaption', 'hint', i.name)); wrap.appendChild(f);
            });
            box.appendChild(wrap);
            return;   // xin xem anh thi chi hien anh, khong ve bang
        }
        [['pet', T.tpet, ['name', 'breed', 'gender', 'age', 'weight', 'price', 'status']],
         ['product', T.tprod, ['name', 'brand', 'category', 'price', 'status']]].forEach(function (g) {
            var rows = items.filter(function (i) { return i.kind === g[0]; });
            if (!rows.length) { return; }
            var h = el('div', 'hint', g[1] + ' (' + rows.length + ')'); h.style.cssText = 'margin:10px 0 6px;font-weight:700';
            box.appendChild(h);
            var tb = el('table', 'tb'), thead = el('thead'), tr = el('tr');
            g[2].forEach(function (c) { tr.appendChild(el('th', '', T.col[c])); });
            thead.appendChild(tr); tb.appendChild(thead);
            var tbody = el('tbody');
            rows.forEach(function (r) {
                var row = el('tr');
                g[2].forEach(function (c) {
                    var td = el('td'); td.setAttribute('data-label', T.col[c]);
                    var v = c === 'name' ? r.name : r.cols[c];
                    if (c === 'name') {
                        if (r.image) { var im = el('img', 'cbthumb'); im.src = r.image; im.alt = ''; im.loading = 'lazy'; im.onerror = function () { im.removeAttribute('src'); im.classList.add('cbthumb-bad'); im.title = 'Không tải được ảnh: ' + r.image; }; im.style.cursor = 'zoom-in'; im.onclick = function () { window.open(r.image, '_blank', 'noopener'); }; td.appendChild(im); }
                        td.appendChild(el('b', '', v));
                    } else if (c === 'price') { td.appendChild(el('b', '', v)); } else { td.textContent = v; }
                    row.appendChild(td);
                });
                tbody.appendChild(row);
            });
            tb.appendChild(tbody); box.appendChild(tb);
        });
    }

    function fill(bot, d) {
        bot.innerHTML = '';
        bot.appendChild(el('div', 'cbtext', d.answer));
        renderTables(bot, d.items || []);
        var info = (d.mode === 'ai' ? T.ai : d.mode === 'fallback' ? T.fb : T.bl) + (d.reason ? ' - ' + T.err + ' ' + d.reason : '');
        bot.appendChild(el('div', 'hint cbmode', info));
        var det = el('details', 'cbdet'); det.appendChild(el('summary', '', T.detail));
        det.appendChild(el('div', 'hint', T.hits));
        var ul = el('ul', 'hint');
        if (!d.hits.length) { ul.appendChild(el('li', '', T.none)); }
        d.hits.forEach(function (h) { ul.appendChild(el('li', '', h.title + ' (' + h.score + ')')); });
        det.appendChild(ul); bot.appendChild(det);
    }

    function reset() { hist = []; shown = []; chat.innerHTML = ''; chat.appendChild(empty); empty.hidden = false; input.focus(); }
    document.getElementById('cbNew').addEventListener('click', function () { if (!busy) { reset(); } });

    f.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var q = input.value.trim();
        if (busy || !q) { return; }
        busy = true; sendBtn.disabled = true; empty.hidden = true;
        chat.appendChild(el('div', 'cbmsg me', q));
        var bot = el('div', 'cbmsg bot', T.wait); chat.appendChild(bot); down();
        input.value = '';
        var fd = new FormData(); fd.append('message', q); fd.append('_csrf', T.csrf); fd.append('history', JSON.stringify(hist)); fd.append('shown', JSON.stringify(shown));
        fetch(T.url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) { bot.textContent = d.error; return; }
                fill(bot, d);
                hist.push({ role: 'user', text: q }, { role: 'model', text: d.answer });
                hist = hist.slice(-12);
                (d.items || []).forEach(function (i) { shown.push(i.name); });
            })
            .catch(function () { bot.textContent = T.fail; })
            .then(function () { busy = false; sendBtn.disabled = false; input.focus(); down(); });
    });
})();
</script>

<?php /* ----- KHOI 4: Cau hoi thuong gap (chinh sach cua hang) ----- */ ?>
<div class="c" id="faq">
    <h2><?= e(t('chatbot.faq.title')) ?></h2>
    <div class="hint" style="margin-bottom:10px"><?= e(t('chatbot.faq.sub')) ?></div>
    <table class="tb">
        <thead><tr><th><?= e(t('chatbot.faq.col.q')) ?></th><th><?= e(t('chatbot.faq.col.a')) ?></th><th><?= e(t('chatbot.faq.col.status')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($faqs as $f): ?>
            <?php $off = $f['status'] === 'hidden'; ?>
            <tr>
                <td data-label="<?= e(t('chatbot.faq.col.q')) ?>"><b><?= e($f['title']) ?></b></td>
                <td data-label="<?= e(t('chatbot.faq.col.a')) ?>" style="max-width:380px"><?= e(mb_strimwidth($f['content'], 0, 140, '…')) ?></td>
                <td data-label="<?= e(t('chatbot.faq.col.status')) ?>"><span class="p <?= $off ? 'gy' : 'gr' ?>"><?= e(t($off ? 'chatbot.status.hidden' : 'chatbot.status.active')) ?></span></td>
                <td data-label="">
                    <a class="lk" href="<?= e(url('admin/chatbot', ['edit' => $f['id']])) ?>#faqform"><?= e(t('chatbot.faq.edit_btn')) ?></a>
                    <form method="post" action="<?= e(url('admin/chatbot/faq/toggle')) ?>" style="margin:6px 0 0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                        <button class="<?= $off ? 'b2' : 'b3' ?> sm" type="submit"><?= e(t($off ? 'chatbot.faq.show' : 'chatbot.faq.hide')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$faqs): ?><tr><td colspan="5" class="hint"><?= e(t('chatbot.faq.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<form class="c fm" id="faqform" method="post" action="<?= e(url('admin/chatbot/faq')) ?>" style="max-width:820px">
    <?= csrf_field() ?>
    <h2><?= e(t($edit ? 'chatbot.faq.edit' : 'chatbot.faq.new')) ?></h2>
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
    <label><?= e(t('chatbot.faq.f.q')) ?>
        <input name="question" maxlength="200" value="<?= e($edit['title'] ?? '') ?>" required>
    </label>
    <label><?= e(t('chatbot.faq.f.a')) ?>
        <textarea name="answer" rows="4" maxlength="3000" required><?= e($edit['content'] ?? '') ?></textarea>
    </label>
    <label><?= e(t('chatbot.faq.f.status')) ?>
        <select name="status">
            <option value="active"<?= sel($edit['status'] ?? 'active', 'active') ?>><?= e(t('chatbot.status.active')) ?></option>
            <option value="hidden"<?= sel($edit['status'] ?? 'active', 'hidden') ?>><?= e(t('chatbot.status.hidden')) ?></option>
        </select>
    </label>
    <div class="rr">
        <button class="b1" type="submit"><?= e(t('chatbot.faq.save')) ?></button>
        <?php if ($edit): ?><a class="b2" href="<?= e(url('admin/chatbot')) ?>"><?= e(t('common.cancel')) ?></a><?php endif; ?>
    </div>
</form>

<?php /* ----- KHOI 5: Hoi dap gan day - moi khach la mot dong, bam vao de xem ca cuoc tro chuyen ----- */ ?>
<div class="c">
    <h2><?= e(t('chatbot.log.title')) ?></h2>
    <table class="tb">
        <thead><tr><th><?= e(t('chatbot.log.col.time')) ?></th><th><?= e(t('chatbot.log.col.user')) ?></th><th><?= e(t('chatbot.log.col.count')) ?></th><th><?= e(t('chatbot.log.col.conv')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $g): ?>
            <tr>
                <td data-label="<?= e(t('chatbot.log.col.time')) ?>"><?= e($fmt($g['last_at'])) ?></td>
                <td data-label="<?= e(t('chatbot.log.col.user')) ?>"><?php if (!empty($g['user_id'])): ?><a href="<?= e(url('admin/users/show', ['id' => $g['user_id']])) ?>"><b><?= e($g['user_id']) ?></b></a><?php else: ?><span class="hint"><?= e(t('chatbot.log.guest')) ?> (<?= e(substr($g['key'], 0, 6)) ?>)</span><?php endif; ?></td>
                <td data-label="<?= e(t('chatbot.log.col.count')) ?>"><?= (int) $g['count'] ?></td>
                <td data-label="<?= e(t('chatbot.log.col.conv')) ?>" style="max-width:640px">
                    <details>
                        <summary style="cursor:pointer;font-weight:600"><?= e(t('chatbot.log.view')) ?> (<?= (int) $g['count'] ?>)</summary>
                        <?php foreach ($g['messages'] as $m): ?>
                            <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e5ded3">
                                <div class="hint"><?= e($fmt($m['created_at'])) ?></div>
                                <div style="white-space:pre-wrap"><b><?= e(t('chatbot.log.you')) ?>:</b> <?= e($m['question']) ?></div>
                                <div style="white-space:pre-wrap;margin-top:4px"><b><?= e(t('chatbot.log.bot')) ?>:</b> <?= e($m['answer']) ?>
                                    <span class="p <?= e($modeClass[$m['mode']] ?? 'gy') ?>"><?= e(t($modeLabel[$m['mode']] ?? 'chatbot.test.mode.blocked')) ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </details>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="4" class="hint"><?= e(t('chatbot.log.empty')) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="note"><?= e(t('chatbot.note')) ?></div>
