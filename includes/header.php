<?php
/**
 * $pageTitle ve $currentPageKey değişkenleri bu include'dan önce
 * index.php içinde ayarlanmış olmalı.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Panel') ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">Yönetim Paneli</div>
        <nav class="sidebar-menu">
            <?php foreach ($menuData as $moduleKey => $module): ?>
                <div class="menu-module">
                    <div class="menu-module-title"><?= e($module['module_name']) ?></div>
                    <ul>
                        <?php foreach ($module['items'] as $item): ?>
                            <li>
                                <a href="index.php?page=<?= urlencode($item['page_key']) ?>"
                                   class="<?= ($currentPageKey ?? '') === $item['page_key'] ? 'active' : '' ?>">
                                    <?= e($item['menu_title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
            <div class="topbar-user">
                <span><?= e(currentFullName()) ?> (<?= e(currentUsername()) ?>)</span>
                <a href="logout.php" class="btn-logout">Çıkış Yap</a>
            </div>
        </header>
        <main class="content-area">
