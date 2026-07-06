<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo
 */

$DUSUK_STOK_ESIGI = 10;

$products = $pdo->query("SELECT * FROM stok_urunleri ORDER BY stok_adedi ASC")->fetchAll();

$toplamAdet   = 0;
$dusukStokSayisi = 0;
foreach ($products as $p) {
    $toplamAdet += (int)$p['stok_adedi'];
    if ((int)$p['stok_adedi'] <= $DUSUK_STOK_ESIGI) {
        $dusukStokSayisi++;
    }
}
?>

<div class="card">
    <h2>Stok Raporu</h2>

    <div style="display:flex; gap:16px; margin-bottom:20px;">
        <div style="flex:1; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
            <div style="font-size:13px;color:#6b7280;">Toplam Ürün Çeşidi</div>
            <div style="font-size:24px;font-weight:700;"><?= count($products) ?></div>
        </div>
        <div style="flex:1; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:16px;">
            <div style="font-size:13px;color:#6b7280;">Toplam Stok Adedi</div>
            <div style="font-size:24px;font-weight:700;"><?= $toplamAdet ?></div>
        </div>
        <div style="flex:1; background:<?= $dusukStokSayisi > 0 ? '#fef2f2' : '#f9fafb' ?>; border:1px solid <?= $dusukStokSayisi > 0 ? '#fecaca' : '#e5e7eb' ?>; border-radius:8px; padding:16px;">
            <div style="font-size:13px;color:#6b7280;">Kritik Stoklu Ürün (≤<?= $DUSUK_STOK_ESIGI ?>)</div>
            <div style="font-size:24px;font-weight:700;color:<?= $dusukStokSayisi > 0 ? '#991b1b' : '#111827' ?>;"><?= $dusukStokSayisi ?></div>
        </div>
    </div>

    <table class="data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Ürün Adı</th>
            <th>Kategori</th>
            <th>Stok Adedi</th>
            <th>Durum</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="5">Henüz ürün bulunmuyor.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= e($p['urun_adi']) ?></td>
                <td><?= e($p['kategori'] ?? '-') ?></td>
                <td><?= (int)$p['stok_adedi'] ?></td>
                <td>
                    <?php if ((int)$p['stok_adedi'] <= $DUSUK_STOK_ESIGI): ?>
                        <span class="badge badge-no">Kritik</span>
                    <?php else: ?>
                        <span class="badge badge-yes">Yeterli</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
