<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if (!empty($_SESSION['admin_must_change_password'])) {
    header('Location: change-password.php');
    exit;
}

$error = '';
$editContact = null;
$editId = max(0, (int) ($_GET['edit'] ?? 0));
if ($editId > 0) {
    $edit = $pdo->prepare('SELECT * FROM contacts WHERE id = :id');
    $edit->execute(['id' => $editId]);
    $editContact = $edit->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = input_string($_POST, 'action');
    $id = max(0, (int) ($_POST['id'] ?? 0));
    try {
        if ($action === 'save') {
            $contact = valid_contact($_POST);
            if ($id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE contacts SET ad = :ad, soyad = :soyad, telefon = :telefon, email = :email,
                     adres = :adres, notlar = :notlar WHERE id = :id'
                );
                $statement->execute($contact + ['id' => $id]);
                redirect_with_message('Kişi güncellendi.');
            }
            $statement = $pdo->prepare(
                'INSERT INTO contacts (ad, soyad, telefon, email, adres, notlar)
                 VALUES (:ad, :soyad, :telefon, :email, :adres, :notlar)'
            );
            $statement->execute($contact);
            redirect_with_message('Kişi eklendi.');
        }

        if ($id < 1) {
            throw new InvalidArgumentException('Geçersiz kişi numarası.');
        }
        if ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
            $statement->execute(['id' => $id]);
            redirect_with_message('Kişi silindi.');
        }
        if ($action === 'toggle') {
            $statement = $pdo->prepare(
                'UPDATE contacts SET is_hidden = CASE WHEN is_hidden = 1 THEN 0 ELSE 1 END WHERE id = :id'
            );
            $statement->execute(['id' => $id]);
            redirect_with_message('Kişinin görünürlük durumu güncellendi.');
        }
        throw new InvalidArgumentException('Geçersiz işlem.');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        if ($action === 'save') {
            $editContact = $_POST + ['id' => $id];
        }
    }
}

$totals = $pdo->query(
    'SELECT COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN is_hidden = 0 THEN 1 ELSE 0 END), 0) AS visible,
        COALESCE(SUM(CASE WHEN is_hidden = 1 THEN 1 ELSE 0 END), 0) AS hidden
     FROM contacts'
)->fetch();

$search = trim(input_string($_GET, 'q'));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$visibility = input_string($_GET, 'visibility') ?: 'all';
if (!in_array($visibility, ['all', 'visible', 'hidden'], true)) {
    $visibility = 'all';
}
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 25, 50], true)) {
    $perPage = 10;
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$where = [];
$parameters = [];
if ($visibility === 'visible') {
    $where[] = 'is_hidden = 0';
} elseif ($visibility === 'hidden') {
    $where[] = 'is_hidden = 1';
}
if ($search !== '') {
    $where[] = '(ad LIKE :ad OR soyad LIKE :soyad OR telefon LIKE :telefon OR email LIKE :email)';
    $prefix = $search . '%';
    $parameters = ['ad' => $prefix, 'soyad' => $prefix, 'telefon' => $prefix, 'email' => $prefix];
}
$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$count = $pdo->prepare('SELECT COUNT(*) FROM contacts' . $whereSql);
$count->execute($parameters);
$total = (int) $count->fetchColumn();
$pageCount = max(1, (int) ceil($total / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;
$statement = $pdo->prepare(
    'SELECT id, ad, soyad, telefon, email, is_hidden, created_at FROM contacts' . $whereSql
    . ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset'
);
foreach ($parameters as $name => $value) {
    $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$contacts = $statement->fetchAll();
$flash = take_flash_message();

render_header('Admin paneli');
?>
<div class="toolbar">
    <a class="button secondary" href="../public/">Public listeyi gör</a>
    <form method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="secondary" type="submit">Çıkış yap</button>
    </form>
</div>
<?php if ($flash !== null): ?><p class="notice"><?= e($flash) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<section class="stats">
    <div class="card stat">Toplam kişi<strong><?= (int) $totals['total'] ?></strong></div>
    <div class="card stat">Görünür<strong><?= (int) $totals['visible'] ?></strong></div>
    <div class="card stat">Gizli<strong><?= (int) $totals['hidden'] ?></strong></div>
</section>

<section class="panel">
    <h2><?= $editContact ? 'Kişiyi düzenle' : 'Yeni kişi ekle' ?></h2>
    <?php if ($editContact === null && $editId > 0): ?><p class="error">Düzenlenecek kişi bulunamadı.</p><?php endif; ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($editContact['id'] ?? 0) ?>">
        <label>Ad * <input name="ad" maxlength="100" required value="<?= e($editContact['ad'] ?? '') ?>"></label>
        <label>Soyad * <input name="soyad" maxlength="100" required value="<?= e($editContact['soyad'] ?? '') ?>"></label>
        <label>Telefon <input name="telefon" maxlength="40" value="<?= e($editContact['telefon'] ?? '') ?>"></label>
        <label>E-posta <input type="email" name="email" maxlength="254" value="<?= e($editContact['email'] ?? '') ?>"></label>
        <label>Adres <textarea name="adres"><?= e($editContact['adres'] ?? '') ?></textarea></label>
        <label>Notlar <textarea name="notlar"><?= e($editContact['notlar'] ?? '') ?></textarea></label>
        <div class="actions">
            <button type="submit"><?= $editContact ? 'Değişiklikleri kaydet' : 'Kişi ekle' ?></button>
            <?php if ($editContact): ?><a class="button secondary" href="index.php">Vazgeç</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Kişi yönetimi</h2>
    <form class="toolbar" method="get">
        <label>Arama <input type="search" name="q" value="<?= e($search) ?>" maxlength="100"></label>
        <label>Durum
            <select name="visibility">
                <option value="all" <?= $visibility === 'all' ? 'selected' : '' ?>>Tümü</option>
                <option value="visible" <?= $visibility === 'visible' ? 'selected' : '' ?>>Görünür</option>
                <option value="hidden" <?= $visibility === 'hidden' ? 'selected' : '' ?>>Gizli</option>
            </select>
        </label>
        <label>Sayfa başına
            <select name="per_page">
                <?php foreach ([10, 25, 50] as $size): ?>
                    <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filtrele</button>
    </form>
    <p class="muted"><?= $total ?> kişi bulundu.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ad soyad</th><th>İletişim</th><th>Durum</th><th>İşlemler</th></tr></thead>
            <tbody>
            <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td><?= e($contact['ad'] . ' ' . $contact['soyad']) ?></td>
                    <td><?= e($contact['telefon']) ?><br><?= e($contact['email']) ?></td>
                    <td><?= (int) $contact['is_hidden'] === 1 ? 'Gizli' : 'Görünür' ?></td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="?<?= e(http_build_query([
                                'edit' => $contact['id'],
                                'q' => $search,
                                'visibility' => $visibility,
                                'per_page' => $perPage,
                                'page' => $page,
                            ])) ?>">Düzenle</a>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                                <button class="secondary" type="submit"><?= (int) $contact['is_hidden'] === 1 ? 'Göster' : 'Gizle' ?></button>
                            </form>
                            <form method="post" onsubmit="return confirm('Bu kişiyi kalıcı olarak silmek istiyor musunuz?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                                <button class="danger" type="submit">Sil</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($contacts === []): ?><tr><td colspan="4">Kişi bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php render_pagination($page, $pageCount, [
        'q' => $search,
        'visibility' => $visibility,
        'per_page' => $perPage,
    ]); ?>
</section>
<?php render_footer(); ?>
