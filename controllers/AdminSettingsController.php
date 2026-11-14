<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: CAI DAT GIAO DIEN
   File    : controllers/AdminSettingsController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/settings                        -> index() -> hien thi views/admin/settings/index.php
   Du lieu lay tu: models/AdminSettings.php
   Dung cua TV3: Uploader (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Cai dat giao dien: doi anh nen va dong chu cua trang dang nhap admin, khong can sua code. */
final class AdminSettingsController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $cfg = AdminSettings::load();
        $errors = [];

        if (is_post()) {
            csrf_verify();
            if (input('action') === 'reset') {
                AdminSettings::deleteImage($cfg['login_image'] ?? null);
                AdminSettings::save([]);
                flash('success', t('settings.msg.reset'));
                redirect('admin/settings');
            }

            $pos = input('login_pos', 'center');
            $tag = trim(input('login_tagline'));
            if (!in_array($pos, ['top', 'center', 'bottom'], true)) {
                $errors['login_pos'] = t('settings.err.pos');
            }
            if (mb_strlen($tag) > 120) {
                $errors['login_tagline'] = t('settings.err.tagline');
            }
            [$img, $imgErr] = Uploader::image($_FILES['image'] ?? null, 'admin');
            if ($imgErr) {
                $errors['image'] = $imgErr;
            }

            if (!$errors) {
                if ($img) {
                    AdminSettings::deleteImage($cfg['login_image'] ?? null);
                    $cfg['login_image'] = $img;
                }
                $cfg['login_pos'] = $pos;
                $cfg['login_tagline'] = $tag;
                AdminSettings::save($cfg);
                flash('success', t('settings.msg.saved'));
                redirect('admin/settings');
            }
            if ($img) {
                Uploader::delete($img);
            }
            $cfg = array_merge($cfg, ['login_pos' => $pos, 'login_tagline' => $tag]);
        }

        AdminView::render('settings/index', compact('cfg', 'errors'), 'settings', t('menu.settings'));
    }
}
