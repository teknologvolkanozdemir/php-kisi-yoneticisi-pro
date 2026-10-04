<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id, password_hash, must_change_password FROM admins WHERE username = :username');
    $statement->execute(['username' => $username]);
    $admin = $statement->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_must_change_password'] = (bool) $admin['must_change_password'];
        header('Location: ' . ($_SESSION['admin_must_change_password'] ? 'change-password.php' : 'index.php'));
        exit;
    }
    $error = 'Kullanıcı adı veya şifre hatalı.';
}

render_header('Admin girişi');
?>
<?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form class="panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Kullanıcı adı <input name="username" maxlength="80" required autocomplete="username"></label>
    <label>Şifre <input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit">Giriş yap</button>
</form>
<?php render_footer(); ?>
