<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$errorMsg = '';

// ---- SİLME ----
if (isset($_GET['delete']) && $canDelete) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM cari_hesaplar WHERE id = :id");
    $stmt->execute(['id' => $deleteId]);
    header('Location: index.php?page=cari_hesaplar');
    exit;
}

// ---- EKLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $cariAdi = trim($_POST['cari_adi'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $bakiye  = (float)($_POST['bakiye'] ?? 0);

    if ($cariAdi === '') {
        $errorMsg = 'Cari adı zorunludur.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO cari_hesaplar (cari_adi, telefon, bakiye) VALUES (:c, :t, :b)"
        );
        $stmt->execute(['c' => $cariAdi, 't' => $telefon, 'b' => $bakiye]);
        header('Location: index.php?page=cari_hesaplar');
        exit;
    }
}

// ---- GÜNCELLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id']) && $canUpdate) {
    $updateId = (int)$_POST['update_id'];
    $cariAdi  = trim($_POST['cari_adi'] ?? '');
    $telefon  = trim($_POST['telefon'] ?? '');
    $bakiye   = (float)($_POST['bakiye'] ?? 0);

    if ($cariAdi !== '') {
        $stmt = $pdo->prepare(
            "UPDATE cari_hesaplar SET cari_adi = :c, telefon = :t, bakiye = :b WHERE id = :id"
        );
        $stmt->execute(['c' => $cariAdi, 't' => $telefon, 'b' => $bakiye, 'id' => $updateId]);
        header('Location: index.php?page=cari_hesaplar');
        exit;
    }
}

// ---- DÜZENLENEN CARİYİ ÇEK ----
$editingCari = null;
if ($editId && $canUpdate) {
    $stmt = $pdo->prepare("SELECT * FROM cari_hesaplar WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editingCari = $stmt->fetch();
}

// ---- LİSTE ----
$cariler = $pdo->query("SELECT * FROM cari_hesaplar ORDER BY cari_adi ASC")->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<?php if ($editingCari): ?>
    <!-- ================= DÜZENLEME FORMU ================= -->
    <div class="card">
        <h2>Cari Hesabı Düzenle</h2>
        <form method="post" action="index.php?page=cari_hesaplar">
            <input type="hidden" name="update_id" value="<?= (int)$editingCari['id'] ?>">

            <label>Cari Adı</label>
            <input type="text" name="cari_adi" value="<?= e($editingCari['cari_adi']) ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Telefon</label>
            <input type="text" name="telefon" value="<?= e($editingCari['telefon'] ?? '') ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Bakiye (₺)</label>
            <input type="number" step="0.01" name="bakiye" value="<?= e((string)$editingCari['bakiye']) ?>"
                   style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?page=cari_hesaplar" class="btn" style="background:#e5e7eb;">Vazgeç</a>
        </form>
    </div>
<?php else: ?>

    <?php if ($canAdd): ?>
    <!-- ================= YENİ CARİ EKLEME ================= -->
    <div class="card">
        <h2>Yeni Cari Hesap Ekle</h2>
        <form method="post" action="index.php?page=cari_hesaplar">
            <input type="hidden" name="action" value="create">

            <label>Cari Adı</label>
            <input type="text" name="cari_adi" style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Telefon</label>
            <input type="text" name="telefon" style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Bakiye (₺)</label>
            <input type="number" step="0.01" name="bakiye" value="0" style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-primary">Cari Ekle</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ================= CARİ LİSTESİ ================= -->
    <div class="card">
        <h2>Cari Hesaplar</h2>
        <table class="data-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Cari Adı</th>
                <th>Telefon</th>
                <th>Bakiye</th>
                <?php if ($canUpdate || $canDelete): ?><th style="width:160px;">İşlemler</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($cariler)): ?>
                <tr><td colspan="5">Henüz cari kaydı yok.</td></tr>
            <?php endif; ?>
            <?php foreach ($cariler as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= e($c['cari_adi']) ?></td>
                    <td><?= e($c['telefon'] ?? '-') ?></td>
                    <td><?= number_format((float)$c['bakiye'], 2) ?> ₺</td>
                    <?php if ($canUpdate || $canDelete): ?>
                        <td>
                            <?php if ($canUpdate): ?>
                                <a href="index.php?page=cari_hesaplar&edit=<?= (int)$c['id'] ?>" class="btn btn-primary">Düzenle</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="index.php?page=cari_hesaplar&delete=<?= (int)$c['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu cari hesabı silmek istediğinize emin misiniz?');">Sil</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
