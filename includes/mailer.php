<?php
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

define('SMTP_HOST', getenv('SALON_SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SALON_SMTP_PORT') ?: 587));
define('SMTP_USERNAME', getenv('SALON_SMTP_USERNAME') ?: 'vageesh02h@gmail.com');
define('SMTP_PASSWORD', getenv('SALON_SMTP_PASSWORD') ?: 'iavqblxmuynlrqhd');
define('SMTP_FROM_NAME', getenv('SALON_SMTP_FROM_NAME') ?: 'Unisex Salon');

function sendSalonMail($toEmail, $toName, $subject, $htmlBody, $plainBody = '') {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));

        return $mail->send();
    } catch (MailException $exception) {
        error_log('Salon mail failed: ' . $exception->getMessage());
        return false;
    }
}

function sendRegistrationOtp($toEmail, $toName, $otp) {
    $safeName = htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $subject = 'Your Unisex Salon verification OTP';
    $html = "
        <div style=\"font-family:Arial,sans-serif;line-height:1.5;color:#222\">
            <p>Hello {$safeName},</p>
            <p>Your OTP for Unisex Salon account registration is:</p>
            <p style=\"font-size:28px;font-weight:bold;letter-spacing:4px;margin:16px 0\">{$safeOtp}</p>
            <p>This OTP is valid for 10 minutes. If you did not request it, you can ignore this email.</p>
        </div>
    ";
    $plain = "Hello {$toName},\n\nYour OTP for Unisex Salon account registration is: {$otp}\n\nThis OTP is valid for 10 minutes.";

    return sendSalonMail($toEmail, $toName, $subject, $html, $plain);
}

function sendPasswordResetOtp($toEmail, $toName, $otp) {
    $safeName = htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $subject = 'Your Unisex Salon password reset OTP';
    $html = "
        <div style=\"font-family:Arial,sans-serif;line-height:1.5;color:#222\">
            <p>Hello {$safeName},</p>
            <p>Your OTP to reset your Unisex Salon password is:</p>
            <p style=\"font-size:28px;font-weight:bold;letter-spacing:4px;margin:16px 0\">{$safeOtp}</p>
            <p>This OTP is valid for 10 minutes. If you did not request it, please ignore this email.</p>
        </div>
    ";
    $plain = "Hello {$toName},\n\nYour OTP to reset your Unisex Salon password is: {$otp}\n\nThis OTP is valid for 10 minutes.";

    return sendSalonMail($toEmail, $toName, $subject, $html, $plain);
}

?>
