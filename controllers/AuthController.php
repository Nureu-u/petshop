<?php
declare(strict_types=1);

final class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect('home');
        }
        $errors = [];
        $old = [];
        if (is_post()) {
            csrf_verify();
            $email = normalize_email((string) ($_POST['email'] ?? ''));
            $old = ['email' => $email];
            $err = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
            if ($err === null) {
                flash('success', 'Đăng nhập thành công.');
                redirect(Auth::isAdmin() ? 'admin/pets' : 'home');
            }
            $errors['login'] = $err;
        }
        $title = 'Đăng nhập';
        view('auth/login', compact('errors', 'old', 'title'));
    }

    public function register(): void
    {
        if (Auth::check()) {
            redirect('home');
        }
        $errors = [];
        $old = [];
        if (is_post()) {
            csrf_verify();
            [$errors, $data] = User::validateRegister($_POST);
            $old = $_POST;
            unset($old['password'], $old['password_confirm']);
            if (!$errors) {
                try {
                    $id = User::create($data);
                    Auth::login($id);
                    flash('success', 'Đăng ký thành công. Chào mừng bạn!');
                    redirect('home');
                } catch (PDOException $e) {
                    // Hai request đăng ký cùng email cùng lúc: UNIQUE KEY chặn ở DB
                    if ($e->getCode() === '23000') {
                        $errors['email'] = 'Email này đã được đăng ký.';
                    } else {
                        throw $e;
                    }
                }
            }
        }
        $title = 'Đăng ký';
        view('auth/register', compact('errors', 'old', 'title'));
    }

    public function logout(): void
    {
        require_post();
        Auth::logout();
        Auth::start();
        flash('success', 'Bạn đã đăng xuất.');
        redirect('home');
    }

    public function profile(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $errors = [];
        $old = $user;

        if (is_post()) {
            csrf_verify();
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'profile') {
                [$errors, $data] = User::validateProfile($_POST);
                if (!$errors) {
                    User::updateProfile($user['id'], $data);
                    flash('success', 'Đã cập nhật hồ sơ.');
                    redirect('auth/profile');
                }
                $old = array_merge($user, $_POST);
            } elseif ($action === 'password') {
                $row = User::findById($user['id']);
                $current = (string) ($_POST['current_password'] ?? '');
                $new = (string) ($_POST['new_password'] ?? '');
                if (!$row || !password_verify($current, $row['password_hash'])) {
                    $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
                } elseif (strlen($new) < 8 || strlen($new) > 72) {
                    $errors['new_password'] = 'Mật khẩu mới từ 8 đến 72 ký tự.';
                } elseif ($new !== (string) ($_POST['new_password_confirm'] ?? '')) {
                    $errors['new_password_confirm'] = 'Mật khẩu nhập lại không khớp.';
                } else {
                    User::updatePassword($user['id'], password_hash($new, PASSWORD_DEFAULT));
                    session_regenerate_id(true);
                    flash('success', 'Đã đổi mật khẩu.');
                    redirect('auth/profile');
                }
            }
        }
        $title = 'Hồ sơ cá nhân';
        view('auth/profile', compact('errors', 'old', 'user', 'title'));
    }

    public function forgot(): void
    {
        $errors = [];
        $resetLink = null;
        if (is_post()) {
            csrf_verify();
            $email = normalize_email((string) ($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email không hợp lệ.';
            } else {
                $user = User::findByEmail($email);
                if ($user && $user['status'] === 'active') {
                    $token = PasswordReset::create($user['id']);
                    $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                          . url('auth/reset', ['token' => $token]);
                    // TODO: khi có hạ tầng email (PHPMailer/SMTP), gửi $link qua email tại đây.
                    // Chế độ debug (demo đồ án): hiển thị link trực tiếp vì chưa gửi email thật.
                    if (config('debug')) {
                        $resetLink = $link;
                    }
                }
                // Luôn trả cùng một thông báo để không lộ email nào đã đăng ký.
                flash('success', 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu đã được tạo.');
            }
        }
        $title = 'Quên mật khẩu';
        view('auth/forgot', compact('errors', 'resetLink', 'title'));
    }

    public function reset(): void
    {
        $token = input('token');
        $row = PasswordReset::findValid($token);
        if (!$row) {
            abort(404, 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
        }
        $errors = [];
        if (is_post()) {
            csrf_verify();
            $new = (string) ($_POST['password'] ?? '');
            if (strlen($new) < 8 || strlen($new) > 72) {
                $errors['password'] = 'Mật khẩu từ 8 đến 72 ký tự.';
            } elseif ($new !== (string) ($_POST['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
            } elseif (PasswordReset::resetPassword($token, $new)) {
                flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập.');
                redirect('auth/login');
            } else {
                abort(404, 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            }
        }
        $title = 'Đặt lại mật khẩu';
        view('auth/reset', compact('errors', 'token', 'title'));
    }
}
