<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/auth.php';

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
        $error = 'Kullanıcı adı ve şifre zorunludur.';
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
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - Yönetim Paneli</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <h1>Yönetim Paneli Girişi</h1>
        <?php if ($error): ?>
            <div class="login-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" autofocus required>

            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</div>
</body>
</html>
