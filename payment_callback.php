<?php

// payment_callback.php - Handles Paystack payment verification, registration processing, and split payment verification

// Start a session to store messages
session_start();

// Include necessary files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/google_sheets.php';
require_once __DIR__ . '/includes/send_email.php';
require_once __DIR__ . '/includes/email_template.php';

// Check if a transaction reference is provided in the URL
if (!isset($_GET['reference'])) {
    $_SESSION['error_message'] = "Payment reference not found. Please try again.";
    header("Location: confirmation.php");
    exit();
}

$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// --- 1. Verify Payment with Paystack ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Cache-Control: no-cache",
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($http_code !== 200 || !isset($result['status']) || $result['status'] !== true || !isset($result['data']['status']) || $result['data']['status'] !== 'success') {
    $error_detail = $result['message'] ?? 'Unknown error during verification.';
    error_log("Paystack verification failed for reference {$reference}: " . $error_detail);
    $_SESSION['error_message'] = "Payment verification failed: " . htmlspecialchars($error_detail) . ". Please contact support with reference: " . htmlspecialchars($reference);
    header("Location: confirmation.php");
    exit();
}

// Payment successfully verified!
$transaction_data = $result['data'];
$amount_paid_kobo = $transaction_data['amount'];
$customer_email = $transaction_data['customer']['email'];
$metadata = $transaction_data['metadata']['custom_fields'] ?? [];

// Extract custom fields from metadata
$name = '';
$phone = '';
$category = '';

foreach ($metadata as $field) {
    if ($field['variable_name'] === 'full_name') {
        $name = $field['value'];
    } elseif ($field['variable_name'] === 'phone_number') {
        $phone = $field['value'];
    } elseif ($field['variable_name'] === 'category') {
        $category = $field['value'];
    }
}

// Convert amount from kobo to Naira for database storage
$amount_naira = $amount_paid_kobo / 100;
$payment_status = 'success';
$registration_date = date('Y-m-d H:i:s'); // Store registration date for email

// Basic validation of extracted data
if (empty($name) || empty($customer_email) || empty($phone) || empty($category)) {
    error_log("Missing metadata after Paystack verification for reference {$reference}.");
    $_SESSION['error_message'] = "Payment verified, but registration details are incomplete. Please contact support with reference: " . htmlspecialchars($reference);
    header("Location: confirmation.php");
    exit();
}

// --- 2. Verify and Store Split Payment Details ---
$split_data = $transaction_data['split'] ?? [];
$split_details = [];

if (!empty($split_data) && isset($split_data['subaccounts'])) {
    error_log("Split payment details received for reference {$reference}: " . json_encode($split_data));
    foreach ($split_data['subaccounts'] as $split) {
        $split_details[] = [
            'subaccount_id' => $split['subaccount'] ?? '',
            'amount' => ($split['share'] / 100), // Convert kobo to Naira
            'transaction_charge' => ($split['transaction_charge'] ?? 0) / 100
        ];
    }
    error_log("Processed split payment details for reference {$reference}: " . json_encode($split_details));
} else {
    error_log("No split payment details found for reference {$reference}. Full amount went to main account.");
}

// --- 3. Save to Database ---
try {
    $pdo = getDbConnection();

    // Check for duplicate registration
    $stmt_check = $pdo->prepare("SELECT id FROM registrations WHERE email = :email AND category = :category AND amount = :amount AND paystack_reference = :paystack_reference AND payment_status = 'success'");
    $stmt_check->bindParam(':email', $customer_email);
    $stmt_check->bindParam(':category', $category);
    $stmt_check->bindParam(':amount', $amount_naira);
    $stmt_check->bindParam(':paystack_reference', $reference);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $_SESSION['success_message'] = "You are already registered for this event. Payment confirmed.";
        header("Location: confirmation.php");
        exit();
    }

    // Insert registration record
    $stmt = $pdo->prepare("INSERT INTO registrations (name, email, phone, category, amount, registration_date, paystack_reference, payment_status) VALUES (:name, :email, :phone, :category, :amount, :registration_date, :paystack_reference, :payment_status)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $customer_email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':amount', $amount_naira);
    $stmt->bindParam(':registration_date', $registration_date);
    $stmt->bindParam(':paystack_reference', $reference);
    $stmt->bindParam(':payment_status', $payment_status);
    $stmt->execute();

    $registration_id = $pdo->lastInsertId();

    // Insert split payment details (if any)
    if (!empty($split_details)) {
        $stmt_split = $pdo->prepare("INSERT INTO split_payments (registration_id, subaccount_id, amount, transaction_charge, paystack_reference) VALUES (:registration_id, :subaccount_id, :amount, :transaction_charge, :paystack_reference)");
        foreach ($split_details as $split) {
            $stmt_split->bindParam(':registration_id', $registration_id);
            $stmt_split->bindParam(':subaccount_id', $split['subaccount_id']);
            $stmt_split->bindParam(':amount', $split['amount']);
            $stmt_split->bindParam(':transaction_charge', $split['transaction_charge']);
            $stmt_split->bindParam(':paystack_reference', $reference);
            $stmt_split->execute();
        }
    }

} catch (PDOException $e) {
    error_log("Database error during registration for reference {$reference}: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred after payment. Please contact support with reference: " . htmlspecialchars($reference);
    header("Location: confirmation.php");
    exit();
}

// --- 4. Save to Google Sheets ---
try {
    $rowData = [
        $registration_date, // Timestamp
        $registration_id,   // Database ID
        $name,
        $customer_email,
        $phone,
        ucfirst($category),
        $amount_naira,
        $reference, // Paystack Reference
        ucfirst($payment_status), // Payment Status
        !empty($split_details) ? json_encode($split_details) : 'No splits' // Split details
    ];

    appendDataToGoogleSheet(GOOGLE_SHEET_ID, GOOGLE_SHEET_RANGE, $rowData);

} catch (Exception $e) {
    error_log("Google Sheets API error for reference {$reference}: " . $e->getMessage());
    $_SESSION['error_message'] = "Registration successful, but there was an issue syncing data to Google Sheets. Please contact support with reference: " . htmlspecialchars($reference);
    header("Location: confirmation.php");
    exit();
}

// --- 5. Send Confirmation Email ---
$email_subject = "Event Registration Confirmation";
$email_body = generateEmailBody($name, $customer_email, $phone, $category, $amount_naira, $reference, $registration_date);

$email_sent = sendEmail($customer_email, $name, $email_subject, $email_body);

if (!$email_sent) {
    error_log("Failed to send confirmation email to {$customer_email} for reference {$reference}.");
    $_SESSION['error_message'] = "Registration successful, but there was an issue sending the confirmation email. Please check your email or contact support with reference: " . htmlspecialchars($reference);
    header("Location: confirmation.php");
    exit();
}

// --- 6. Success Response ---
$_SESSION['success_message'] = "Thank you, " . htmlspecialchars($name) . "! Your registration for the event as a " . htmlspecialchars($category) . " has been successfully processed. A confirmation email has been sent to " . htmlspecialchars($customer_email) . ". Your payment reference is: " . htmlspecialchars($reference);
header("Location: confirmation.php");
exit();
?>