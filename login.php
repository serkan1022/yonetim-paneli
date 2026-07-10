<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/lang.php';

// Zaten giriş yapmışsa doğrudan panele yönlendir
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = t('login_error_empty');
    } else {
        $stmt = $pdo->prepare(
            "SELECT u.*, g.group_name
             FROM users u
             INNER JOIN groups_table g ON g.id = u.group_id
             WHERE u.username = :username AND u.is_active = 1
             LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['group_id']   = $user['group_id'];
            $_SESSION['group_name'] = $user['group_name'];

            // Son giriş zamanını güncelle
            $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $upd->execute(['id' => $user['id']]);

            header('Location: index.php');
            exit;
        } else {
            $error = t('login_error_wrong');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>" data-theme="<?= e(currentTheme()) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= e(t('login_title')) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:16px;">
            <a href="javascript:void(0)" onclick="panelSwitchLang('tr')"
               style="text-decoration:none; color:var(--text-dark); font-size:13px; font-weight:<?= currentLang() === 'tr' ? '700' : '400' ?>;">TR</a>
            <span style="color:var(--border-color);">|</span>
            <a href="javascript:void(0)" onclick="panelSwitchLang('en')"
               style="text-decoration:none; color:var(--text-dark); font-size:13px; font-weight:<?= currentLang() === 'en' ? '700' : '400' ?>;">EN</a>
            <button type="button" class="btn btn-theme" onclick="panelToggleTheme()"
                    title="<?= currentTheme() === 'light' ? e(t('theme_dark_tooltip')) : e(t('theme_light_tooltip')) ?>">
                <?= currentTheme() === 'light' ? '🌙' : '☀️' ?>
            </button>
        </div>

        <h1><?= e(t('login_title')) ?></h1>
        <?php if ($error): ?>
            <div class="login-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username"><?= e(t('username')) ?></label>
            <input type="text" id="username" name="username" autofocus required>

            <label for="password"><?= e(t('password')) ?></label>
            <input type="password" id="password" name="password" required>

            <button type="submit"><?= e(t('login_button')) ?></button>
        </form>
    </div>
</div>
<script src="assets/js/theme_lang.js"></script>
</body>
</html>
