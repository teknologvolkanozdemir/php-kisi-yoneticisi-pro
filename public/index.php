<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$search = trim(input_string($_GET, 'q'));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 25, 50], true)) {
    $perPage = 10;
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$where = ['is_hidden = 0'];
$parameters = [];

if ($search !== '') {
    $where[] = "(ad LIKE :ad ESCAPE '=' OR soyad LIKE :soyad ESCAPE '=' OR telefon LIKE :telefon ESCAPE '=' OR email LIKE :email ESCAPE '=')";
    $prefix = prefix_search_pattern($search);
    $parameters = ['ad' => $prefix, 'soyad' => $prefix, 'telefon' => $prefix, 'email' => $prefix];
}
$whereSql = implode(' AND ', $where);
$count = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE $whereSql");
$count->execute($parameters);
$total = (int) $count->fetchColumn();
$pageCount = max(1, (int) ceil($total / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT id, ad, soyad, telefon, email, adres, notlar, created_at
     FROM contacts WHERE $whereSql
     ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset"
);
foreach ($parameters as $name => $value) {
    $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$contacts = $statement->fetchAll();

render_header('Kişiler');
?>
<section class="panel">
    <p>Yalnızca görünür kişiler bu sayfada listelenir.</p>
    <form class="toolbar" method="get">
        <label>Ad, soyad, telefon veya e-posta
            <input type="search" name="q" value="<?= e($search) ?>" maxlength="100">
        </label>
        <label>Sayfa başına
            <select name="per_page">
                <?php foreach ([10, 25, 50] as $size): ?>
                    <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Ara</button>
    </form>
</section>
<p class="muted"><?= $total ?> kişi bulundu.</p>
<?php if ($contacts === []): ?>
    <section class="panel">Gösterilecek kişi bulunamadı.</section>
<?php else: ?>
    <?php foreach ($contacts as $contact): ?>
        <article class="card">
            <h2><?= e($contact['ad'] . ' ' . $contact['soyad']) ?></h2>
            <?php if ($contact['telefon'] !== ''): ?><p><strong>Telefon:</strong> <?= e($contact['telefon']) ?></p><?php endif; ?>
            <?php if ($contact['email'] !== ''): ?><p><strong>E-posta:</strong> <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a></p><?php endif; ?>
            <?php if ($contact['adres'] !== ''): ?><p><strong>Adres:</strong> <?= nl2br(e($contact['adres'])) ?></p><?php endif; ?>
            <?php if ($contact['notlar'] !== ''): ?><p><?= nl2br(e($contact['notlar'])) ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php render_pagination($page, $pageCount, ['q' => $search, 'per_page' => $perPage], 'index.php'); ?>
<?php render_footer(); ?>
