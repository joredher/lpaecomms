<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $htmlBody): bool
{
    $env = parse_ini_file(__DIR__ . '/../.env');

    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = $env['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['MAIL_USERNAME'];
        $mail->Password = $env['MAIL_PASSWORD'];
        $mail->Port = $env['MAIL_PORT'];

        // Sender and Recipient
        $mail->setFrom($env['MAIL_FROM_EMAIL'], $env['MAIL_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("❌ PHPMailer error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendVerificationEmail(array $data): bool {
    $html = file_get_contents(__DIR__ . '/../pages/templates/emails/verify_email_template.html');
    $html = str_replace(
        ['{{firstname}}', '{{verification_link}}'],
        [htmlspecialchars($data['firstname']), $data['verificationUrl']],
        $html
    );

    $subject = "Verify Your Email Address, {$data['firstname']}!";
    return sendEmail($data['to'], $subject, $html);

}

function sendValidationCodeEmail (array $data): bool
{
    $html = file_get_contents(__DIR__ . '/../pages/templates/emails/verify_code_template.html');
    $html = str_replace(
        ['{{firstname}}', '{{username}}', '{{verification_code}}'],
        [htmlspecialchars($data['firstname']), $data['username'] ,$data['validationCode']],
        $html
    );

    $subject = "Your Verification Code, {$data['firstname']}!";
    return sendEmail($data['to'], $subject, $html);
}
