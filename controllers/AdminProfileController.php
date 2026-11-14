<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: HO SO QUAN TRI VIEN
   File    : controllers/AdminProfileController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/profile                         -> index() -> hien thi views/admin/profile/index.php
   Dung cua TV3: Auth, User (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Ho so quan tri vien: sua thong tin ca nhan va doi mat khau (dung lai ham cua TV3). */
final class AdminProfileController
{
    public function index(): void
    {
        AdminAuthController::guard();
        $user = Auth::user();
        $errors = [];
        $pwErrors = [];
        $old = $user;

        if (is_post()) {
            csrf_verify();
            $action = input('action');
            if ($action === 'profile') {
                // Admin chi sua ho ten; dien thoai va dia chi giu nguyen nhu dang luu
                [$errors, $data] = User::validateProfile(array_merge($_POST, ['phone' => (string) ($user['phone'] ?? ''), 'address' => (string) ($user['address'] ?? '')]));
                if (!$errors) {
                    User::updateProfile($user['id'], $data);
                    flash('success', t('profile.msg.saved'));
                    redirect('admin/profile');
                }
                $old = array_merge($user, $_POST);
            } elseif ($action === 'password') {
                $row = User::findById($user['id']);
                $current = (string) ($_POST['current_password'] ?? '');
                $new = (string) ($_POST['new_password'] ?? '');
                if (!$row || !password_verify($current, $row['password_hash'])) {
                    $pwErrors['current_password'] = t('profile.err.current');
                } elseif (strlen($new) < 8 || strlen($new) > 72) {
                    $pwErrors['new_password'] = t('profile.err.new_len');
                } elseif ($new !== (string) ($_POST['new_password_confirm'] ?? '')) {
                    $pwErrors['new_password_confirm'] = t('profile.err.confirm');
                } else {
                    User::updatePassword($user['id'], password_hash($new, PASSWORD_DEFAULT));
                    session_regenerate_id(true);
                    flash('success', t('profile.msg.password'));
                    redirect('admin/profile');
                }
            }
        }
        AdminView::render('profile/index', compact('user', 'old', 'errors', 'pwErrors'), 'profile', t('menu.profile'));
    }
}
