<?php
// process_registration.php - Handles form submission, saves to DB and Google Sheets, generates PDF invoice, sends email

// Start a session to store messages and form data
session_start();

// Include necessary files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/google_sheets.php';
require_once __DIR__ . '/email_template.php'; // Your email body generator
require_once __DIR__ . '/includes/send_email.php'; // New email sending function

// Require Composer's autoloader for Dompdf and Endroid/QrCode
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
// use Endroid\QrCode\Label\Label; // Not used currently, but kept for reference if labels are needed

// Ensure necessary constants are defined in config.php
// For PHPMailer:
// define('SMTP_HOST', 'your_smtp_host');
// define('SMTP_USERNAME', 'your_smtp_username');
// define('SMTP_PASSWORD', 'your_smtp_password');
// define('SMTP_PORT', 587); // Or 465 for SMTPS
// define('SMTP_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS); // Or PHPMailer::ENCRYPTION_SMTPS
// define('SMTP_FROM_EMAIL', 'your_email@example.com');
// define('SMTP_FROM_NAME', 'Your Company Name');

// For Invoice/PDF:
// define('COMPANY_ADDRESS', '123 Event Lane, City, Country');
// define('COMPANY_PHONE', '+1234567890');
// define('COMPANY_EMAIL', 'info@yourcompany.com'); // For invoice contact


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'category' => $category,
    ];

    // Basic server-side validation
    if (empty($name) || empty($email) || empty($phone) || empty($category)) {
        $_SESSION['error_message'] = "All fields are required. Please fill out the form completely.";
        header("Location: index.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format. Please enter a valid email address.";
        header("Location: index.php");
        exit();
    }

    $allowed_categories = ['member', 'non-member', 'vip'];
    if (!in_array($category, $allowed_categories)) {
        $_SESSION['error_message'] = "Invalid category selected.";
        header("Location: index.php");
        exit();
    }

    $amount = 0;
    switch ($category) {
        case 'member':
            $amount = 20000; // NGN
            break;
        case 'non-member':
            $amount = 30000; // NGN
            break;
        case 'vip':
            $amount = 70000; // NGN
            break;
    }

    $registration_id = null; // Initialize registration ID
    $reference_number = null; // Initialize reference number

    // --- 1. Save to Database ---
    try {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare("INSERT INTO registrations (name, email, phone, category, amount, registration_date) VALUES (:name, :email, :phone, :category, :amount, NOW())");

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':amount', $amount);

        $stmt->execute();
        $registration_id = $pdo->lastInsertId();

        // Generate a unique reference number (e.g., combination of ID and timestamp)
        // You might want a more robust unique ID system for payment references
        $reference_number = 'REG-' . $registration_id . '-' . date('YmdHis');

        // Update the database with the generated reference number
        $update_stmt = $pdo->prepare("UPDATE registrations SET reference_number = :reference_number WHERE id = :id");
        $update_stmt->bindParam(':reference_number', $reference_number);
        $update_stmt->bindParam(':id', $registration_id);
        $update_stmt->execute();

    } catch (PDOException $e) {
        error_log("Database error during registration: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred during registration. Please try again later.";
        header("Location: index.php");
        exit();
    }

    // --- 2. Save to Google Sheets (Real-time) ---
    try {
        $rowData = [
            date('Y-m-d H:i:s'), // Timestamp
            $registration_id,    // Database ID
            $reference_number,   // New: Reference Number
            $name,
            $email,
            $phone,
            ucfirst($category),
            $amount,
            'Pending Payment' // Assuming initial status
        ];
        appendDataToGoogleSheet(GOOGLE_SHEET_ID, GOOGLE_SHEET_RANGE, $rowData);
    } catch (Exception $e) {
        error_log("Google Sheets API error: " . $e->getMessage());
        $_SESSION['error_message'] = "Registration successful, but there was an issue syncing data to Google Sheets. Please contact support if you don't receive a confirmation.";
        // We continue, as DB registration succeeded
    }

    // --- 3. Generate QR Code for Invoice ---
    $qrCodeData = $reference_number; // Data to embed in QR code
    $writer = new PngWriter();
    $qrCode = QrCode::create($qrCodeData)
        ->setSize(200)
        ->setMargin(10);

    // Render QR code to a string (base64 encoded)
    $qrCodeResult = $writer->write($qrCode);
    $qrCodeBase64 = $qrCodeResult->getDataUri(); // Get as Data URI (base64)

    // --- 4. Generate Invoice PDF ---
    $invoiceHtmlPath = __DIR__ . '/templates/invoice_template.html';
    $invoiceContent = file_get_contents($invoiceHtmlPath);

    // Replace placeholders in invoice template
    $invoice_date = date('Y-m-d');
    $invoice_due_date = date('Y-m-d', strtotime('+7 days')); // Example: due in 7 days

    $invoiceContent = str_replace('{{name}}', htmlspecialchars($name), $invoiceContent);
    $invoiceContent = str_replace('{{email}}', htmlspecialchars($email), $invoiceContent);
    $invoiceContent = str_replace('{{phone}}', htmlspecialchars($phone), $invoiceContent);
    $invoiceContent = str_replace('{{category}}', htmlspecialchars(ucfirst($category)), $invoiceContent);
    $invoiceContent = str_replace('{{amount}}', number_format($amount, 2), $invoiceContent);
    $invoiceContent = str_replace('{{reference}}', htmlspecialchars($reference_number), $invoiceContent);
    $invoiceContent = str_replace('{{invoice_date}}', htmlspecialchars($invoice_date), $invoiceContent);
    $invoiceContent = str_replace('{{invoice_due_date}}', htmlspecialchars($invoice_due_date), $invoiceContent);
    $invoiceContent = str_replace('{{payment_status}}', 'Awaiting Payment', $invoiceContent); // Initial status
    $invoiceContent = str_replace('{{payment_status_color}}', '#d9534f', $invoiceContent); // Red for awaiting
    $invoiceContent = str_replace('{{qr_code_image}}', $qrCodeBase64, $invoiceContent); // Embed QR code

    // Company details for invoice - ensure these are defined in config.php
    $invoiceContent = str_replace('{{from_name}}', htmlspecialchars(defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Your Company'), $invoiceContent);
    $invoiceContent = str_replace('{{from_address}}', htmlspecialchars(defined('COMPANY_ADDRESS') ? COMPANY_ADDRESS : 'N/A'), $invoiceContent);
    $invoiceContent = str_replace('{{from_phone}}', htmlspecialchars(defined('COMPANY_PHONE') ? COMPANY_PHONE : 'N/A'), $invoiceContent);
    $invoiceContent = str_replace('{{from_email}}', htmlspecialchars(defined('COMPANY_EMAIL') ? COMPANY_EMAIL : (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'N/A')), $invoiceContent);


    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Enable loading of remote images (like your logo)
    $options->set('defaultFont', 'sans-serif');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($invoiceContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF to a temporary file
    $pdfFileName = 'invoice_' . $reference_number . '.pdf';
    $pdfFilePath = sys_get_temp_dir() . '/' . $pdfFileName;
    file_put_contents($pdfFilePath, $dompdf->output());

    // --- 5. Send Confirmation Email with Invoice Attachment ---
    $subject = "Your Event Registration Confirmation and Invoice - " . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Event Registration');
    $body = generateEmailBody($name, $email, $phone, $category, $amount, $reference_number, date('Y-m-d H:i:s')); // Use actual registration date

    if (sendEmailWithAttachment($email, $name, $subject, $body, $pdfFilePath, $pdfFileName)) {
        $_SESSION['success_message'] = "Thank you, " . htmlspecialchars($name) . "! Your registration has been successfully processed. A confirmation email with your invoice has been sent to " . htmlspecialchars($email) . ".";
    } else {
        $_SESSION['error_message'] = "Registration successful, but there was an issue sending the confirmation email with the invoice. Please contact support.";
    }

    // Clean up temporary PDF file
    if (file_exists($pdfFilePath)) {
        unlink($pdfFilePath);
    }

    header("Location: index.php");
    exit();

} else {
    // If accessed directly without POST request
    header("Location: index.php");
    exit();
}
