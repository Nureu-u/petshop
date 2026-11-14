/* ==============================================================================
   PETSHOP ADMIN - FILE JAVASCRIPT CUA TRANG QUAN TRI
   File nay duoc nap o cuoi moi trang admin (views/admin/layout/footer.php) va trang dang nhap.

   CACH DOC FILE NAY
   - File chia thanh cac MUC danh so. Moi muc co khung ghi chu cho biet:
       Hien o  : muc nay lam thay doi phan nao tren man hinh.
       Chay o  : trang / file PHP nao can den muc nay.
   - Moi muc la mot khoi doc lap, hong mot muc khong lam hong muc khac.

   MUC LUC
     1. Giao dien chung: doi sang/toi, menu tha xuong, menu dien thoai, phim tat
     2. Thong bao noi tu an
     3. So tren Tong quan chay tu 0 len gia tri that
     4. O chon trong thanh loc tu ap dung ngay
     5. Ghi nho da xem hieu ung Tong quan
     6. Bo chon ngay gio rieng (lich bo goc, tieng Viet, gio 24h)
     7. Nut con mat hien / an mat khau
     8. Thong bao loi nhap lieu bang tieng Viet
     9. O nhap tien tu them dau cham (10.000)
    10. Hop xac nhan "Ban co chac khong?"
    11. Giu nguyen vi tri cuon khi bam nut chuyen (Ban chay nhat: San pham | Thu cung)
   ============================================================================== */

