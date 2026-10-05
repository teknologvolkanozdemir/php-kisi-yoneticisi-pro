<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$statement = $pdo->prepare('SELECT password_hash FROM admins WHERE id = :id');
$statement->execute(['id' => $_SESSION['admin_id']]);
$admin = $statement->fetch();
if (!$admin) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = input_string($_POST, 'current_password');
    $newPassword = input_string($_POST, 'new_password');
    $confirmation = input_string($_POST, 'confirm_password');
    if (!password_verify($current, $admin['password_hash'])) {
        $error = 'Mevcut şifreniz hatalı.';
    } elseif (mb_strlen($newPassword) < 12) {
        $error = 'Yeni şifre en az 12 karakter olmalıdır.';
    } elseif ($newPassword !== $confirmation) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } else {
        $update = $pdo->prepare(
            'UPDATE admins SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $_SESSION['admin_id'],
        ]);
        $_SESSION['admin_must_change_password'] = false;
        session_regenerate_id(true);
        redirect_with_message('Şifreniz güncellendi.');
    }
}

render_header('Şifre değiştir');
?>
<p class="notice">İlk girişiniz. Devam etmeden önce yönetici şifrenizi değiştirin.</p>
<?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form class="panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Mevcut şifre <input type="password" name="current_password" required autocomplete="current-password"></label><br>
    <label>Yeni şifre (en az 12 karakter) <input type="password" name="new_password" minlength="12" required autocomplete="new-password"></label><br>
    <label>Yeni şifreyi doğrula <input type="password" name="confirm_password" minlength="12" required autocomplete="new-password"></label><br>
    <button type="submit">Şifreyi güncelle</button>
</form>
<?php render_footer(); ?>
