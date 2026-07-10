<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

$errorMsg   = '';
$successMsg = '';

// ---- Yeni Lisans Oluşturma ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $etiket = trim($_POST['etiket'] ?? '');
    $yeniKod = bin2hex(random_bytes(20)); // 40 karakterlik güvenli rastgele kod

    $stmt = $pdo->prepare("INSERT INTO api_lisanslar (lisans_kodu, etiket) VALUES (:k, :e)");
    $stmt->execute(['k' => $yeniKod, 'e' => $etiket]);

    header('Location: index.php?page=lisans_yonetimi&created=' . urlencode($yeniKod));
    exit;
}

// ---- Aktif/Pasif Toggle ----
if (isset($_GET['toggle']) && $canUpdate) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE api_lisanslar SET aktif = 1 - aktif WHERE id = :id")->execute(['id' => $id]);
    header('Location: index.php?page=lisans_yonetimi');
    exit;
}

// ---- Silme ----
if (isset($_GET['delete']) && $canDelete) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM api_lisanslar WHERE id = :id")->execute(['id' => $id]);
    header('Location: index.php?page=lisans_yonetimi');
    exit;
}

if (isset($_GET['created'])) {
    $successMsg = 'Yeni lisans kodu oluşturuldu (aşağıda tek seferlik gösteriliyor, kopyalamayı unutma): ' . $_GET['created'];
}

$lisanslar = $pdo->query(
    "SELECT l.*, i.son_guncelleme, i.bilgisayar_adi
     FROM api_lisanslar l
     LEFT JOIN istemci_bilgileri i ON i.lisans_id = l.id
     ORDER BY l.id DESC"
)->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
    <div class="card" style="background:#dcfce7; border-color:#86efac; padding:12px 16px; margin-bottom:16px; word-break:break-all;">
        <?= e($successMsg) ?>
    </div>
<?php endif; ?>

<?php if ($canAdd): ?>
<div class="card">
    <h2>Yeni Lisans Oluştur</h2>
    <p style="color:#6b7280; font-size:14px;">
        Her istemci bilgisayara (C# uygulamasına) ayrı bir lisans kodu vermen önerilir,
        böylece hangi kaydın hangi bilgisayardan geldiğini net ayırt edebilirsin.
    </p>
    <form method="post" action="index.php?page=lisans_yonetimi">
        <input type="hidden" name="action" value="create">
        <label>Etiket (örn. "Muhasebe PC" - opsiyonel, tanıma kolaylığı için)</label>
        <input type="text" name="etiket" style="width:100%;padding:8px;margin-bottom:16px;">
        <button type="submit" class="btn btn-success">Lisans Oluştur</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Lisans Listesi</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Etiket</th>
            <th>Lisans Kodu</th>
            <th>Durum</th>
            <th>Son Kullanım (İstemci)</th>
            <th>Bilgisayar</th>
            <?php if ($canUpdate || $canDelete): ?><th style="width:180px;">İşlemler</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($lisanslar)): ?>
            <tr><td colspan="7">Henüz lisans oluşturulmamış.</td></tr>
        <?php endif; ?>
        <?php foreach ($lisanslar as $l): ?>
            <tr>
                <td><?= (int)$l['id'] ?></td>
                <td><?= e($l['etiket'] ?? '-') ?></td>
                <td style="font-family:monospace; font-size:12px;"><?= e($l['lisans_kodu']) ?></td>
                <td>
                    <?php if ($l['aktif']): ?>
                        <span class="badge badge-yes">Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-no">Pasif</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;"><?= $l['son_guncelleme'] ? e($l['son_guncelleme']) : '-' ?></td>
                <td><?= e($l['bilgisayar_adi'] ?? '-') ?></td>
                <?php if ($canUpdate || $canDelete): ?>
                    <td>
                        <?php if ($canUpdate): ?>
                            <a href="index.php?page=lisans_yonetimi&toggle=<?= (int)$l['id'] ?>" class="btn btn-primary">
                                <?= $l['aktif'] ? 'Pasif Yap' : 'Aktif Yap' ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="index.php?page=lisans_yonetimi&delete=<?= (int)$l['id'] ?>" class="btn btn-danger"
                               onclick="return confirm('Bu lisansı silmek istediğinize emin misiniz? Bağlı istemci verileri de silinecek.');">Sil</a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
