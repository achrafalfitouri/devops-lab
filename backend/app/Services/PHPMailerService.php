<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerService
{
    public function sendEmail($to, $subject, $body, $attachments = [])
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');

            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment['tmp_name'])) {
                        $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
                    }
                }
            }

            $mail->send();

            return ['status' => 'success'];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'mailer_error' => $mail->ErrorInfo,
            ];
        }
    }
}
