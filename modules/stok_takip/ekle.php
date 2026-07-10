<?php
/**
 * Bu dosya index.php üzerinden dahil edilir.
 * Kullanılabilir hazır değişkenler: $pdo, $canAdd
 */

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canAdd) {
    $urunAdi   = trim($_POST['urun_adi'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $stokAdedi = (int)($_POST['stok_adedi'] ?? 0);

    if ($urunAdi === '') {
        $errorMsg = 'Ürün adı zorunludur.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO stok_urunleri (urun_adi, kategori, stok_adedi) VALUES (:urun_adi, :kategori, :stok_adedi)"
        );
        $stmt->execute([
            'urun_adi'   => $urunAdi,
            'kategori'   => $kategori,
            'stok_adedi' => $stokAdedi,
        ]);
        header('Location: index.php?page=stok_listesi');
        exit;
    }
}
?>

<div class="card">
    <h2>Yeni Ürün Ekle</h2>

    <?php if ($errorMsg): ?>
        <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?page=stok_ekle">
        <label>Ürün Adı</label>
        <input type="text" name="urun_adi" style="width:100%;padding:8px;margin-bottom:12px;" required>
        <label>Kategori</label>
        <input type="text" name="kategori" style="width:100%;padding:8px;margin-bottom:12px;">
        <label>Stok Adedi</label>
        <input type="number" name="stok_adedi" value="0" style="width:100%;padding:8px;margin-bottom:16px;">

        <button type="submit" class="btn btn-success">Kaydet</button>
        <a href="index.php?page=stok_listesi" class="btn" style="background:#e5e7eb;">Vazgeç</a>
    </form>
</div>
