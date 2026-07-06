<?php
/**
 * index.php üzerinden dahil edilir.
 * Bu sayfada CRUD yetkisi kavramı yok - herkes SADECE KENDİ şifresini değiştirebilir.
 * Erişim, normal sayfa yetkilendirme sistemi üzerinden (can_view) kontrol edilir.
 */

$errorMsg   = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword      = $_POST['new_password'] ?? '';
    $newPasswordAgain = $_POST['new_password_again'] ?? '';

    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($currentPassword === '' || $newPassword === '' || $newPasswordAgain === '') {
        $errorMsg = 'Tüm alanları doldurmanız zorunludur.';
    } elseif ($newPassword !== $newPasswordAgain) {
        $errorMsg = 'Yeni şifre ve tekrarı birbiriyle eşleşmiyor.';
    } elseif (strlen($newPassword) < 6) {
        $errorMsg = 'Yeni şifre en az 6 karakter olmalıdır.';
    } else {
        // Mevcut şifre hash'ini çek ve doğrula
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            $errorMsg = 'Mevcut şifreniz hatalı.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = :p WHERE id = :id");
            $upd->execute(['p' => $newHash, 'id' => $userId]);
            $successMsg = 'Şifreniz başarıyla güncellendi.';
        }
    }
}
?>

<div class="card" style="max-width:480px;">
    <h2>Şifremi Değiştir</h2>
    <p style="color:#6b7280; font-size:14px; margin-top:-8px;">
        Kullanıcı: <strong><?= e(currentUsername()) ?></strong>
    </p>

    <?php if ($errorMsg): ?>
        <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
        <div class="card" style="background:#dcfce7; border-color:#86efac; padding:12px 16px; margin-bottom:16px;">
            <?= e($successMsg) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=sifre_degistir">
        <label>Mevcut Şifre</label>
        <input type="password" name="current_password" style="width:100%;padding:8px;margin-bottom:12px;" required>

        <label>Yeni Şifre</label>
        <input type="password" name="new_password" style="width:100%;padding:8px;margin-bottom:12px;" required minlength="6">

        <label>Yeni Şifre (Tekrar)</label>
        <input type="password" name="new_password_again" style="width:100%;padding:8px;margin-bottom:16px;" required minlength="6">

        <button type="submit" class="btn btn-success">Şifreyi Güncelle</button>
    </form>
</div>
