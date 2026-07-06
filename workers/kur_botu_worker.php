<?php
/**
 * ============================================================
 *  KUR BOTU WORKER
 * ============================================================
 * Bu dosya web üzerinden DEĞİL, komut satırından çalıştırılır:
 *
 *   C:\php\php.exe C:\Apache24\htdocs\panel\workers\kur_botu_worker.php
 *
 * Windows Görev Zamanlayıcı ile günde 3 kez (09:00, 12:00, 13:00) çalıştırılması
 * gerekir (bunun için 3 ayrı görev zamanlayıcı kaydı oluşturulmalı, her biri aynı
 * komutu farklı saatte tetikler).
 *
 * Güncel USD/EUR/GBP kurlarını çekip, aktif tüm "kur botu alıcıları" için
 * Mail Kuyruğu'na (mail_queue) ekler. Gerçek gönderim send_mail_worker.php
 * tarafından yapılır.
 * ============================================================
 */

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/kur_helper.php';

$result = enqueueKurBotuMails($pdo);

if ($result === -1) {
    echo '[' . date('Y-m-d H:i:s') . "] Kur verileri alınamadı (internet bağlantısı veya API erişimi sorunlu olabilir).\n";
    exit(1);
}

echo '[' . date('Y-m-d H:i:s') . "] {$result} alıcı için kur maili kuyruğa eklendi.\n";