(function () {
  var root = document.documentElement;


  /* ----------------------------------------------------------------------------
     [MUC 1] GIAO DIEN CHUNG
     Hien o  : nut mat troi / mat trang, cac menu tha xuong (chuong, ten admin),
               menu truot tren dien thoai, phim tat "/" nhay vao o tim kiem.
     Chay o  : views/admin/layout/header.php (cac thuoc tinh data-theme-set, data-dd,
               data-dd-toggle, data-menu-toggle, data-menu-close).
     ---------------------------------------------------------------------------- */

  // Doi giao dien sang / toi va nho lua chon trong trinh duyet
  function setTheme(theme) {
    root.setAttribute('data-theme', theme);
    try {
      localStorage.setItem('ps_admin_theme', theme);
    } catch (e) {}
    document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
      btn.classList.toggle('on', btn.getAttribute('data-theme-set') === theme);
    });
  }
  setTheme(root.getAttribute('data-theme') || 'light');

  // Dong moi menu tha xuong (tru menu dang bam vao)
  function closeDropdowns(except) {
    document.querySelectorAll('[data-dd].open').forEach(function (dd) {
      if (dd !== except) {
        dd.classList.remove('open');
      }
    });
  }

  // Mot cho xu ly moi cu bam: menu tha xuong, nut sang/toi, nut 3 gach, lop mo
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-dd-toggle]');
    var dropdown = toggle ? toggle.closest('[data-dd]') : null;
    closeDropdowns(dropdown);
    if (dropdown) {
      dropdown.classList.toggle('open');
      return;
    }

    var themeBtn = e.target.closest('[data-theme-set]');
    if (themeBtn) {
      setTheme(themeBtn.getAttribute('data-theme-set'));
      return;
    }
    var menuBtn = e.target.closest('[data-menu-toggle]');
    if (menuBtn) {
      var isOpen = document.body.classList.toggle('menu-open');
      menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      return;
    }
    if (e.target.closest('[data-menu-close]')) {
      document.body.classList.remove('menu-open');
      document.querySelectorAll('[data-menu-toggle]').forEach(function (btn) {
        btn.setAttribute('aria-expanded', 'false');
      });
    }
  });

  // Phim Esc dong menu; phim "/" nhay vao o tim kiem chung
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeDropdowns(null);
      document.body.classList.remove('menu-open');
      return;
    }
    var tag = (document.activeElement && document.activeElement.tagName) || '';
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag)) {
      var search = document.querySelector('.adm-search input[type="search"]');
      if (search) {
        e.preventDefault();
        search.focus();
      }
    }
  });


  /* ----------------------------------------------------------------------------
     [MUC 2] THONG BAO NOI TU AN
     Hien o  : khung "Da luu...", "Da cap nhat..." o goc phai tren.
     Chay o  : views/admin/layout/header.php (khoi .toasts). Sau 6 giay them class "hide".
     ---------------------------------------------------------------------------- */
  setTimeout(function () {
    document.querySelectorAll('.flash').forEach(function (flash) {
      flash.classList.add('hide');
    });
  }, 6000);


  /* ----------------------------------------------------------------------------
     [MUC 3] SO TREN TONG QUAN CHAY TU 0 LEN GIA TRI THAT
     Hien o  : cac so trong the so lieu va cac o "can xu ly ngay" cua trang Tong quan.
     Chay o  : views/admin/dashboard/index.php (phan tu co thuoc tinh data-count).
               Khong chay neu nguoi dung bat "giam chuyen dong" hoac da xem Tong quan (muc 5).
     ---------------------------------------------------------------------------- */
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduceMotion && !root.classList.contains('no-anim')) {
    document.querySelectorAll('[data-count]').forEach(function (el) {
      var end = parseInt(el.getAttribute('data-count'), 10);
      if (!isFinite(end) || end <= 0) {
        return;
      }
      var start = null;
      el.textContent = '0';
      function step(ts) {
        if (start === null) {
          start = ts;
        }
        var progress = Math.min(1, (ts - start) / 700);
        el.textContent = String(Math.round(end * (1 - Math.pow(1 - progress, 3))));
        if (progress < 1) {
          requestAnimationFrame(step);
        }
      }
      requestAnimationFrame(step);
    });
  }


  /* ----------------------------------------------------------------------------
     [MUC 4] O CHON TRONG THANH LOC TU AP DUNG NGAY
     Hien o  : cac o chon (Sap xep, Danh muc, Thanh toan, Trang thai...) phia tren bang.
     Chay o  : moi form loc dung method="get" (orders, users, coupons, reviews, posts, pets, products).
               Doi o chon la trang tu tai lai theo bo loc, khong can bam nut "Loc".
     ---------------------------------------------------------------------------- */
  document.addEventListener('change', function (e) {
    var select = e.target;
    var isFilterSelect = select.tagName === 'SELECT' && select.form
      && (select.form.getAttribute('method') || '').toLowerCase() === 'get';
    if (isFilterSelect) {
      select.form.submit();
    }
  });


  /* ----------------------------------------------------------------------------
     [MUC 5] GHI NHO DA XEM HIEU UNG TONG QUAN
     Hien o  : khong hien gi; chi de lan sau vao Tong quan khong chay lai hieu ung cot moc / so chay.
     Chay o  : views/admin/dashboard/index.php (co phan tu .plot). Co nay duoc doc lai o
               views/admin/layout/header.php de gan class "no-anim".
     ---------------------------------------------------------------------------- */
  try {
    if (document.querySelector('.plot')) {
      sessionStorage.setItem('ps_dash_anim', '1');
    }
  } catch (e) {}


  /* ----------------------------------------------------------------------------
     [MUC 6] BO CHON NGAY GIO RIENG (lich bo goc, tieng Viet/Anh theo ngon ngu trang, gio 24h)
     Hien o  : moi o ngay: Bat dau / Ket thuc ma giam gia, Ngay sinh thu cung, Tu ngay / Den
               ngay o loc don hang.
     Chay o  : views/admin/coupons/form.php, catalog/pets/form.php, orders/index.php. Tu thay
               moi <input type="date"> va <input type="datetime-local"> bang o rieng; gia tri
               gui len may chu van dang chuan (2026-11-07 hoac 2026-11-07T00:00).
     ---------------------------------------------------------------------------- */

  (function () {
    var isEn = (document.documentElement.lang || 'vi').toLowerCase().indexOf('en') === 0;
    var T = isEn
      ? { months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
          wd: ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'], clear: 'Clear', today: 'Today', done: 'Done', hour: 'Hour', minute: 'Min', prev: 'Previous month', next: 'Next month' }
      : { months: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
          wd: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'], clear: 'Xóa', today: 'Hôm nay', done: 'Xong', hour: 'Giờ', minute: 'Phút', prev: 'Tháng trước', next: 'Tháng sau' };

    /* HELPERS START */
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function parse(v) {
      var m = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/.exec(v || '');
      if (!m) { return null; }
      return { y: +m[1], mo: +m[2], d: +m[3], h: m[4] ? +m[4] : 0, mi: m[5] ? +m[5] : 0 };
    }
    function toIso(o, withTime) {
      var s = o.y + '-' + pad(o.mo) + '-' + pad(o.d);
      return withTime ? s + 'T' + pad(o.h) + ':' + pad(o.mi) : s;
    }
    function toShow(o, withTime) {
      var s = pad(o.d) + '/' + pad(o.mo) + '/' + o.y;
      return withTime ? s + ' ' + pad(o.h) + ':' + pad(o.mi) : s;
    }
    function daysIn(y, mo) { return new Date(y, mo, 0).getDate(); }
    function firstDow(y, mo) { return (new Date(y, mo - 1, 1).getDay() + 6) % 7; }
    function cmp(a, b) { return (a.y - b.y) || (a.mo - b.mo) || (a.d - b.d); }
    function grid(y, mo) {
      var cells = [], off = firstDow(y, mo), dim = daysIn(y, mo);
      var py = mo === 1 ? y - 1 : y, pm = mo === 1 ? 12 : mo - 1, pd = daysIn(py, pm);
      var ny = mo === 12 ? y + 1 : y, nm = mo === 12 ? 1 : mo + 1;
      for (var i = 0; i < 42; i++) {
        var d = i - off + 1;
        if (d < 1) { cells.push({ y: py, mo: pm, d: pd + d, out: true }); }
        else if (d > dim) { cells.push({ y: ny, mo: nm, d: d - dim, out: true }); }
        else { cells.push({ y: y, mo: mo, d: d, out: false }); }
      }
      return cells;
    }
    /* HELPERS END */

    var pop = null, ctx = null;

    function nowObj() { var n = new Date(); return { y: n.getFullYear(), mo: n.getMonth() + 1, d: n.getDate(), h: 0, mi: 0 }; }
    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (text !== undefined) { e.textContent = text; } return e; }

    function commit() {
      if (!ctx) { return; }
      var v = ctx.sel ? toIso(ctx.sel, ctx.withTime) : '';
      ctx.hidden.value = v;
      ctx.display.value = ctx.sel ? toShow(ctx.sel, ctx.withTime) : '';
      ctx.hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function close() {
      if (pop) { pop.remove(); pop = null; }
      ctx = null;
    }
    function place() {
      if (!pop || !ctx) { return; }
      var r = ctx.display.getBoundingClientRect(), pw = pop.offsetWidth, ph = pop.offsetHeight;
      var sx = window.pageXOffset, sy = window.pageYOffset, vw = document.documentElement.clientWidth;
      var left = Math.max(sx + 8, Math.min(r.left + sx, sx + vw - pw - 8));
      var top = r.bottom + sy + 8;
      if (r.bottom + ph + 12 > window.innerHeight && r.top - ph - 12 > 0) { top = r.top + sy - ph - 8; }
      pop.style.left = left + 'px';
      pop.style.top = top + 'px';
    }
    function inRange(o) {
      return !((ctx.min && cmp(o, ctx.min) < 0) || (ctx.max && cmp(o, ctx.max) > 0));
    }
    function pick(o) {
      var h = ctx.sel ? ctx.sel.h : 0, mi = ctx.sel ? ctx.sel.mi : 0;
      ctx.sel = { y: o.y, mo: o.mo, d: o.d, h: h, mi: mi };
      ctx.vy = o.y; ctx.vm = o.mo;
      commit();
      if (!ctx.withTime) { close(); return; }
      renderCal(); syncTime();
    }
    function setTime(key, val) {
      if (!ctx.sel) { var n = nowObj(); ctx.sel = n; ctx.vy = n.y; ctx.vm = n.mo; }
      ctx.sel[key] = val;
      commit(); renderCal(); syncTime();
    }
    function shift(delta) {
      var m = ctx.vm + delta, y = ctx.vy;
      if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
      ctx.vm = m; ctx.vy = y; renderCal();
    }

    function renderCal() {
      var box = pop.querySelector('.dp-cal');
      box.textContent = '';
      var head = el('div', 'dp-head');
      var prev = el('button', 'dp-nav', '‹'); prev.type = 'button'; prev.setAttribute('aria-label', T.prev);
      prev.addEventListener('click', function () { shift(-1); });
      var next = el('button', 'dp-nav', '›'); next.type = 'button'; next.setAttribute('aria-label', T.next);
      next.addEventListener('click', function () { shift(1); });
      var ms = el('select'); T.months.forEach(function (n, i) { var o = el('option', '', n); o.value = i + 1; if (i + 1 === ctx.vm) { o.selected = true; } ms.appendChild(o); });
      ms.addEventListener('change', function () { ctx.vm = +ms.value; renderCal(); });
      var ys = el('select'), y0 = ctx.min ? ctx.min.y : nowObj().y - 100, y1 = ctx.max ? ctx.max.y : nowObj().y + 20;
      if (ctx.vy < y0) { y0 = ctx.vy; } if (ctx.vy > y1) { y1 = ctx.vy; }
      for (var y = y1; y >= y0; y--) { var o = el('option', '', String(y)); o.value = y; if (y === ctx.vy) { o.selected = true; } ys.appendChild(o); }
      ys.addEventListener('change', function () { ctx.vy = +ys.value; renderCal(); });
      head.appendChild(prev); head.appendChild(ms); head.appendChild(ys); head.appendChild(next);
      box.appendChild(head);

      var g = el('div', 'dp-grid');
      T.wd.forEach(function (w) { g.appendChild(el('div', 'dp-wd', w)); });
      var t = nowObj();
      grid(ctx.vy, ctx.vm).forEach(function (c) {
        var b = el('button', 'dp-day' + (c.out ? ' out' : '') + (cmp(c, t) === 0 ? ' today' : '') + (ctx.sel && cmp(c, ctx.sel) === 0 ? ' sel' : ''), String(c.d));
        b.type = 'button';
        if (!inRange(c)) { b.disabled = true; }
        b.addEventListener('click', function () { pick(c); });
        g.appendChild(b);
      });
      box.appendChild(g);

      var foot = el('div', 'dp-foot');
      var clr = el('button', 'dp-link', T.clear); clr.type = 'button';
      clr.addEventListener('click', function () { ctx.sel = null; commit(); close(); });
      var td = el('button', 'dp-link', T.today); td.type = 'button';
      td.addEventListener('click', function () { var n = nowObj(); if (inRange(n)) { pick(n); } });
      foot.appendChild(clr); foot.appendChild(td);
      box.appendChild(foot);
      place();
    }

    function buildTime() {
      var wrap = el('div', 'dp-time'), cols = el('div', 'dp-tcols');
      [['h', 24, T.hour], ['mi', 60, T.minute]].forEach(function (spec) {
        var c = el('div', 'dp-tcol'); c.appendChild(el('div', 'dp-colh', spec[2]));
        var list = el('div', 'dp-col'); list.setAttribute('data-key', spec[0]);
        for (var i = 0; i < spec[1]; i++) {
          (function (v) {
            var b = el('button', 'dp-opt', pad(v)); b.type = 'button'; b.setAttribute('data-v', v);
            b.addEventListener('click', function () { setTime(spec[0], v); });
            list.appendChild(b);
          })(i);
        }
        c.appendChild(list); cols.appendChild(c);
      });
      wrap.appendChild(cols);
      var quick = el('div', 'dp-quick');
      [['00:00', 0, 0], ['12:00', 12, 0], ['23:59', 23, 59]].forEach(function (q) {
        var b = el('button', 'dp-chip', q[0]); b.type = 'button';
        b.addEventListener('click', function () { setTime('h', q[1]); setTime('mi', q[2]); });
        quick.appendChild(b);
      });
      wrap.appendChild(quick);
      var ok = el('button', 'dp-link dp-done', T.done); ok.type = 'button';
      ok.addEventListener('click', close);
      wrap.appendChild(ok);
      return wrap;
    }
    function syncTime() {
      if (!ctx.withTime) { return; }
      pop.querySelectorAll('.dp-col').forEach(function (list) {
        var key = list.getAttribute('data-key'), cur = ctx.sel ? ctx.sel[key] : -1, hit = null;
        list.querySelectorAll('.dp-opt').forEach(function (b) {
          var on = +b.getAttribute('data-v') === cur;
          b.classList.toggle('sel', on);
          if (on) { hit = b; }
        });
        if (hit) { list.scrollTop = Math.max(0, hit.offsetTop - list.clientHeight / 2 + 16); }
      });
    }

    function open(c) {
      if (ctx && ctx.display === c.display) { close(); return; }
      close();
      ctx = c;
      var base = ctx.sel || nowObj();
      ctx.vy = base.y; ctx.vm = base.mo;
      pop = el('div', 'dp-pop'); pop.setAttribute('role', 'dialog');
      pop.appendChild(el('div', 'dp-cal'));
      if (ctx.withTime) { pop.appendChild(buildTime()); }
      document.body.appendChild(pop);
      renderCal(); syncTime(); place();
    }

    document.addEventListener('mousedown', function (e) {
      if (pop && !pop.contains(e.target) && !(ctx && ctx.display.parentNode.contains(e.target))) { close(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && pop) { close(); } });
    window.addEventListener('resize', place);

    document.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function (inp) {
      if (!inp.closest('.main')) { return; }
      var withTime = inp.type === 'datetime-local', initial = inp.value;
      var wrap = el('span', 'dp'), disp = el('input', 'dp-input');
      disp.type = 'text'; disp.readOnly = true; disp.autocomplete = 'off';
      disp.placeholder = withTime ? 'dd/mm/yyyy 00:00' : 'dd/mm/yyyy';
      disp.setAttribute('aria-haspopup', 'dialog');
      var lbl = inp.getAttribute('aria-label'); if (lbl) { disp.setAttribute('aria-label', lbl); }
      var ico = el('span', 'dp-ico');
      ico.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>';
      wrap.appendChild(disp); wrap.appendChild(ico);
      inp.parentNode.insertBefore(wrap, inp);
      var c = { hidden: inp, display: disp, withTime: withTime, min: parse(inp.getAttribute('min')), max: parse(inp.getAttribute('max')), sel: parse(initial), vy: 0, vm: 0 };
      inp.type = 'hidden';
      inp.value = initial;
      if (c.sel) { disp.value = toShow(c.sel, withTime); }
      disp.addEventListener('click', function () { open(c); });
      disp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') { e.preventDefault(); open(c); }
        else if (e.key === 'Backspace' || e.key === 'Delete') { c.sel = null; ctx = c; commit(); ctx = null; }
      });
    });
  })();

  /* ----------------------------------------------------------------------------
     [MUC 7] NUT CON MAT HIEN / AN MAT KHAU
     Hien o  : ba o mat khau o trang Ho so cua toi.
     Chay o  : views/admin/profile/index.php. Tu them nut vao moi <input type="password"> trong
               khu admin (trang dang nhap co nut rieng trong views/admin/auth/login.php).
     ---------------------------------------------------------------------------- */

  (function () {
    var en = (document.documentElement.lang || 'vi').toLowerCase().indexOf('en') === 0;
    var label = en ? 'Show or hide password' : 'Hiện hoặc ẩn mật khẩu';
    var svg = function (inner) { return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + inner + '</svg>'; };
    var EYE = svg('<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>');
    var EYE_OFF = svg('<path d="M17.94 17.94A10.5 10.5 0 0 1 12 19c-6 0-10-7-10-7a18.5 18.5 0 0 1 4.06-5.06M9.9 4.24A9.9 9.9 0 0 1 12 5c6 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12A3 3 0 1 1 9.88 9.88"/><path d="M1 1l22 22"/>');
    document.querySelectorAll('.main input[type="password"]').forEach(function (inp) {
      if (inp.parentNode.classList.contains('pw')) { return; }
      var wrap = document.createElement('span');
      wrap.className = 'pw';
      inp.parentNode.insertBefore(wrap, inp);
      wrap.appendChild(inp);
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'pw-eye';
      b.setAttribute('aria-label', label);
      b.innerHTML = EYE;
      b.addEventListener('click', function () {
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        b.innerHTML = show ? EYE_OFF : EYE;
        inp.focus();
      });
      wrap.appendChild(b);
    });
  })();

  /* ----------------------------------------------------------------------------
     [MUC 8] THONG BAO LOI NHAP LIEU BANG TIENG VIET / ANH
     Hien o  : khung bao nho khi bo trong o bat buoc, nhap thieu ky tu, email sai...
     Chay o  : moi form (thay cho cau tieng Anh mac dinh cua trinh duyet), ke ca trang dang
               nhap. Cau chu nam ngay trong muc nay (bien S).
     ---------------------------------------------------------------------------- */

  (function () {
    var en = (document.documentElement.lang || 'vi').toLowerCase().indexOf('en') === 0;
    var S = en ? {
      required: 'Please fill out this field.', select: 'Please choose an item from the list.', file: 'Please choose a file.',
      check: 'Please tick this box to continue.', email: 'Please enter a valid email address.', type: 'Please enter a value in the right format.',
      tooShort: function (n, c) { return 'Please enter at least ' + n + ' characters (you have ' + c + ').'; },
      tooLong: function (n) { return 'Please use at most ' + n + ' characters.'; },
      min: function (v) { return 'The value must be ' + v + ' or more.'; },
      max: function (v) { return 'The value must not be more than ' + v + '.'; },
      step: 'Please enter a valid number.', pattern: 'The value does not match the required format.', bad: 'The value is not valid.'
    } : {
      required: 'Vui lòng điền vào ô này.', select: 'Vui lòng chọn một mục trong danh sách.', file: 'Vui lòng chọn một tệp.',
      check: 'Vui lòng tích vào ô này để tiếp tục.', email: 'Vui lòng nhập địa chỉ email hợp lệ (ví dụ: ten@gmail.com).', type: 'Vui lòng nhập đúng định dạng.',
      tooShort: function (n, c) { return 'Vui lòng nhập ít nhất ' + n + ' ký tự (hiện có ' + c + ' ký tự).'; },
      tooLong: function (n) { return 'Chỉ được nhập tối đa ' + n + ' ký tự.'; },
      min: function (v) { return 'Giá trị phải từ ' + v + ' trở lên.'; },
      max: function (v) { return 'Giá trị không được lớn hơn ' + v + '.'; },
      step: 'Vui lòng nhập một số hợp lệ.', pattern: 'Giá trị chưa đúng định dạng yêu cầu.', bad: 'Giá trị không hợp lệ.'
    };
    /* MSG START */
    function msg(el) {
      var v = el.validity;
      if (!v || v.valid) { return ''; }
      if (v.valueMissing) {
        if (el.tagName === 'SELECT') { return S.select; }
        if (el.type === 'file') { return S.file; }
        if (el.type === 'checkbox') { return S.check; }
        return S.required;
      }
      if (v.typeMismatch) { return el.type === 'email' ? S.email : S.type; }
      if (v.tooShort) { return S.tooShort(el.minLength, el.value.length); }
      if (v.tooLong) { return S.tooLong(el.maxLength); }
      if (v.rangeUnderflow) { return S.min(el.min); }
      if (v.rangeOverflow) { return S.max(el.max); }
      if (v.stepMismatch) { return S.step; }
      if (v.patternMismatch) { return S.pattern; }
      if (v.badInput) { return S.bad; }
      return '';
    }
    /* MSG END */
    document.addEventListener('invalid', function (e) {
      var el = e.target;
      if (!el.setCustomValidity) { return; }
      el.setCustomValidity('');
      var m = msg(el);
      if (m) { el.setCustomValidity(m); }
    }, true);
    function clear(e) { var el = e.target; if (el && el.setCustomValidity) { el.setCustomValidity(''); } }
    document.addEventListener('input', clear, true);
    document.addEventListener('change', clear, true);
  })();

  /* ----------------------------------------------------------------------------
     [MUC 9] O NHAP TIEN TU THEM DAU CHAM NGAN CACH HANG NGHIN (vi du 10.000)
     Hien o  : gia tri giam, giam toi da, don toi thieu, so luot dung, gia thu cung, gia san
               pham; chu vi du mo trong o.
     Chay o  : views/admin/coupons/form.php, catalog/pets/form.php, catalog/products/form.php (o
               co thuoc tinh data-money). Khi bam Luu, dau cham duoc bo de may chu nhan so
               thuan.
     ---------------------------------------------------------------------------- */

  (function () {
    /* MONEY START */
    function fmtDigits(d) { return d.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function clean(raw) { return raw.replace(/\D/g, '').replace(/^0+(?=\d)/, ''); }
    function caretFor(out, left) {
      var n = 0, p = 0;
      if (left > 0) { for (p = 0; p < out.length && n < left; p++) { if (/\d/.test(out.charAt(p))) { n++; } } }
      return p;
    }
    /* MONEY END */
    document.querySelectorAll('input[data-money]').forEach(function (inp) {
      inp.addEventListener('input', function () {
        var raw = inp.value, pos = inp.selectionStart == null ? raw.length : inp.selectionStart;
        var left = raw.slice(0, pos).replace(/\D/g, '').length;
        var out = fmtDigits(clean(raw));
        inp.value = out;
        try { var p = caretFor(out, left); inp.setSelectionRange(p, p); } catch (e) {}
      });
      if (inp.value) { inp.value = fmtDigits(clean(inp.value)); }
    });
    // Khi gui form: bo dau cham de may chu nhan so thuan
    document.addEventListener('submit', function (e) {
      if (!e.target.querySelectorAll) { return; }
      e.target.querySelectorAll('input[data-money]').forEach(function (i) { i.value = i.value.replace(/\D/g, ''); });
    }, true);
    // Ma giam gia: goi y vi du doi theo loai giam (phan tram hoac so tien)
    var typeSel = document.querySelector('select[name="discount_type"]');
    var valInp = document.querySelector('input[name="discount_value"][data-ph-percent]');
    if (typeSel && valInp) {
      var unit = document.querySelector('[data-unit]');
      var hint = document.querySelector('[data-hint]');
      // Giam theo phan tram: toi da 100 (100% la giam het)
      var clamp = function () {
        if (typeSel.value === 'percent') {
          var n = parseInt(valInp.value.replace(/\D/g, ''), 10);
          if (isFinite(n) && n > 100) { valInp.value = '100'; }
        }
      };
      // Doi goi y, don vi (% hoac dong) va chu huong dan theo loai giam dang chon
      var upd = function () {
        var pct = typeSel.value === 'percent';
        var ph = valInp.getAttribute(pct ? 'data-ph-percent' : 'data-ph-fixed');
        if (ph) { valInp.placeholder = ph; }
        if (unit) { unit.textContent = pct ? '%' : '\u20AB'; }
        if (hint) { hint.textContent = hint.getAttribute(pct ? 'data-hint-percent' : 'data-hint-fixed') || hint.textContent; }
        clamp();
      };
      typeSel.addEventListener('change', upd);
      valInp.addEventListener('input', clamp);
      upd();
    }
  })();

  /* ----------------------------------------------------------------------------
     [MUC 10] HOP XAC NHAN "BAN CO CHAC KHONG?"
     Hien o  : hop bo goc giua man hinh khi bam Dang xuat, Huy don, Khoa tai khoan, Xoa danh
               muc...
     Chay o  : moi <form data-confirm="cau hoi"> (vi du nut Dang xuat o
               views/admin/layout/header.php, nut huy don o views/admin/orders/show.php). Bam
               Dong y thi form moi duoc gui.
     ---------------------------------------------------------------------------- */

  (function () {
    var en = (document.documentElement.lang || 'vi').toLowerCase().indexOf('en') === 0;
    var T = en ? { title: 'Please confirm', ok: 'Confirm', cancel: 'Cancel' } : { title: 'Xác nhận', ok: 'Đồng ý', cancel: 'Hủy' };
    function ask(message, onYes) {
      var wrap = document.createElement('div'); wrap.className = 'cm'; wrap.setAttribute('role', 'dialog');
      wrap.innerHTML = '<div class="cm-box"><div class="cm-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg></div><h3 class="cm-title"></h3><p class="cm-msg"></p><div class="cm-act"><button type="button" class="b2" data-no></button><button type="button" class="b1" data-yes></button></div></div>';
      wrap.querySelector('.cm-title').textContent = T.title;
      wrap.querySelector('.cm-msg').textContent = message;
      wrap.querySelector('[data-no]').textContent = T.cancel;
      wrap.querySelector('[data-yes]').textContent = T.ok;
      function close() { document.removeEventListener('keydown', onKey, true); wrap.remove(); }
      function onKey(e) { if (e.key === 'Escape') { e.stopPropagation(); close(); } }
      wrap.addEventListener('mousedown', function (e) { if (e.target === wrap) { close(); } });
      wrap.querySelector('[data-no]').addEventListener('click', close);
      wrap.querySelector('[data-yes]').addEventListener('click', function () { close(); onYes(); });
      document.addEventListener('keydown', onKey, true);
      document.body.appendChild(wrap);
      wrap.querySelector('[data-yes]').focus();
    }
    document.addEventListener('submit', function (e) {
      var f = e.target, m = f.getAttribute && f.getAttribute('data-confirm');
      if (!m || f.__confirmed) { return; }
      e.preventDefault();
      ask(m, function () { f.__confirmed = true; f.submit(); });
    }, true);
  })();

  /* ----------------------------------------------------------------------------
     [MUC 11] GIU NGUYEN VI TRI CUON KHI BAM NUT CHUYEN
     Hien o  : khong hien gi; chi lam trang khong nhay len dau sau khi bam "San pham | Thu cung"
               o khoi "Ban chay nhat" cua Tong quan.
     Chay o  : cac lien ket co thuoc tinh data-keep-scroll (views/admin/dashboard/index.php).
               Truoc khi chuyen trang, ghi nho vi tri cuon; trang moi mo ra thi cuon lai dung cho do.
     ---------------------------------------------------------------------------- */
  (function () {
    var KEY = 'ps_keep_scroll';

    // Truoc khi chuyen trang: ghi nho vi tri cuon hien tai
    document.addEventListener('click', function (e) {
      var link = e.target.closest('a[data-keep-scroll]');
      if (!link) {
        return;
      }
      try {
        sessionStorage.setItem(KEY, String(window.pageYOffset));
      } catch (err) {}
    });

    // Khi trang moi mo ra: cuon lai dung vi tri da ghi nho (chi mot lan)
    try {
      var saved = sessionStorage.getItem(KEY);
      if (saved !== null) {
        sessionStorage.removeItem(KEY);
        window.scrollTo(0, parseInt(saved, 10) || 0);
      }
    } catch (err) {}
  })();
})();
