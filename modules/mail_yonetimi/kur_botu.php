<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

require_once __DIR__ . '/../../includes/kur_helper.php';

$errorMsg   = '';
$successMsg = '';

// ---- Silme ----
if (isset($_GET['delete']) && $canDelete) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM kur_botu_aliciler WHERE id = :id")->execute(['id' => $id]);
    header('Location: index.php?page=kur_botu_ayarlari');
    exit;
}

// ---- Aktif/Pasif Toggle ----
if (isset($_GET['toggle']) && $canUpdate) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE kur_botu_aliciler SET aktif = 1 - aktif WHERE id = :id")->execute(['id' => $id]);
    header('Location: index.php?page=kur_botu_ayarlari');
    exit;
}

// ---- Yeni Alıcı Ekleme ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $eposta = trim($_POST['eposta'] ?? '');
    $adSoyad = trim($_POST['ad_soyad'] ?? '');

    if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Geçerli bir e-posta adresi giriniz.';
    } else {
        $pdo->prepare("INSERT INTO kur_botu_aliciler (eposta, ad_soyad) VALUES (:e, :a)")
            ->execute(['e' => $eposta, 'a' => $adSoyad]);
        header('Location: index.php?page=kur_botu_ayarlari');
        exit;
    }
}

// ---- Şimdi Test Gönderimi (manuel kuyruğa ekleme) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_now' && $canAdd) {
    $result = enqueueKurBotuMails($pdo);
    if ($result === -1) {
        $errorMsg = 'Güncel kur verileri alınamadı. İnternet bağlantısını kontrol edin.';
    } elseif ($result === 0) {
        $errorMsg = 'Kuyruğa hiçbir şey eklenmedi - aktif alıcı bulunmuyor.';
    } else {
        $successMsg = "{$result} alıcı için kur maili kuyruğa eklendi. Gerçek gönderim için Mail Kuyruğu sayfasından takip edebilirsiniz (worker çalıştığında gönderilecek).";
    }
}

$aliciler = $pdo->query("SELECT * FROM kur_botu_aliciler ORDER BY id DESC")->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
    <div class="card" style="background:#dcfce7; border-color:#86efac; padding:12px 16px; margin-bottom:16px;">
        <?= e($successMsg) ?>
    </div>
<?php endif; ?>

<?php if ($canAdd): ?>
<div class="card">
    <h2>Şimdi Test Gönderimi Yap</h2>
    <p style="color:#6b7280; font-size:14px;">
        Güncel kurları hemen çeker ve aktif tüm alıcılar için Mail Kuyruğu'na ekler.
        Gerçek gönderim, <code>send_mail_worker.php</code> bir sonraki çalıştığında yapılır.
    </p>
    <form method="post" action="index.php?page=kur_botu_ayarlari">
        <input type="hidden" name="action" value="send_now">
        <button type="submit" class="btn btn-primary">Şimdi Kuyruğa Ekle</button>
    </form>
</div>

<div class="card">
    <h2>Yeni Alıcı Ekle</h2>
    <form method="post" action="index.php?page=kur_botu_ayarlari">
        <input type="hidden" name="action" value="create">
        <label>E-posta</label>
        <input type="email" name="eposta" style="width:100%;padding:8px;margin-bottom:12px;" required>
        <label>Ad Soyad (opsiyonel)</label>
        <input type="text" name="ad_soyad" style="width:100%;padding:8px;margin-bottom:16px;">
        <button type="submit" class="btn btn-success">Alıcı Ekle</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Kur Botu Alıcı Listesi</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>E-posta</th>
            <th>Ad Soyad</th>
            <th>Durum</th>
            <?php if ($canUpdate || $canDelete): ?><th style="width:180px;">İşlemler</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($aliciler)): ?>
            <tr><td colspan="5">Henüz alıcı eklenmemiş.</td></tr>
        <?php endif; ?>
        <?php foreach ($aliciler as $a): ?>
            <tr>
                <td><?= (int)$a['id'] ?></td>
                <td><?= e($a['eposta']) ?></td>
                <td><?= e($a['ad_soyad'] ?? '-') ?></td>
                <td>
                    <?php if ($a['aktif']): ?>
                        <span class="badge badge-yes">Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-no">Pasif</span>
                    <?php endif; ?>
                </td>
                <?php if ($canUpdate || $canDelete): ?>
                    <td>
                        <?php if ($canUpdate): ?>
                            <a href="index.php?page=kur_botu_ayarlari&toggle=<?= (int)$a['id'] ?>" class="btn btn-primary">
                                <?= $a['aktif'] ? 'Pasif Yap' : 'Aktif Yap' ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="index.php?page=kur_botu_ayarlari&delete=<?= (int)$a['id'] ?>" class="btn btn-danger"
                               onclick="return confirm('Bu alıcıyı silmek istediğinize emin misiniz?');">Sil</a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
