<?php
/**
 * $pageTitle ve $currentPageKey değişkenleri bu include'dan önce
 * index.php içinde ayarlanmış olmalı. $pdo üzerinden includes/lang.php
 * zaten index.php tarafından yüklenmiş olmalı (t(), currentLang(), currentTheme()).
 */
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>" data-theme="<?= e(currentTheme()) ?>">
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
        <div class="sidebar-brand"><?= e(t('brand_title')) ?></div>
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
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="topbar-controls">
                    <a href="javascript:void(0)" onclick="panelSwitchLang('tr')"
                       style="font-weight:<?= currentLang() === 'tr' ? '700' : '400' ?>;">TR</a>
                    <span style="color:var(--border-color);">|</span>
                    <a href="javascript:void(0)" onclick="panelSwitchLang('en')"
                       style="font-weight:<?= currentLang() === 'en' ? '700' : '400' ?>;">EN</a>
                    <button type="button" class="btn btn-theme" onclick="panelToggleTheme()"
                            title="<?= currentTheme() === 'light' ? e(t('theme_dark_tooltip')) : e(t('theme_light_tooltip')) ?>">
                        <?= currentTheme() === 'light' ? '🌙' : '☀️' ?>
                    </button>
                </div>
                <div class="topbar-user">
                    <span><?= e(currentFullName()) ?> (<?= e(currentUsername()) ?>)</span>
                    <a href="logout.php" class="btn-logout"><?= e(t('logout')) ?></a>
                </div>
            </div>
        </header>
        <main class="content-area">
