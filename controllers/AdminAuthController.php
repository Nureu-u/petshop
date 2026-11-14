<?php
declare(strict_types=1);

require_once BASE_PATH . '/core/AdminView.php';

/* ==============================================================================
   CONTROLLER: DANG NHAP / DANG XUAT
   File    : controllers/AdminAuthController.php
   Moi ham cong khai (public) la mot trang hoac mot thao tac. Bang duong dan (nam trong index.php):
     ?r=admin/login, ?r=auth/login            -> login() -> hien thi views/admin/auth/login.php
     ?r=admin/logout                          -> logout()
   Dung cua TV3: Auth (chi goi, khong sua code cua TV3).
   ============================================================================== */
/** Dang nhap / dang xuat RIENG cho khu quan tri (admin) cua TV5 (dung lai Auth cua TV3, khong sua file TV3). */
final class AdminAuthController
{
    /** Thay cho Auth::requireAdmin(): chua dang nhap thi chuyen toi trang dang nhap admin. */
    public static function guard(): void
    {
        if (!Auth::check()) {
            redirect('admin/login');
        }
        if (!Auth::isAdmin()) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }

    public function login(): void
    {
        $l = input('l');
        if ($l === 'vi' || $l === 'en') {
            $_SESSION['admin_lang'] = $l;
        }
        if (Auth::check() && Auth::isAdmin()) {
            redirect('admin/dashboard');
        }

        $errors = [];
        $old = [];
        if (is_post()) {
            csrf_verify();
            $email = normalize_email((string) ($_POST['email'] ?? ''));
            $old = ['email' => $email];
            $err = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
            if ($err === null && !Auth::isAdmin()) {
                // Trang nay CHI danh cho quan tri vien: tai khoan khach dang nhap dung cung bi dang xuat ngay
                // (khach dung trang dang nhap cua TV1/TV3 o ?r=auth/login)
                Auth::logout();
                Auth::start();
                $err = t('login.err.not_admin');
            }
            if ($err === null) {
                flash('success', t('login.success'));
                redirect('admin/dashboard');
            }
            $errors['login'] = $err;
        }
        require BASE_PATH . '/views/admin/auth/login.php';
    }

    public function logout(): void
    {
        require_post();
        Auth::logout();
        Auth::start();
        flash('success', t('login.logged_out'));
        redirect('admin/login');
    }
}
