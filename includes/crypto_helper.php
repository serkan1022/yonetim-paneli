<?php
/**
 * Ağ İzleme API'si için şifreleme yardımcıları.
 *
 * MANTIK: Her istemcinin şifreleme anahtarı, o istemcinin KENDİ lisans koduna
 * bağlıdır (SHA-256 ile 32 byte'lık bir AES anahtarına dönüştürülür). Böylece:
 * - Ekstra, ayrı bir "ortak parola" yönetmemize gerek kalmıyor
 * - Bir istemcinin anahtarı bir şekilde ele geçirilirse, sadece o istemcinin
 *   verisi risk altında olur, diğer istemciler etkilenmez
 *
 * Mesaj formatı (istemciden gelen ham gövde, base64 ile kodlanmış):
 *   [16 byte IV][AES-256-CBC ile şifrelenmiş JSON verisi]
 */

function lisansAnahtariniAlAesKey(string $lisansKodu): string
{
    // SHA-256 ile lisans kodunu 32 byte'lık (256 bit) ham bir anahtara çeviriyoruz
    return hash('sha256', $lisansKodu, true);
}

/**
 * İstemciden gelen base64 kodlu, şifreli veriyi çözer.
 * Başarısız olursa null döner.
 */
function sifreliVeriyiCoz(string $base64Data, string $lisansKodu): ?array
{
    $ham = base64_decode($base64Data, true);
    if ($ham === false || strlen($ham) < 17) {
        return null;
    }

    $iv         = substr($ham, 0, 16);
    $sifreliVeri = substr($ham, 16);

    $anahtar = lisansAnahtariniAlAesKey($lisansKodu);

    $cozulmus = openssl_decrypt($sifreliVeri, 'aes-256-cbc', $anahtar, OPENSSL_RAW_DATA, $iv);
    if ($cozulmus === false) {
        return null;
    }

    $json = json_decode($cozulmus, true);
    return is_array($json) ? $json : null;
}
