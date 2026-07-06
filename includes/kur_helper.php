<?php
/**
 * Kur botu ortak fonksiyonları.
 * Hem panel içi "Şimdi Gönder" butonu hem de workers/kur_botu_worker.php
 * (CLI, günde 3 kez zamanlanmış) tarafından kullanılır.
 */

/**
 * Güncel USD, EUR, GBP -> TRY kurlarını ücretsiz bir API'den çeker.
 * Başarısız olursa null döner.
 */
function fetchCurrentRates(): ?array
{
    $url = 'https://open.er-api.com/v6/latest/TRY';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PanelKurBotu/1.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['rates']['USD'], $data['rates']['EUR'], $data['rates']['GBP'])) {
        return null;
    }

    // API "1 TRY = X USD" formatında dönüyor, biz "1 USD = X TRY" istiyoruz -> ters çeviriyoruz
    return [
        'USD'   => $data['rates']['USD'] > 0 ? round(1 / $data['rates']['USD'], 4) : null,
        'EUR'   => $data['rates']['EUR'] > 0 ? round(1 / $data['rates']['EUR'], 4) : null,
        'GBP'   => $data['rates']['GBP'] > 0 ? round(1 / $data['rates']['GBP'], 4) : null,
        'tarih' => $data['time_last_update_utc'] ?? date('Y-m-d H:i:s'),
    ];
}

/**
 * Kur verilerinden şık bir HTML e-posta şablonu oluşturur.
 */
function buildKurEmailHtml(array $rates): string
{
    $simdi = date('d.m.Y H:i');
    $satirlar = [
        ['ABD Doları (USD)', $rates['USD']],
        ['Euro (EUR)', $rates['EUR']],
        ['İngiliz Sterlini (GBP)', $rates['GBP']],
    ];

    $satirHtml = '';
    foreach ($satirlar as $s) {
        $deger = $s[1] !== null ? number_format($s[1], 4, ',', '.') . ' ₺' : 'Veri alınamadı';
        $satirHtml .= '<tr>
            <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;font-size:15px;color:#111827;">' . htmlspecialchars($s[0]) . '</td>
            <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;font-size:15px;font-weight:600;color:#2563eb;text-align:right;">' . $deger . '</td>
        </tr>';
    }

    return '
    <div style="font-family:Segoe UI, Arial, sans-serif; max-width:480px; margin:0 auto; background:#f3f4f6; padding:24px;">
        <div style="background:#1f2937; padding:20px 24px; border-radius:8px 8px 0 0;">
            <h1 style="color:#ffffff; font-size:18px; margin:0;">Güncel Döviz Kurları</h1>
            <p style="color:#9ca3af; font-size:13px; margin:4px 0 0 0;">' . htmlspecialchars($simdi) . ' itibarıyla</p>
        </div>
        <div style="background:#ffffff; border-radius:0 0 8px 8px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                ' . $satirHtml . '
            </table>
        </div>
        <p style="font-size:11px; color:#9ca3af; text-align:center; margin-top:16px;">
            Bu e-posta otomatik olarak Yönetim Paneli Kur Botu tarafından gönderilmiştir.
        </p>
    </div>';
}

/**
 * Güncel kurları çekip, aktif tüm kur botu alıcıları için mail kuyruğuna ekler.
 * Dönüş: eklenen kayıt sayısı (int), veri çekilemezse -1.
 */
function enqueueKurBotuMails(PDO $pdo): int
{
    $rates = fetchCurrentRates();
    if ($rates === null) {
        return -1;
    }

    $html    = buildKurEmailHtml($rates);
    $subject = 'Güncel Döviz Kurları - ' . date('d.m.Y H:i');

    $recipients = $pdo->query("SELECT eposta, ad_soyad FROM kur_botu_aliciler WHERE aktif = 1")->fetchAll();

    $stmt = $pdo->prepare(
        "INSERT INTO mail_queue (alici_eposta, alici_adi, konu, icerik, kaynak) VALUES (:e, :a, :k, :i, 'kur_botu')"
    );

    $eklenen = 0;
    foreach ($recipients as $r) {
        $stmt->execute([
            'e' => $r['eposta'],
            'a' => $r['ad_soyad'],
            'k' => $subject,
            'i' => $html,
        ]);
        $eklenen++;
    }

    return $eklenen;
}
