<?php
/**
 * ============================================================
 *  AĞ İZLEME API - network_report.php
 * ============================================================
 * C# istemci uygulaması bu adrese düzenli aralıklarla (3 dakikada bir)
 * POST isteği atar. Veri, Wireshark gibi araçlarla dinlemeye karşı
 * AES-256-CBC ile şifrelenmiş olarak gönderilir (anahtar: istemcinin
 * kendi lisans kodundan türetilir, bkz. includes/crypto_helper.php).
 *
 * İstek örneği:
 *   POST http://SUNUCU_ADRESI/panel/api/network_report.php
 *   Header: X-License-Key: <lisans_kodu>   (açık metin - sadece kimlik tespiti için)
 *   Content-Type: text/plain
 *   Body: base64( [16 byte IV] + [AES-256-CBC şifreli JSON] )
 *
 * Şifresi çözülen JSON içeriği:
 *   {
 *     "bilgisayar_adi": "MUHASEBE-PC01",
 *     "kullanici_adi": "ahmet.yilmaz",
 *     "mac_adresi": "00:1A:2B:3C:4D:5E",
 *     "ip_adresi": "192.168.1.45",
 *     "subnet_mask": "255.255.255.0",
 *     "default_gateway": "192.168.1.1"
 *   }
 *
 * Dönüş: her zaman JSON, { "success": true/false, "message": "..." }
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/crypto_helper.php';

function jsonYanit(bool $success, string $message, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Sadece POST kabul edilir ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonYanit(false, 'Bu uç nokta sadece POST isteklerini kabul eder.', 405);
}

// ---- Lisans kodunu bul (header üzerinden - bu her zaman açık metin gelir, sadece kimlik tespiti için) ----
$lisansKodu = $_SERVER['HTTP_X_LICENSE_KEY'] ?? '';
$lisansKodu = trim((string)$lisansKodu);

if ($lisansKodu === '') {
    jsonYanit(false, 'Lisans kodu eksik.', 401);
}

// ---- Lisansı doğrula ----
$stmt = $pdo->prepare("SELECT * FROM api_lisanslar WHERE lisans_kodu = :k LIMIT 1");
$stmt->execute(['k' => $lisansKodu]);
$lisans = $stmt->fetch();

if (!$lisans) {
    jsonYanit(false, 'Geçersiz lisans kodu.', 401);
}

if (!$lisans['aktif']) {
    jsonYanit(false, 'Bu lisans pasif durumda.', 403);
}

// ---- İstek gövdesini oku ve şifresini çöz ----
// Gövde artık düz JSON değil, base64 kodlu şifreli veri (bkz. includes/crypto_helper.php)
$rawBody = trim(file_get_contents('php://input'));

$data = sifreliVeriyiCoz($rawBody, $lisansKodu);

if ($data === null) {
    // Geriye dönük uyumluluk: eski (şifresiz) istemcilerden düz JSON gelirse onu da dene
    $fallback = json_decode($rawBody, true);
    $data = is_array($fallback) ? $fallback : null;
}

if ($data === null) {
    jsonYanit(false, 'Veri çözülemedi veya bozuk.', 400);
}

// ---- Gelen verileri al ve temizle ----
$bilgisayarAdi = trim((string)($data['bilgisayar_adi'] ?? ''));
$kullaniciAdi  = trim((string)($data['kullanici_adi'] ?? ''));
$macAdresi     = trim((string)($data['mac_adresi'] ?? ''));
$ipAdresi      = trim((string)($data['ip_adresi'] ?? ''));
$subnetMask    = trim((string)($data['subnet_mask'] ?? ''));
$gateway       = trim((string)($data['default_gateway'] ?? ''));

$lisansId = (int)$lisans['id'];

// ---- Güncel durumu upsert et (istemci_bilgileri: her lisans için tek satır) ----
$stmt = $pdo->prepare(
    "INSERT INTO istemci_bilgileri
        (lisans_id, bilgisayar_adi, kullanici_adi, mac_adresi, ip_adresi, subnet_mask, default_gateway, son_guncelleme)
     VALUES (:lid, :bad, :kad, :mac, :ip, :sm, :gw, NOW())
     ON DUPLICATE KEY UPDATE
        bilgisayar_adi = VALUES(bilgisayar_adi),
        kullanici_adi = VALUES(kullanici_adi),
        mac_adresi = VALUES(mac_adresi),
        ip_adresi = VALUES(ip_adresi),
        subnet_mask = VALUES(subnet_mask),
        default_gateway = VALUES(default_gateway),
        son_guncelleme = NOW()"
);
$stmt->execute([
    'lid' => $lisansId, 'bad' => $bilgisayarAdi, 'kad' => $kullaniciAdi,
    'mac' => $macAdresi, 'ip' => $ipAdresi, 'sm' => $subnetMask, 'gw' => $gateway,
]);

// ---- Geçmiş tabloya da bir satır ekle (denetim/izleme için) ----
$stmt = $pdo->prepare(
    "INSERT INTO istemci_bilgileri_log
        (lisans_id, bilgisayar_adi, kullanici_adi, mac_adresi, ip_adresi, subnet_mask, default_gateway)
     VALUES (:lid, :bad, :kad, :mac, :ip, :sm, :gw)"
);
$stmt->execute([
    'lid' => $lisansId, 'bad' => $bilgisayarAdi, 'kad' => $kullaniciAdi,
    'mac' => $macAdresi, 'ip' => $ipAdresi, 'sm' => $subnetMask, 'gw' => $gateway,
]);

// ---- Lisansın son kullanım zamanını güncelle ----
$pdo->prepare("UPDATE api_lisanslar SET son_kullanim = NOW() WHERE id = :id")->execute(['id' => $lisansId]);

jsonYanit(true, 'Kayıt başarıyla güncellendi.');
