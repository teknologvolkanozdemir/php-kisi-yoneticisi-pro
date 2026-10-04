<?php
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Yapılandırma bulunamadı. README.md dosyasındaki kurulum adımlarını izleyin.');
}

$config = require $configPath;
$db = $config['database'];
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['name'],
    $db['charset']
);

try {
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Veritabanına bağlanılamadı. Yapılandırma bilgilerini kontrol edin.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Oturum doğrulanamadı. Sayfayı yenileyip tekrar deneyin.');
    }
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function redirect_with_message(string $message): never
{
    $_SESSION['flash_message'] = $message;
    header('Location: index.php');
    exit;
}

function take_flash_message(): ?string
{
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return is_string($message) ? $message : null;
}

function valid_contact(array $input): array
{
    $contact = [];
    foreach (['ad', 'soyad', 'telefon', 'email', 'adres', 'notlar'] as $field) {
        $value = trim((string) ($input[$field] ?? ''));
        if (in_array($field, ['ad', 'soyad'], true) && $value === '') {
            throw new InvalidArgumentException('Ad ve soyad alanları zorunludur.');
        }
        $contact[$field] = $value;
    }

    if (mb_strlen($contact['ad']) > 100 || mb_strlen($contact['soyad']) > 100
        || mb_strlen($contact['telefon']) > 40 || mb_strlen($contact['email']) > 254) {
        throw new InvalidArgumentException('Ad, soyad, telefon veya e-posta alanı çok uzun.');
    }
    if ($contact['email'] !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Geçerli bir e-posta adresi girin.');
    }
    return $contact;
}

function render_header(string $title): void
{
    ?>
    <!doctype html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="../public/assets/style.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="../public/">Kişi Yönetimi</a>
        <nav><a href="../public/">Kişiler</a> <a href="../admin/">Admin</a></nav>
    </header>
    <main class="container">
        <h1><?= e($title) ?></h1>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
