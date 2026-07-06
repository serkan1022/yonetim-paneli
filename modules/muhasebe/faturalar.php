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
    $stmt = $pdo->prepare("DELETE FROM faturalar WHERE id = :id");
    $stmt->execute(['id' => $deleteId]);
    header('Location: index.php?page=fatura_listesi');
    exit;
}

// ---- EKLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $faturaNo = trim($_POST['fatura_no'] ?? '');
    $cariAdi  = trim($_POST['cari_adi'] ?? '');
    $tutar    = (float)($_POST['tutar'] ?? 0);
    $durum    = $_POST['durum'] ?? 'Bekliyor';
    $tarih    = $_POST['fatura_tarihi'] ?? date('Y-m-d');

    if ($faturaNo === '' || $cariAdi === '') {
        $errorMsg = 'Fatura no ve cari adı zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO faturalar (fatura_no, cari_adi, tutar, durum, fatura_tarihi)
                 VALUES (:fn, :c, :t, :d, :dt)"
            );
            $stmt->execute(['fn' => $faturaNo, 'c' => $cariAdi, 't' => $tutar, 'd' => $durum, 'dt' => $tarih]);
            header('Location: index.php?page=fatura_listesi');
            exit;
        } catch (PDOException $e) {
            $errorMsg = 'Bu fatura numarası zaten kayıtlı.';
        }
    }
}

// ---- GÜNCELLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id']) && $canUpdate) {
    $updateId = (int)$_POST['update_id'];
    $cariAdi  = trim($_POST['cari_adi'] ?? '');
    $tutar    = (float)($_POST['tutar'] ?? 0);
    $durum    = $_POST['durum'] ?? 'Bekliyor';
    $tarih    = $_POST['fatura_tarihi'] ?? date('Y-m-d');

    if ($cariAdi !== '') {
        $stmt = $pdo->prepare(
            "UPDATE faturalar SET cari_adi = :c, tutar = :t, durum = :d, fatura_tarihi = :dt WHERE id = :id"
        );
        $stmt->execute(['c' => $cariAdi, 't' => $tutar, 'd' => $durum, 'dt' => $tarih, 'id' => $updateId]);
        header('Location: index.php?page=fatura_listesi');
        exit;
    }
}

// ---- DÜZENLENEN FATURAYI ÇEK ----
$editingInvoice = null;
if ($editId && $canUpdate) {
    $stmt = $pdo->prepare("SELECT * FROM faturalar WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editingInvoice = $stmt->fetch();
}

// ---- LİSTE ----
$invoices = $pdo->query("SELECT * FROM faturalar ORDER BY fatura_tarihi DESC")->fetchAll();

$durumSecenekleri = ['Bekliyor', 'Ödendi', 'İptal'];
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<?php if ($editingInvoice): ?>
    <!-- ================= DÜZENLEME FORMU ================= -->
    <div class="card">
        <h2>Faturayı Düzenle: <?= e($editingInvoice['fatura_no']) ?></h2>
        <form method="post" action="index.php?page=fatura_listesi">
            <input type="hidden" name="update_id" value="<?= (int)$editingInvoice['id'] ?>">

            <label>Cari Adı</label>
            <input type="text" name="cari_adi" value="<?= e($editingInvoice['cari_adi']) ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Tutar (₺)</label>
            <input type="number" step="0.01" name="tutar" value="<?= e((string)$editingInvoice['tutar']) ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Durum</label>
            <select name="durum" style="width:100%;padding:8px;margin-bottom:12px;">
                <?php foreach ($durumSecenekleri as $d): ?>
                    <option value="<?= e($d) ?>" <?= $editingInvoice['durum'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fatura Tarihi</label>
            <input type="date" name="fatura_tarihi" value="<?= e($editingInvoice['fatura_tarihi']) ?>"
                   style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?page=fatura_listesi" class="btn" style="background:#e5e7eb;">Vazgeç</a>
        </form>
    </div>
<?php else: ?>

    <?php if ($canAdd): ?>
    <!-- ================= YENİ FATURA EKLEME ================= -->
    <div class="card">
        <h2>Yeni Fatura Ekle</h2>
        <form method="post" action="index.php?page=fatura_listesi">
            <input type="hidden" name="action" value="create">

            <label>Fatura No</label>
            <input type="text" name="fatura_no" style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Cari Adı</label>
            <input type="text" name="cari_adi" style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Tutar (₺)</label>
            <input type="number" step="0.01" name="tutar" style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Durum</label>
            <select name="durum" style="width:100%;padding:8px;margin-bottom:12px;">
                <?php foreach ($durumSecenekleri as $d): ?>
                    <option value="<?= e($d) ?>"><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fatura Tarihi</label>
            <input type="date" name="fatura_tarihi" value="<?= date('Y-m-d') ?>"
                   style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-primary">Fatura Ekle</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ================= FATURA LİSTESİ ================= -->
    <div class="card">
        <h2>Fatura Listesi</h2>
        <table class="data-table">
            <thead>
            <tr>
                <th>Fatura No</th>
                <th>Cari Adı</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
                <?php if ($canUpdate || $canDelete): ?><th style="width:160px;">İşlemler</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="6">Henüz fatura kaydı yok.</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?= e($inv['fatura_no']) ?></td>
                    <td><?= e($inv['cari_adi']) ?></td>
                    <td><?= number_format((float)$inv['tutar'], 2) ?> ₺</td>
                    <td>
                        <?php
                        $badgeClass = $inv['durum'] === 'Ödendi' ? 'badge-yes' : ($inv['durum'] === 'İptal' ? 'badge-no' : '');
                        ?>
                        <span class="badge <?= $badgeClass ?>" style="<?= $badgeClass === '' ? 'background:#fef3c7;color:#92400e;' : '' ?>">
                            <?= e($inv['durum']) ?>
                        </span>
                    </td>
                    <td><?= e($inv['fatura_tarihi']) ?></td>
                    <?php if ($canUpdate || $canDelete): ?>
                        <td>
                            <?php if ($canUpdate): ?>
                                <a href="index.php?page=fatura_listesi&edit=<?= (int)$inv['id'] ?>" class="btn btn-primary">Düzenle</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="index.php?page=fatura_listesi&delete=<?= (int)$inv['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu faturayı silmek istediğinize emin misiniz?');">Sil</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
