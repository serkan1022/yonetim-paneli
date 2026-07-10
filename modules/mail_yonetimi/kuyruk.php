<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canUpdate, $canDelete
 */

// ---- Yeniden Dene ----
if (isset($_GET['retry']) && $canUpdate) {
    $id = (int)$_GET['retry'];
    $stmt = $pdo->prepare("UPDATE mail_queue SET durum='Bekliyor', hata_mesaji=NULL WHERE id=:id");
    $stmt->execute(['id' => $id]);
    header('Location: index.php?page=mail_kuyrugu');
    exit;
}

// ---- Silme ----
if (isset($_GET['delete']) && $canDelete) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM mail_queue WHERE id=:id");
    $stmt->execute(['id' => $id]);
    header('Location: index.php?page=mail_kuyrugu');
    exit;
}

// ---- Filtre ----
$filtre = $_GET['durum'] ?? '';
$sql = "SELECT * FROM mail_queue";
$params = [];
if (in_array($filtre, ['Bekliyor', 'Gonderildi', 'Hata', 'Gonderiliyor'], true)) {
    $sql .= " WHERE durum = :d";
    $params['d'] = $filtre;
}
$sql .= " ORDER BY id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$queue = $stmt->fetchAll();

// ---- Özet Sayılar ----
$counts = $pdo->query("SELECT durum, COUNT(*) AS adet FROM mail_queue GROUP BY durum")->fetchAll();
$countMap = ['Bekliyor' => 0, 'Gonderiliyor' => 0, 'Gonderildi' => 0, 'Hata' => 0];
foreach ($counts as $c) {
    $countMap[$c['durum']] = (int)$c['adet'];
}

function durumBadgeClass(string $durum): string
{
    return match ($durum) {
        'Gonderildi' => 'badge-yes',
        'Hata'       => 'badge-no',
        default      => '',
    };
}
?>

