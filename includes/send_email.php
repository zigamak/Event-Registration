<?php
// includes/send_email.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// IMPORTANT: This line is crucial if you're using Composer for PHPMailer.
// It assumes your 'vendor' directory is one level up from 'includes'.
// If your project structure is different, adjust this path accordingly.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends an email using PHPMailer with optional attachments.
 *
 * This function relies on SMTP configuration defined as constants (e.g., SMTP_HOST)
 * which should be set up in your config.php file.
 *
 * @param string $toEmail The recipient's email address.
 * @param string $toName The recipient's name.
 * @param string $subject The subject of the email.
 * @param string $body The HTML body of the email.
 * @param string|null $attachmentPath Optional. The full path to the file to attach.
 * @param string|null $attachmentName Optional. The name of the attachment as it appears in the email.
 * @return bool True if the email was sent successfully, false otherwise.
 */
function sendEmail($toEmail, $toName, $subject, $body, $attachmentPath = null, $attachmentName = null) {
    // You were trying to use `global $SMTP_HOST, ...` for constants.
    // Constants are globally accessible by default in PHP, no need for `global`.
    // Ensure these are defined in your 'config.php' file as 'define("SMTP_HOST", "...");'
    // Example: define("SMTP_HOST", "smtp.gmail.com");

    $mail = new PHPMailer(true); // Passing true enables exceptions for better error handling

    try {
        // Server settings (ensure these constants are defined in your config.php)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        // Using PHPMailer::ENCRYPTION_SMTPS for SSL (port 465) or PHPMailer::ENCRYPTION_STARTTLS for TLS (port 587)
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8'; // Recommended for handling various characters

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName); // Add a recipient

        // Content
        $mail->isHTML(true);                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Plain text version for non-HTML email clients

        // Attachments
        if ($attachmentPath && file_exists($attachmentPath)) {
            // If attachmentName is not provided, PHPMailer will use the base name of the file
            $mail->addAttachment($attachmentPath, $attachmentName);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log("Failed to send email to {$toEmail}. Mailer Error: {$mail->ErrorInfo}. Exception: {$e->getMessage()}");
        return false;
    }
}