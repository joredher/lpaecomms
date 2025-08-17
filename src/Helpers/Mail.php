<?php

namespace Lpaecomms\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $htmlBody): bool
{
    $env = parse_ini_file(__DIR__ . '/../../.env');

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
    $html = file_get_contents(__DIR__ . '/../../pages/templates/emails/verify_email_template.html');
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
    $html = file_get_contents(__DIR__ . '/../../pages/templates/emails/verify_code_template.html');
    $html = str_replace(
        ['{{firstname}}', '{{username}}', '{{verification_code}}'],
        [htmlspecialchars($data['firstname']), $data['username'] ,$data['validationCode']],
        $html
    );

    $subject = "Your Verification Code, {$data['firstname']}!";
    return sendEmail($data['to'], $subject, $html);
}

function sendResetPasswordEmail (array $data): bool
{
    $html = file_get_contents(__DIR__ . '/../../pages/templates/emails/reset_password_template.html');

    $html = str_replace(
        ['{{firstname}}', '{{link_to_reset_password}}'],
        [htmlspecialchars($data['firstname']), $data['link_to_reset_password']],
        $html
    );

    $subject = "Reset Password, {$data['firstname']}!";
    return sendEmail($data['to'], $subject, $html);
}

function sendEmailToCustomerService (array $data): bool
{
    $env = parse_ini_file(__DIR__ . '/../../.env');
    $html = file_get_contents(__DIR__ . '/../../pages/templates/emails/customer_service_template.html');
    $random_number = "LPA_CONTACT".\Carbon\Carbon::now()->format('Ymd').random_int(1000,9999);
    $html = str_replace(
        ['{{name}}', '{{email}}', '{{phone_number}}', '{{message}}', '{{reference_code}}'],
        [htmlspecialchars($data['name']), $data['email'], $data['phone'], $data['message'], $random_number],
        $html
    );

    $name = ucfirst($data['name']);

    sleep(3);
    $subject = "New Message from Contact Form, {$name}! (REF: $random_number)";
    $isSentToContactService = sendEmail($env['MAIL_TO_CONTACT'], $subject, $html);

    if ($isSentToContactService) {
        $html = file_get_contents(__DIR__ . '/../../pages/templates/emails/customer_confirmation_template.html');
        $html = str_replace(
            ['{{name}}', '{{email}}', '{{phone_number}}', '{{message}}', '{{reference_code}}'],
            [htmlspecialchars($data['name']), $data['email'], $data['phone'], $data['message'], $random_number],
            $html
        );

        $subject = "We received your message, REF: $random_number";

        sleep(3);

        return sendEmail($data['email'], $subject, $html);
    }

    return false;
}