<div class="card">
    <h2>Mail Kuyruğu</h2>

    <div style="display:flex; gap:16px; margin-bottom:20px;">
        <div style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;">
            <div style="font-size:13px;color:#6b7280;">Bekliyor</div>
            <div style="font-size:22px;font-weight:700;"><?= $countMap['Bekliyor'] ?></div>
        </div>
        <div style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;">
            <div style="font-size:13px;color:#6b7280;">Gönderiliyor</div>
            <div style="font-size:22px;font-weight:700;"><?= $countMap['Gonderiliyor'] ?></div>
        </div>
        <div style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;">
            <div style="font-size:13px;color:#6b7280;">Gönderildi</div>
            <div style="font-size:22px;font-weight:700;color:#166534;"><?= $countMap['Gonderildi'] ?></div>
        </div>
        <div style="flex:1;background:<?= $countMap['Hata'] > 0 ? '#fef2f2' : '#f9fafb' ?>;border:1px solid <?= $countMap['Hata'] > 0 ? '#fecaca' : '#e5e7eb' ?>;border-radius:8px;padding:14px;">
            <div style="font-size:13px;color:#6b7280;">Hata</div>
            <div style="font-size:22px;font-weight:700;color:#991b1b;"><?= $countMap['Hata'] ?></div>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <a href="index.php?page=mail_kuyrugu" class="btn" style="<?= $filtre === '' ? 'background:var(--accent);color:#fff;' : 'background:#e5e7eb;' ?>">Tümü</a>
        <a href="index.php?page=mail_kuyrugu&durum=Bekliyor" class="btn" style="<?= $filtre === 'Bekliyor' ? 'background:var(--accent);color:#fff;' : 'background:#e5e7eb;' ?>">Bekliyor</a>
        <a href="index.php?page=mail_kuyrugu&durum=Gonderildi" class="btn" style="<?= $filtre === 'Gonderildi' ? 'background:var(--accent);color:#fff;' : 'background:#e5e7eb;' ?>">Gönderildi</a>
        <a href="index.php?page=mail_kuyrugu&durum=Hata" class="btn" style="<?= $filtre === 'Hata' ? 'background:var(--accent);color:#fff;' : 'background:#e5e7eb;' ?>">Hata</a>
    </div>

    <table class="data-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Alıcı</th>
            <th>Konu</th>
            <th>Kaynak</th>
            <th>Durum</th>
            <th>Deneme</th>
            <th>Oluşturma</th>
            <th>Gönderim</th>
            <th>Hata Mesajı</th>
            <th style="width:200px;">İşlemler</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($queue)): ?>
            <tr><td colspan="10">Kayıt bulunamadı.</td></tr>
        <?php endif; ?>
        <?php foreach ($queue as $q): ?>
            <tr>
                <td><?= (int)$q['id'] ?></td>
                <td><?= e($q['alici_eposta']) ?><?= $q['alici_adi'] ? ' (' . e($q['alici_adi']) . ')' : '' ?></td>
                <td><?= e($q['konu']) ?></td>
                <td><?= e($q['kaynak'] ?? '-') ?></td>
                <td>
                    <?php $badge = durumBadgeClass($q['durum']); ?>
                    <span class="badge <?= $badge ?>" style="<?= $badge === '' ? 'background:#fef3c7;color:#92400e;' : '' ?>">
                        <?= e($q['durum']) ?>
                    </span>
                </td>
                <td><?= (int)$q['deneme_sayisi'] ?></td>
                <td style="font-size:12px;"><?= e($q['olusturma_tarihi']) ?></td>
                <td style="font-size:12px;"><?= $q['gonderim_tarihi'] ? e($q['gonderim_tarihi']) : '-' ?></td>
                <td style="font-size:12px;color:#991b1b;max-width:220px;"><?= $q['hata_mesaji'] ? e($q['hata_mesaji']) : '-' ?></td>
                <td>
                    <button type="button" class="btn" style="background:#e5e7eb;padding:6px 10px;"
                            title="Mail içeriğini görüntüle"
                            onclick="panelMailPreview('<?= (int)$q['id'] ?>', '<?= e($q['konu']) ?>')"
                            data-mail-content-<?= (int)$q['id'] ?>="<?= base64_encode($q['icerik']) ?>">
                        👁
                    </button>
                    <?php if ($canUpdate && $q['durum'] === 'Hata'): ?>
                        <a href="index.php?page=mail_kuyrugu&retry=<?= (int)$q['id'] ?>" class="btn btn-primary">Yeniden Dene</a>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <a href="index.php?page=mail_kuyrugu&delete=<?= (int)$q['id'] ?>" class="btn btn-danger"
                           onclick="return confirm('Bu kaydı silmek istediğinize emin misiniz?');">Sil</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="font-size:12px;color:#9ca3af;margin-top:12px;">Son 200 kayıt gösteriliyor.</p>
</div>

<!-- ================= MAİL ÖNİZLEME MODAL ================= -->
<div id="panelMailModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; width:90%; max-width:640px; max-height:85vh; border-radius:10px; overflow:hidden; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #e5e7eb;">
            <strong id="panelMailModalTitle" style="font-size:15px;">Mail İçeriği</strong>
            <button type="button" onclick="panelMailPreviewClose()" class="btn btn-danger" style="padding:4px 12px;">Kapat</button>
        </div>
        <iframe id="panelMailModalFrame" style="flex:1; width:100%; border:none; min-height:400px;"></iframe>
    </div>
</div>

<script>
function panelMailPreview(id, konu) {
    var btn = document.querySelector('[data-mail-content-' + id + ']');
    if (!btn) { return; }
    var encoded = btn.getAttribute('data-mail-content-' + id);
    var html;
    try {
        html = decodeURIComponent(escape(atob(encoded)));
    } catch (e) {
        html = atob(encoded);
    }
    document.getElementById('panelMailModalTitle').textContent = konu;
    document.getElementById('panelMailModalFrame').srcdoc = html;
    document.getElementById('panelMailModalOverlay').style.display = 'flex';
}
function panelMailPreviewClose() {
    document.getElementById('panelMailModalOverlay').style.display = 'none';
    document.getElementById('panelMailModalFrame').srcdoc = '';
}
// Overlay'in kendisine (arka plana) tıklanınca da kapansın
document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('panelMailModalOverlay');
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            panelMailPreviewClose();
        }
    });
});
</script>
