<?php
/**
 * Bu dosya index.php üzerinden dahil edilir.
 * Kullanılabilir hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete, $page (mevcut sayfa kaydı)
 */

$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// ---- SİLME İŞLEMİ ----
if (isset($_GET['delete']) && $canDelete) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM stok_urunleri WHERE id = :id");
    $stmt->execute(['id' => $deleteId]);
    header('Location: index.php?page=stok_listesi');
    exit;
}

// ---- GÜNCELLEME İŞLEMİ ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id']) && $canUpdate) {
    $updateId   = (int)$_POST['update_id'];
    $urunAdi    = trim($_POST['urun_adi'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? '');
    $stokAdedi  = (int)($_POST['stok_adedi'] ?? 0);

    if ($urunAdi !== '') {
        $stmt = $pdo->prepare(
            "UPDATE stok_urunleri SET urun_adi = :urun_adi, kategori = :kategori, stok_adedi = :stok_adedi WHERE id = :id"
        );
        $stmt->execute([
            'urun_adi'   => $urunAdi,
            'kategori'   => $kategori,
            'stok_adedi' => $stokAdedi,
            'id'         => $updateId,
        ]);
        header('Location: index.php?page=stok_listesi');
        exit;
    }
}

$editingProduct = null;
if ($editId && $canUpdate) {
    $stmt = $pdo->prepare("SELECT * FROM stok_urunleri WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editingProduct = $stmt->fetch();
}

// ---- LİSTEYİ ÇEK ----
$products = $pdo->query("SELECT * FROM stok_urunleri ORDER BY id DESC")->fetchAll();
?>

<?php if ($editingProduct): ?>
    <!-- ================= DÜZENLEME FORMU ================= -->
    <div class="card">
        <h2>Ürünü Düzenle</h2>
        <form method="post" action="index.php?page=stok_listesi">
            <input type="hidden" name="update_id" value="<?= (int)$editingProduct['id'] ?>">

            <label>Ürün Adı</label>
            <input type="text" name="urun_adi" value="<?= e($editingProduct['urun_adi']) ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Kategori</label>
            <input type="text" name="kategori" value="<?= e($editingProduct['kategori'] ?? '') ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Stok Adedi</label>
            <input type="number" name="stok_adedi" value="<?= (int)$editingProduct['stok_adedi'] ?>"
                   style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?page=stok_listesi" class="btn" style="background:#e5e7eb;">Vazgeç</a>
        </form>
    </div>

<?php else: ?>
    <!-- ================= LİSTE GÖRÜNÜMÜ ================= -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="margin:0;">Stok Listesi</h2>
            <?php if ($canAdd): ?>
                <a href="index.php?page=stok_ekle" class="btn btn-primary">+ Yeni Ürün</a>
            <?php endif; ?>
        </div>

        <table class="data-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Ürün Adı</th>
                <th>Kategori</th>
                <th>Stok Adedi</th>
                <?php if ($canUpdate || $canDelete): ?><th style="width:180px;">İşlemler</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="5">Henüz ürün eklenmemiş.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td><?= e($p['urun_adi']) ?></td>
                    <td><?= e($p['kategori'] ?? '-') ?></td>
                    <td><?= (int)$p['stok_adedi'] ?></td>
                    <?php if ($canUpdate || $canDelete): ?>
                        <td>
                            <?php if ($canUpdate): ?>
                                <a href="index.php?page=stok_listesi&edit=<?= (int)$p['id'] ?>" class="btn btn-primary">Düzenle</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="index.php?page=stok_listesi&delete=<?= (int)$p['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">Sil</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
