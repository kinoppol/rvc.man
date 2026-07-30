<?php
declare(strict_types=1);

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('account');
        }
        App::render('public/login', ['error' => null], [
            'title' => 'เข้าสู่ระบบ', 'section' => 'bare', 'active' => 'account',
        ]);
    }

    public function login(): void
    {
        verify_csrf();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $result = Auth::attempt($username, $password);
        if ($result !== 'ok') {
            App::render('public/login', [
                'error' => $result === 'disabled'
                    ? 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'
                    : 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
            ], ['title' => 'เข้าสู่ระบบ', 'section' => 'bare', 'active' => 'account']);
            return;
        }

        $intended = $_SESSION['_intended'] ?? '';
        unset($_SESSION['_intended']);
        flash('เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . (Auth::user()['name'] ?? ''));

        if ($intended !== '') {
            header('Location: ' . $intended);
            exit;
        }
        redirect(Auth::isAdmin() ? 'admin' : '');
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logout();
        flash('ออกจากระบบแล้ว');
        redirect('');
    }

    public function account(): void
    {
        Auth::requireLogin();
        $repo = new Repository();
        App::render('public/account', [
            'user'          => Auth::user(),
            'favoriteCount' => count($repo->favorites((int) Auth::id())),
        ], ['title' => 'บัญชีของฉัน', 'active' => 'account']);
    }

    public function changePassword(): void
    {
        verify_csrf();
        Auth::requireLogin();

        $repo    = new Repository();
        $user    = $repo->user((int) Auth::id());
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!$user || !password_verify($current, $user['password_hash'])) {
            flash('รหัสผ่านปัจจุบันไม่ถูกต้อง', 'error');
        } elseif (mb_strlen($new, 'UTF-8') < 6) {
            flash('รหัสผ่านใหม่ต้องยาวอย่างน้อย 6 ตัวอักษร', 'error');
        } elseif ($new !== $confirm) {
            flash('ยืนยันรหัสผ่านใหม่ไม่ตรงกัน', 'error');
        } else {
            $repo->setPassword((int) $user['id'], $new);
            flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        }

        redirect('account');
    }
}
