<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public static function envoyer(string $destinataire, string $sujet, string $texte, string $html): bool
    {
        $config = (array) config('mail', []);
        if (($config['host'] ?? '') === '') {
            if (config('debug', false)) {
                error_log("Email non envoyé (SMTP non configuré) à $destinataire : $sujet\n$texte");
                return true;
            }
            error_log('Email impossible : SMTP non configuré.');
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) $config['host'];
            $mail->Port = (int) ($config['port'] ?? 587);
            $mail->SMTPAuth = ($config['user'] ?? '') !== '';
            $mail->Username = (string) ($config['user'] ?? '');
            $mail->Password = (string) ($config['pass'] ?? '');
            $mail->SMTPSecure = ($config['secure'] ?? 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Timeout = 10;

            $mail->setFrom((string) $config['from'], (string) ($config['from_name'] ?? app_name()));
            $mail->addAddress($destinataire);
            $mail->Subject = $sujet;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $texte;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email non envoyé : ' . $mail->ErrorInfo);
            return false;
        }
    }
}
