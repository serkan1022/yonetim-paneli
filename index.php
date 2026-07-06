<?php
// Çıktı tamponlama: sayfa içeriği (header.php dahil) render edilmeye başlasa bile
// modül dosyaları içinde header()/redirect kullanılabilmesi için gerekli.
ob_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

requireLogin();

$groupId  = currentGroupId();
$menuData = getMenuForGroup($pdo, $groupId);

$pageKey = $_GET['page'] ?? '';

if ($pageKey === '') {
    // ---- Varsayılan ana sayfa (dashboard) ----
    $pageTitle      = 'Ana Sayfa';
    $currentPageKey = '';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card">
        <h2>Hoş geldin, <?= e(currentFullName()) ?></h2>
        <p>Grubun: <strong><?= e($_SESSION['group_name'] ?? '') ?></strong></p>
        <p>Sol menüden, yetkin olan modül ve sayfalara erişebilirsin.</p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- URL'den gelen sayfa anahtarına göre veritabanından sayfayı bul ----
$page = getPageByKey($pdo, $pageKey);

if (!$page) {
    http_response_code(404);
    $pageTitle      = 'Sayfa Bulunamadı';
    $currentPageKey = $pageKey;
    require __DIR__ . '/includes/header.php';
    echo '<div class="card"><h2>404 - Sayfa Bulunamadı</h2><p>Aradığınız sayfa tanımlı değil.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- Görme yetkisi kontrolü ----
requirePermission($pdo, (int)$page['id'], 'view');

// ---- Sekme başlığı veritabanından dinamik olarak geliyor ----
$pageTitle      = $page['page_title'];
$currentPageKey = $pageKey;

// Modül dosyasının erişebileceği yetki bilgileri
$canAdd    = groupHasPermission($pdo, $groupId, (int)$page['id'], 'add');
$canUpdate = groupHasPermission($pdo, $groupId, (int)$page['id'], 'update');
$canDelete = groupHasPermission($pdo, $groupId, (int)$page['id'], 'delete');

require __DIR__ . '/includes/header.php';

$targetFile = __DIR__ . '/' . $page['file_path'];
if (file_exists($targetFile)) {
    require $targetFile;
} else {
    echo '<div class="card"><h2>Dosya Bulunamadı</h2><p>Bu sayfaya ait fiziksel dosya (' . e($page['file_path']) . ') mevcut değil.</p></div>';
}

require __DIR__ . '/includes/footer.php';
