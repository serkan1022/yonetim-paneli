<?php
/**
 * ============================================================
 *  SEND MAIL WORKER
 * ============================================================
 * Bu dosya web üzerinden DEĞİL, komut satırından çalıştırılır:
 *
 *   C:\php\php.exe C:\Apache24\htdocs\panel\workers\send_mail_worker.php
 *
 * Windows Görev Zamanlayıcı ile örn. her 2 dakikada bir çalıştırılması önerilir.
 * Kuyrukta "Bekliyor" durumundaki maileri alıp gerçekten gönderir.
 * ============================================================
 */

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/mail_helper.php';

$settings = getMailSettings($pdo);

if (!$settings) {
    echo '[' . date('Y-m-d H:i:s') . "] Mail ayarları bulunamadı, worker durduruldu. Panelden Mail Ayarları sayfasını doldurun.\n";
    exit(1);
}

$stmt = $pdo->prepare("SELECT * FROM mail_queue WHERE durum = 'Bekliyor' ORDER BY id ASC LIMIT 20");
$stmt->execute();
$items = $stmt->fetchAll();

echo '[' . date('Y-m-d H:i:s') . '] ' . count($items) . " adet bekleyen mail bulundu.\n";

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

echo '[' . date('Y-m-d H:i:s') . "] Worker tamamlandı.\n";
