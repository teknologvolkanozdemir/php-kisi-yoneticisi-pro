<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = trim($argv[1] ?? '');
$password = $argv[2] ?? '';
if ($username === '' || strlen($username) > 80 || strlen($password) < 12) {
    fwrite(STDERR, "Kullanım: php admin/create-admin.php <kullanıcı_adı> <en_az_12_karakter_şifre>\n");
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
