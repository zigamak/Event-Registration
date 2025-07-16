<?php

// email_template.php - Generates the email body for confirmation emails

function generateEmailBody($name, $email, $phone, $category, $amount, $reference, $registration_date) {
    // Load the HTML email template
    ob_start();
    include __DIR__ . '/templates/confirmation_email.html';
    $template = ob_get_clean();

    // Replace placeholders with actual values
    $template = str_replace('{{name}}', htmlspecialchars($name), $template);
    $template = str_replace('{{email}}', htmlspecialchars($email), $template);
    $template = str_replace('{{phone}}', htmlspecialchars($phone), $template);
    $template = str_replace('{{category}}', htmlspecialchars(ucfirst($category)), $template);
    $template = str_replace('{{amount}}', number_format($amount, 2), $template);
    $template = str_replace('{{reference}}', htmlspecialchars($reference), $template);
    $template = str_replace('{{registration_date}}', htmlspecialchars($registration_date), $template);
    $template = str_replace('{{from_name}}', htmlspecialchars(SMTP_FROM_NAME), $template);

    return $template;
}
?>