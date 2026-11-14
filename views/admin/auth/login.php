<?php
/* ==============================================================================
   VIEW: TRANG DANG NHAP QUAN TRI (rieng cho admin)
   File    : views/admin/auth/login.php
   Dia chi  : ?r=admin/login, ?r=auth/login
   Controller: controllers/AdminAuthController.php  (ham login)
   CSS      : public/css/admin-ui.css, nhom [4] THANH DAU TRANG VA CAC MENU THA XUONG; [6] THONG
           BAO NOI; [19] TRANG DANG NHAP.
   ============================================================================== */
?>
<?php
/** @var array $errors @var array $old */
$lang = AdminView::lang();
$flashes = pull_flash();
$cfg = AdminSettings::load();
$heroImg = '';
if (!empty($cfg['login_image']) && is_file(BASE_PATH . '/' . $cfg['login_image'])) {
    $heroImg = asset($cfg['login_image']);
} else {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        if (is_file(BASE_PATH . '/public/images/admin-login.' . $ext)) {
            $heroImg = asset('public/images/admin-login.' . $ext);
            break;
        }
    }
}
$heroPos = ['top' => 'center top', 'center' => 'center', 'bottom' => 'center bottom'][$cfg['login_pos'] ?? 'center'] ?? 'center';
$tagline = trim((string) ($cfg['login_tagline'] ?? '')) !== '' ? (string) $cfg['login_tagline'] : t('login.tagline');
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('login.title')) ?> - Pet Shop</title>
    <script>try{var __t=localStorage.getItem('ps_admin_theme');if(__t){document.documentElement.setAttribute('data-theme',__t);}}catch(e){}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_asset('public/css/admin-ui.css')) ?>">
