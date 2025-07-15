Event Registration System with Paystack Integration
Overview
This project is a PHP-based web application for event registration, integrated with Paystack for payment processing. It allows users to register for an event by selecting a category (Member, Non-Member, or VIP), filling out their details, and making a payment via Paystack. The system supports split payments, where a percentage of the transaction amount is sent to a subaccount, with the remainder going to the main Paystack account. Registration details are stored in a MySQL database and synced to a Google Sheet for record-keeping.
Features

User Registration: Collects user details (name, email, phone, category) via a responsive form.
Paystack Integration: Processes payments using Paystack's inline checkout with split payment support.
Split Payments: Configurable percentage-based split to a subaccount (e.g., 80% to subaccount, 20% to main account).
Database Storage: Stores registration and payment details in a MySQL database.
Google Sheets Sync: Appends registration data to a specified Google Sheet.
Session Management: Displays success/error messages and retains form data on submission errors.
Responsive UI: Built with Tailwind CSS and custom styles for a modern, user-friendly interface.

Prerequisites

PHP: Version 7.4 or higher.
MySQL: For storing registration and payment data.
Composer: For installing PHP dependencies (e.g., Google Sheets API client).
Paystack Account: Test or live account with a subaccount configured for split payments.
Google Cloud Project: For Google Sheets API access, with a service account and credentials.
Web Server: Apache/Nginx with PHP support.
Dependencies:
google/apiclient for Google Sheets integration.
cURL extension for PHP to communicate with Paystack API.



Setup Instructions

Clone the Repository:
git clone <repository-url>
cd event-registration-system


Install PHP Dependencies:Install the Google API client library using Composer:
composer require google/apiclient:^2.0


Configure the Database:

Create a MySQL database named event_registration.
Execute the following SQL to create the required tables:CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    registration_date DATETIME NOT NULL,
    paystack_reference VARCHAR(100) NOT NULL,
    payment_status VARCHAR(20) NOT NULL
);

CREATE TABLE split_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    subaccount_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_charge DECIMAL(10, 2) NOT NULL,
    paystack_reference VARCHAR(100) NOT NULL,
    FOREIGN KEY (registration_id) REFERENCES registrations(id)
);




Configure Environment:

Copy includes/config.php.example to includes/config.php (if applicable) and update the following:// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'event_registration');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Google Sheets API Configuration
define('GOOGLE_SERVICE_ACCOUNT_KEY_FILE', __DIR__ . '/../credentials.json');
define('GOOGLE_SHEET_ID', 'your_google_sheet_id');
define('GOOGLE_SHEET_RANGE', 'Registrations!A:G');

// Paystack API Configuration
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_public_key');
define('PAYSTACK_SECRET_KEY', 'sk_test_your_secret_key');
define('PAYSTACK_SUBACCOUNT_CODE', 'ACCT_your_subaccount_code');
define('PAYSTACK_SUBACCOUNT_PERCENTAGE', 80); // 80% to subaccount


Replace your_db_user and your_db_password with your MySQL credentials.
Obtain Paystack test keys and subaccount code from the Paystack dashboard (Test Mode).
For Google Sheets, create a service account in the Google Cloud Console, download the credentials.json file, and place it in the project root.


Set Up Google Sheets:

Create a Google Sheet and note its ID (from the URL: https://docs.google.com/spreadsheets/d/<SHEET_ID>/edit).
Share the sheet with the service account email (found in credentials.json).
Ensure the sheet has a tab named Registrations with columns: Timestamp, ID, Name, Email, Phone, Category, Amount, Paystack Reference, Payment Status, Split Details.


Set Up File Structure:Ensure the following files are in place:

index.php: Main registration form with Paystack integration.
payment_callback.php: Handles payment verification and data storage.
includes/config.php: Configuration file.
includes/db_connect.php: Database connection logic (not provided, must implement PDO connection).
includes/google_sheets.php: Google Sheets API logic (not provided, must implement using Google API client).
css/style.css: Custom CSS (create if needed, or leave empty for Tailwind-only styling).
credentials.json: Google service account credentials.


Set Up Web Server:

Configure your web server (e.g., Apache) to serve the project.
Ensure the document root points to the project directory.
Verify that .htaccess allows PHP execution if needed.



Usage

Access the Registration Form:

Navigate to http://your-domain.com/index.php.
Fill out the form (name, email, phone, category) and click "Pay & Register".
Complete the payment using Paystack's checkout modal (use test cards in Test Mode).


Payment Processing:

Payments are processed via Paystack, with the specified percentage (e.g., 80%) going to the subaccount defined in config.php.
Upon successful payment, the user is redirected to confirmation.php (not provided, must implement) with a success message.
If payment fails or is canceled, an error message is displayed.


Data Storage:

Registration details are saved to the registrations table in MySQL.
Split payment details (if applicable) are saved to the split_payments table.
Data is appended to the specified Google Sheet.



Testing

Test Payment with Paystack:

Use Paystack test cards (e.g., 4084084084084081, CVV: 408, Expiry: Any future date, PIN: 1234).
Verify split payments in the Paystack dashboard (Test Mode) under Transactions and Subaccounts.
Check server logs for messages like:Split payment details received for reference {...}: {...}
Processed split payment details for reference {...}: {...}




Verify Database:

Check the registrations and split_payments tables for new records.
Ensure the paystack_reference matches the transaction reference in the Paystack dashboard.


Verify Google Sheets:

Confirm that new rows are added to the Registrations tab with all required fields, including split payment details.



Troubleshooting

Split Payments Not Working:

Verify the subaccount code (PAYSTACK_SUBACCOUNT_CODE) in the Paystack dashboard (Test Mode).
Check server logs for messages indicating whether split details were received.
Use the Paystack API to list subaccounts:curl -X GET "https://api.paystack.co/subaccount" \
-H "Authorization: Bearer sk_test_your_secret_key" \
-H "Cache-Control: no-cache"


Ensure the subaccount is active and linked to a valid payout method.
Contact Paystack support with the transaction reference if issues persist.


Database Errors:

Verify MySQL credentials in config.php.
Ensure the registrations and split_payments tables exist.
Check server logs for PDO exceptions.


Google Sheets Sync Issues:

Verify the credentials.json file and Google Sheet ID.
Ensure the service account has edit access to the sheet.
Check server logs for Google API errors.


Payment Verification Failures:

Confirm that PAYSTACK_SECRET_KEY is correct.
Check


