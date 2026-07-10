<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canDelete
 */

// ---- Silme (istemci kaydını ve geçmişini temizler) ----
if (isset($_GET['delete']) && $canDelete) {
    $lisansId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM istemci_bilgileri WHERE lisans_id = :id")->execute(['id' => $lisansId]);
    $pdo->prepare("DELETE FROM istemci_bilgileri_log WHERE lisans_id = :id")->execute(['id' => $lisansId]);
    header('Location: index.php?page=istemci_listesi');
    exit;
}

// ---- Geçmiş görüntüleme (belirli bir lisansın son 50 kaydı) ----
$historyLisansId = isset($_GET['history']) ? (int)$_GET['history'] : 0;
$historyRows = [];
$historyEtiket = '';
if ($historyLisansId) {
    $stmt = $pdo->prepare("SELECT etiket FROM api_lisanslar WHERE id = :id");
    $stmt->execute(['id' => $historyLisansId]);
    $historyEtiket = $stmt->fetchColumn() ?: ('Lisans #' . $historyLisansId);

    $stmt = $pdo->prepare(
        "SELECT * FROM istemci_bilgileri_log WHERE lisans_id = :id ORDER BY id DESC LIMIT 50"
    );
    $stmt->execute(['id' => $historyLisansId]);
    $historyRows = $stmt->fetchAll();
}

// ---- Güncel istemci listesi ----
$istemciler = $pdo->query(
    "SELECT i.*, l.etiket, l.lisans_kodu, l.aktif AS lisans_aktif
     FROM istemci_bilgileri i
     INNER JOIN api_lisanslar l ON l.id = i.lisans_id
     ORDER BY i.son_guncelleme DESC"
)->fetchAll();
?>

<?php if ($historyLisansId): ?>
    <!-- ================= GEÇMİŞ GÖRÜNÜMÜ ================= -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2 style="margin:0;">Geçmiş: <?= e($historyEtiket) ?></h2>
            <a href="index.php?page=istemci_listesi" class="btn" style="background:#e5e7eb;">← Listeye Dön</a>
        </div>
        <table class="data-table">
            <thead>
            <tr>
                <th>Tarih</th>
                <th>Bilgisayar</th>
                <th>Kullanıcı</th>
                <th>IP Adresi</th>
                <th>MAC Adresi</th>
                <th>Subnet Mask</th>
                <th>Gateway</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($historyRows)): ?>
                <tr><td colspan="7">Kayıt bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($historyRows as $h): ?>
                <tr>
                    <td style="font-size:12px;"><?= e($h['kayit_tarihi']) ?></td>
                    <td><?= e($h['bilgisayar_adi'] ?? '-') ?></td>
                    <td><?= e($h['kullanici_adi'] ?? '-') ?></td>
                    <td><?= e($h['ip_adresi'] ?? '-') ?></td>
                    <td style="font-family:monospace; font-size:12px;"><?= e($h['mac_adresi'] ?? '-') ?></td>
                    <td><?= e($h['subnet_mask'] ?? '-') ?></td>
                    <td><?= e($h['default_gateway'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="font-size:12px;color:#9ca3af;margin-top:12px;">Son 50 kayıt gösteriliyor.</p>
    </div>
<?php else: ?>
    <!-- ================= GÜNCEL DURUM LİSTESİ ================= -->
    <div class="card">
        <h2>İstemci Listesi (Güncel Durum)</h2>
        <p style="color:#6b7280; font-size:14px;">
            Her satır, o lisans koduna ait en son alınan ağ bilgisini gösterir. İstemci uygulaması her 3 dakikada
            bir bu bilgileri günceller.
        </p>
        <table class="data-table">
            <thead>
            <tr>
                <th>Etiket</th>
                <th>Bilgisayar Adı</th>
                <th>Kullanıcı</th>
                <th>IP Adresi</th>
                <th>MAC Adresi</th>
                <th>Subnet Mask</th>
                <th>Gateway</th>
                <th>Son Güncelleme</th>
                <th style="width:200px;">İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($istemciler)): ?>
                <tr><td colspan="9">Henüz hiçbir istemciden veri alınmadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($istemciler as $i): ?>
                <?php
                // 10 dakikadan uzun süredir güncelleme yoksa "çevrimdışı" say
                $cevrimici = $i['son_guncelleme'] && (time() - strtotime($i['son_guncelleme'])) < 600;
                ?>
                <tr>
                    <td>
                        <?= e($i['etiket'] ?? ('Lisans #' . $i['lisans_id'])) ?>
                        <?php if (!$i['lisans_aktif']): ?><span class="badge badge-no" style="margin-left:6px;">Lisans Pasif</span><?php endif; ?>
                    </td>
                    <td><?= e($i['bilgisayar_adi'] ?? '-') ?></td>
                    <td><?= e($i['kullanici_adi'] ?? '-') ?></td>
                    <td><?= e($i['ip_adresi'] ?? '-') ?></td>
                    <td style="font-family:monospace; font-size:12px;"><?= e($i['mac_adresi'] ?? '-') ?></td>
                    <td><?= e($i['subnet_mask'] ?? '-') ?></td>
                    <td><?= e($i['default_gateway'] ?? '-') ?></td>
                    <td style="font-size:12px;">
                        <?= $i['son_guncelleme'] ? e($i['son_guncelleme']) : '-' ?>
                        <?php if ($cevrimici): ?>
                            <span class="badge badge-yes" style="margin-left:4px;">Çevrimiçi</span>
                        <?php else: ?>
                            <span class="badge badge-no" style="margin-left:4px;">Çevrimdışı</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="index.php?page=istemci_listesi&history=<?= (int)$i['lisans_id'] ?>" class="btn btn-primary">Geçmiş</a>
                        <?php if ($canDelete): ?>
                            <a href="index.php?page=istemci_listesi&delete=<?= (int)$i['lisans_id'] ?>" class="btn btn-danger"
                               onclick="return confirm('Bu istemcinin tüm kayıtları silinecek. Emin misiniz?');">Sil</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
