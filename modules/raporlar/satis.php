<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

$errorMsg = '';

// ---- Satış kaydı ekleme (sadece can_add yetkisi olan görebilir - genelde admin) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sale' && $canAdd) {
    $urunId = (int)($_POST['urun_id'] ?? 0);
    $adet   = (int)($_POST['adet'] ?? 0);
    $fiyat  = (float)($_POST['birim_fiyat'] ?? 0);

    if ($urunId && $adet > 0) {
        $pdo->beginTransaction();
        try {
            // Stok yeterli mi kontrol et
            $stmt = $pdo->prepare("SELECT stok_adedi FROM stok_urunleri WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $urunId]);
            $mevcut = $stmt->fetch();

            if (!$mevcut || $mevcut['stok_adedi'] < $adet) {
                $errorMsg = 'Yetersiz stok.';
                $pdo->rollBack();
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO satislar (urun_id, adet, birim_fiyat) VALUES (:u, :a, :f)"
                );
                $ins->execute(['u' => $urunId, 'a' => $adet, 'f' => $fiyat]);

                $upd = $pdo->prepare("UPDATE stok_urunleri SET stok_adedi = stok_adedi - :a WHERE id = :id");
                $upd->execute(['a' => $adet, 'id' => $urunId]);

                $pdo->commit();
                header('Location: index.php?page=satis_raporu');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = 'Satış kaydedilirken bir hata oluştu.';
        }
    } else {
        $errorMsg = 'Ürün ve adet seçimi zorunludur.';
    }
}

// ---- Rapor verisi ----
$sales = $pdo->query(
    "SELECT s.*, u.urun_adi, (s.adet * s.birim_fiyat) AS toplam
     FROM satislar s
     INNER JOIN stok_urunleri u ON u.id = s.urun_id
     ORDER BY s.satis_tarihi DESC"
)->fetchAll();

$genelToplam = 0;
foreach ($sales as $s) {
    $genelToplam += $s['toplam'];
}

$stokListesi = $pdo->query("SELECT id, urun_adi, stok_adedi FROM stok_urunleri ORDER BY urun_adi")->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<?php if ($canAdd): ?>
<div class="card">
    <h2>Yeni Satış Kaydı</h2>
    <form method="post" action="index.php?page=satis_raporu">
        <input type="hidden" name="action" value="create_sale">

        <label>Ürün</label>
        <select name="urun_id" style="width:100%;padding:8px;margin-bottom:12px;" required>
            <option value="">-- Ürün Seçin --</option>
            <?php foreach ($stokListesi as $st): ?>
                <option value="<?= (int)$st['id'] ?>">
                    <?= e($st['urun_adi']) ?> (Stokta: <?= (int)$st['stok_adedi'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label>Adet</label>
        <input type="number" name="adet" min="1" style="width:100%;padding:8px;margin-bottom:12px;" required>

        <label>Birim Fiyat (₺)</label>
        <input type="number" name="birim_fiyat" step="0.01" min="0" style="width:100%;padding:8px;margin-bottom:16px;" required>

        <button type="submit" class="btn btn-success">Satışı Kaydet</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Satış Raporu</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Ürün</th>
            <th>Adet</th>
            <th>Birim Fiyat</th>
            <th>Toplam</th>
            <th>Tarih</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($sales)): ?>
            <tr><td colspan="6">Henüz satış kaydı yok.</td></tr>
        <?php endif; ?>
        <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= (int)$s['id'] ?></td>
                <td><?= e($s['urun_adi']) ?></td>
                <td><?= (int)$s['adet'] ?></td>
                <td><?= number_format((float)$s['birim_fiyat'], 2) ?> ₺</td>
                <td><?= number_format((float)$s['toplam'], 2) ?> ₺</td>
                <td><?= e($s['satis_tarihi']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if (!empty($sales)): ?>
        <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:600;">Genel Toplam:</td>
            <td style="font-weight:600;"><?= number_format($genelToplam, 2) ?> ₺</td>
            <td></td>
        </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
