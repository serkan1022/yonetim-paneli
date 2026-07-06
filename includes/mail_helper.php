<?php
/**
 * Ortak mail gönderme yardımcıları.
 * Hem panel içi sayfalar (ayarlar.php, kuyruk.php) hem de
 * workers/send_mail_worker.php (CLI) tarafından kullanılır.
 *
 * ÖNEMLİ: Çalışması için libs/PHPMailer/ klasörüne şu 3 dosyanın
 * indirilip konulmuş olması gerekir: Exception.php, PHPMailer.php, SMTP.php
 */

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Kayıtlı mail (SMTP) ayarlarını getirir. Sadece tek satır bekleniyor.
 */
function getMailSettings(PDO $pdo): ?array
{
    $row = $pdo->query("SELECT * FROM mail_settings ORDER BY id ASC LIMIT 1")->fetch();
    return $row ?: null;
}

/**
 * Tek bir maili gerçekten SMTP üzerinden gönderir.
 * Başarılıysa true döner. Başarısızsa false döner ve $errorMessage'a
 * hata sebebini yazar (kuyruk tablosuna kaydedilecek).
 */
function sendSingleMail(array $settings, string $toEmail, string $toName, string $subject, string $htmlBody, ?string &$errorMessage = null): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'];
        $mail->Password   = $settings['smtp_password'];
        $mail->Port       = (int)$settings['smtp_port'];

        if ($settings['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($settings['smtp_encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($settings['gonderen_eposta'], $settings['gonderen_adi']);
        $mail->addAddress($toEmail, $toName ?: '');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        $errorMessage = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        return false;
    }
}