</head>
<body>
<div class="vl">
    <section class="vl-hero<?= $heroImg !== '' ? ' has-img' : '' ?>"<?= $heroImg !== '' ? ' style="background-image:url(\'' . e($heroImg) . '\');background-position:' . $heroPos . '"' : '' ?>>
        <div class="vl-art" aria-hidden="true">
            <?php foreach (['a1', 'a2', 'a3'] as $cls): ?>
                <svg class="<?= $cls ?>" viewBox="0 0 24 24" fill="currentColor"><circle cx="6" cy="10" r="2.4"/><circle cx="10" cy="5" r="2.4"/><circle cx="14" cy="5" r="2.4"/><circle cx="18" cy="10" r="2.4"/><path d="M12 12c-3.4 0-5.6 3.2-5.6 5.4 0 2.2 2.2 3.2 5.6 2.2 3.4 1 5.6 0 5.6-2.2 0-2.2-2.2-5.4-5.6-5.4z"/></svg>
            <?php endforeach; ?>
        </div>
        <div class="vl-corner"></div>
        <div class="vl-hero-body">
            <ul class="vl-chips" aria-hidden="true">
                <li><?= AdminView::icon('orders', 18) ?><?= e(t('login.chip1')) ?></li>
                <li><?= AdminView::icon('coin', 18) ?><?= e(t('login.chip2')) ?></li>
                <li><?= AdminView::icon('pets', 18) ?><?= e(t('login.chip3')) ?></li>
                <li><?= AdminView::icon('coupons', 18) ?><?= e(t('login.chip4')) ?></li>
            </ul>
            <div class="vl-tag"><?= e($tagline) ?></div>
        </div>
    </section>

    <section class="vl-panel">
        <div class="vl-arc"></div>
        <div class="vl-dots"></div>
        <div class="vl-top">
            <div class="lang" role="group" aria-label="<?= e(t('common.language')) ?>">
                <a href="<?= e(url('admin/login', ['l' => 'vi'])) ?>"<?= $lang === 'vi' ? ' class="on"' : '' ?>>VI</a>
                <a href="<?= e(url('admin/login', ['l' => 'en'])) ?>"<?= $lang === 'en' ? ' class="on"' : '' ?>>EN</a>
            </div>
            <div class="theme" role="group">
                <button type="button" data-theme-set="light" aria-label="<?= e(t('common.light')) ?>"><?= AdminView::icon('sun', 16) ?></button>
                <button type="button" data-theme-set="dark" aria-label="<?= e(t('common.dark')) ?>"><?= AdminView::icon('moon', 16) ?></button>
            </div>
        </div>

        <main class="vl-box">
            <div class="vl-logo"><i><?= AdminView::icon('pets', 22) ?></i>PetShop</div>
            <p class="vl-badge"><?= AdminView::icon('shield', 14) ?><?= e(t('login.portal')) ?></p>
            <h1><?= e(t('login.welcome')) ?></h1>
            <p class="vl-sub"><?= e(t('login.sub')) ?></p>

            <?php foreach ($flashes as $f): ?>
                <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?php if (isset($errors['login'])): ?>
                <div class="flash error"><?= e($errors['login']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('admin/login')) ?>" id="loginForm" data-busy="<?= e(t('login.signing')) ?>">
                <?= csrf_field() ?>
                <label><?= e(t('login.email')) ?>
                    <span class="vl-in">
                        <?= AdminView::icon('mail', 18) ?>
                        <input type="email" name="email" id="loginEmail" value="<?= val($old, 'email') ?>" required autofocus autocomplete="username">
                    </span>
                </label>
                <label><?= e(t('login.password')) ?>
                    <span class="vl-in">
                        <?= AdminView::icon('lock', 18) ?>
                        <input type="password" name="password" id="loginPw" required autocomplete="current-password">
                        <button type="button" class="vl-eye" id="loginEye" aria-label="<?= e(t('login.show_pw')) ?>"><span class="eye-on"><?= AdminView::icon('eye', 18) ?></span><span class="eye-off"><?= AdminView::icon('eyeoff', 18) ?></span></button>
                    </span>
                </label>
                <div class="vl-caps" id="loginCaps" hidden><?= e(t('login.capslock')) ?></div>
                <div class="vl-row">
                    <label class="chk"><input type="checkbox" id="loginRem"> <?= e(t('login.remember')) ?></label>
                </div>
                <button class="vl-btn" type="submit" id="loginBtn"><span><?= e(t('login.submit')) ?></span><?= AdminView::icon('arrow', 18) ?></button>
            </form>
        </main>
        <div class="vl-foot">© <?= date('Y') ?> PetShop · <?= e(t('login.footer')) ?></div>
    </section>
</div>
<script src="<?= e(admin_asset('public/js/admin-ui.js')) ?>"></script>
<script>
(function () {
  var em = document.getElementById('loginEmail'), pw = document.getElementById('loginPw'),
      eye = document.getElementById('loginEye'), rem = document.getElementById('loginRem'),
      caps = document.getElementById('loginCaps'), btn = document.getElementById('loginBtn'),
      form = document.getElementById('loginForm'), K = 'ps_admin_email';
  if (eye) {
    eye.addEventListener('click', function () {
      pw.type = pw.type === 'password' ? 'text' : 'password';
      eye.classList.toggle('on', pw.type === 'text');
      eye.setAttribute('aria-pressed', pw.type === 'text' ? 'true' : 'false');
    });
  }
  if (pw && caps) {
    var chk = function (e) { caps.hidden = !(e.getModifierState && e.getModifierState('CapsLock')); };
    pw.addEventListener('keydown', chk); pw.addEventListener('keyup', chk);
    pw.addEventListener('blur', function () { caps.hidden = true; });
  }
  try {
    var saved = localStorage.getItem(K);
    if (saved && em && !em.value) { em.value = saved; rem.checked = true; if (pw) { pw.focus(); } }
  } catch (e) {}
  if (form) {
    form.addEventListener('submit', function () {
      try { if (rem.checked) { localStorage.setItem(K, em.value); } else { localStorage.removeItem(K); } } catch (e) {}
      if (btn) { btn.classList.add('busy'); btn.querySelector('span').textContent = form.getAttribute('data-busy'); }
    });
  }
})();
</script>
</body>
</html>
