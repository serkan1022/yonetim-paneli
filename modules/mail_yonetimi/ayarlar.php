<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canUpdate
 */

$errorMsg   = '';
$successMsg = '';

$settings = $pdo->query("SELECT * FROM mail_settings ORDER BY id ASC LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canUpdate) {
    $host      = trim($_POST['smtp_host'] ?? '');
    $port      = (int)($_POST['smtp_port'] ?? 587);
    $username  = trim($_POST['smtp_username'] ?? '');
    $password  = $_POST['smtp_password'] ?? '';
    $enc       = $_POST['smtp_encryption'] ?? 'tls';
    $fromEmail = trim($_POST['gonderen_eposta'] ?? '');
    $fromName  = trim($_POST['gonderen_adi'] ?? '');

    if ($host === '' || $username === '' || $fromEmail === '') {
        $errorMsg = 'SMTP sunucu, kullanıcı adı ve gönderen e-posta zorunludur.';
    } else {
        if ($settings) {
            $finalPassword = $password !== '' ? $password : $settings['smtp_password'];
            $stmt = $pdo->prepare(
                "UPDATE mail_settings
                 SET smtp_host=:h, smtp_port=:p, smtp_username=:u, smtp_password=:pw,
                     smtp_encryption=:enc, gonderen_eposta=:fe, gonderen_adi=:fn
                 WHERE id=:id"
            );
            $stmt->execute([
                'h' => $host, 'p' => $port, 'u' => $username, 'pw' => $finalPassword,
                'enc' => $enc, 'fe' => $fromEmail, 'fn' => $fromName, 'id' => $settings['id'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO mail_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, gonderen_eposta, gonderen_adi)
                 VALUES (:h, :p, :u, :pw, :enc, :fe, :fn)"
            );
            $stmt->execute([
                'h' => $host, 'p' => $port, 'u' => $username, 'pw' => $password,
                'enc' => $enc, 'fe' => $fromEmail, 'fn' => $fromName,
            ]);
        }
        header('Location: index.php?page=mail_ayarlari&saved=1');
        exit;
    }
    $settings = $pdo->query("SELECT * FROM mail_settings ORDER BY id ASC LIMIT 1")->fetch();
}

if (isset($_GET['saved'])) {
    $successMsg = 'Mail ayarları kaydedildi.';
}
?>

<div class="card" style="max-width:560px;">
    <h2>Mail Ayarları (SMTP)</h2>

    <?php if ($errorMsg): ?>
        <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
        <div class="card" style="background:#dcfce7; border-color:#86efac; padding:12px 16px; margin-bottom:16px;">
            <?= e($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($canUpdate): ?>
    <form method="post" action="index.php?page=mail_ayarlari">
        <label>SMTP Sunucu (Gmail için: smtp.gmail.com)</label>
        <input type="text" name="smtp_host" value="<?= e($settings['smtp_host'] ?? 'smtp.gmail.com') ?>"
               style="width:100%;padding:8px;margin-bottom:12px;" required>

        <label>SMTP Port (Gmail için: 587)</label>
        <input type="number" name="smtp_port" value="<?= e((string)($settings['smtp_port'] ?? 587)) ?>"
               style="width:100%;padding:8px;margin-bottom:12px;">

        <label>Şifreleme</label>
        <select name="smtp_encryption" style="width:100%;padding:8px;margin-bottom:12px;">
            <?php foreach (['tls' => 'TLS (Gmail için önerilen)', 'ssl' => 'SSL', 'none' => 'Yok'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($settings['smtp_encryption'] ?? 'tls') === $val ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>SMTP Kullanıcı Adı (Gmail adresiniz)</label>
        <input type="text" name="smtp_username" value="<?= e($settings['smtp_username'] ?? '') ?>"
               style="width:100%;padding:8px;margin-bottom:12px;" required>

        <label>SMTP Şifre (Gmail Uygulama Şifresi - boş bırakılırsa değişmez)</label>
        <input type="password" name="smtp_password" style="width:100%;padding:8px;margin-bottom:12px;"
               placeholder="<?= $settings ? '••••••••' : '' ?>">
        <p style="font-size:12px;color:#6b7280;margin-top:-8px;margin-bottom:12px;">
            Gmail kullanıyorsanız normal şifreniz değil, Google Hesabı → Güvenlik → Uygulama Şifreleri'nden
            oluşturulan 16 haneli özel şifre girilmelidir.
        </p>

        <label>Gönderen E-posta</label>
        <input type="email" name="gonderen_eposta" value="<?= e($settings['gonderen_eposta'] ?? '') ?>"
               style="width:100%;padding:8px;margin-bottom:12px;" required>

        <label>Gönderen Adı</label>
        <input type="text" name="gonderen_adi" value="<?= e($settings['gonderen_adi'] ?? 'Yönetim Paneli') ?>"
               style="width:100%;padding:8px;margin-bottom:16px;">

        <button type="submit" class="btn btn-success">Kaydet</button>
    </form>
    <?php else: ?>
        <p style="color:#6b7280;">Ayarları görüntüleyebiliyorsunuz ancak değiştirme izniniz yok.</p>
    <?php endif; ?>
</div>
