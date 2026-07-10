<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/mail_helper.php';

const TOPLAM_CALISMA_SURESI = 120; // saniye - worker'ın toplam açık kalacağı süre
const KONTROL_ARALIGI       = 10;  // saniye - kuyruğun ne sıklıkla kontrol edileceği

$baslangicZamani = time();

echo '[' . date('Y-m-d H:i:s') . '] Worker başlatıldı. '
    . TOPLAM_CALISMA_SURESI . " saniye boyunca her " . KONTROL_ARALIGI . " saniyede bir kuyruk kontrol edilecek.\n";

while ((time() - $baslangicZamani) < TOPLAM_CALISMA_SURESI) {
    islemYap($pdo);

    $gecenSure = time() - $baslangicZamani;
    $kalanSure = TOPLAM_CALISMA_SURESI - $gecenSure;

    // Bir sonraki kontrole kadar yeterli süre kaldıysa bekle, yoksa döngüyü bitir
    if ($kalanSure > KONTROL_ARALIGI) {
        sleep(KONTROL_ARALIGI);
    } else {
        break;
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Worker süresi doldu, kapatılıyor.\n";


/**
 * Kuyrukta bekleyen mail olup olmadığını kontrol eder, varsa hepsini gönderir.
 */
function islemYap(PDO $pdo): void
{
    $settings = getMailSettings($pdo);

    if (!$settings) {
        echo '[' . date('Y-m-d H:i:s') . "] Mail ayarları bulunamadı, bu kontrol atlandı.\n";
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM mail_queue WHERE durum = 'Bekliyor' ORDER BY id ASC LIMIT 20");
    $stmt->execute();
    $items = $stmt->fetchAll();

    if (empty($items)) {
        // Sessizce geç - her 10 saniyede "0 mail bulundu" yazmasın diye log kirletmiyoruz
        return;
    }

    echo '[' . date('Y-m-d H:i:s') . '] ' . count($items) . " adet bekleyen mail bulundu, gönderiliyor.\n";

    foreach ($items as $item) {
        $pdo->prepare("UPDATE mail_queue SET durum = 'Gonderiliyor' WHERE id = :id")
            ->execute(['id' => $item['id']]);

        $errorMessage = null;
        $success = sendSingleMail(
            $settings,
            $item['alici_eposta'],
            $item['alici_adi'] ?? '',
            $item['konu'],
            $item['icerik'],
            $errorMessage
        );

        if ($success) {
            $pdo->prepare("UPDATE mail_queue SET durum = 'Gonderildi', gonderim_tarihi = NOW() WHERE id = :id")
                ->execute(['id' => $item['id']]);
            echo "  [OK]   #{$item['id']} -> {$item['alici_eposta']}\n";
        } else {
            $pdo->prepare("UPDATE mail_queue SET durum = 'Hata', hata_mesaji = :e, deneme_sayisi = deneme_sayisi + 1 WHERE id = :id")
                ->execute(['e' => $errorMessage, 'id' => $item['id']]);
            echo "  [HATA] #{$item['id']} -> {$item['alici_eposta']}: {$errorMessage}\n";
        }
    }
}
