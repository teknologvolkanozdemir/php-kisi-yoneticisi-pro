<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$username = trim($argv[1] ?? '');
if ($username === '' || mb_strlen($username) > 80 || !stream_isatty(STDIN)) {
    fwrite(STDERR, "Kullanım: php admin/create-admin.php <kullanıcı_adı> (etkileşimli terminal gerekir)\n");
    exit(1);
}

fwrite(STDOUT, "Yeni şifre (en az 12 karakter): ");
exec('stty -echo');
$password = trim((string) fgets(STDIN));
exec('stty echo');
fwrite(STDOUT, PHP_EOL);
if (mb_strlen($password) < 12) {
    fwrite(STDERR, "Şifre en az 12 karakter olmalıdır.\n");
    exit(1);
}

$statement = $pdo->prepare(
    'INSERT INTO admins (username, password_hash, must_change_password) VALUES (:username, :password_hash, 1)'
);
try {
    $statement->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    fwrite(STDERR, "Admin oluşturulamadı; kullanıcı adı zaten kullanılıyor olabilir.\n");
    exit(1);
}

fwrite(STDOUT, "Admin oluşturuldu. İlk girişte şifrenizi değiştirin.\n");
