<?php
// includes/config.php

// Database Configuration (MySQL Example)
define('DB_HOST', 'localhost');
define('DB_NAME', 'evenesrh_emc');
define('DB_USER', 'evenesrh_emc');
define('DB_PASS', '}U._0NP})%YD');

// Google Sheets API Configuration
define('GOOGLE_SERVICE_ACCOUNT_KEY_FILE', __DIR__ . '/../credentials.json');
define('GOOGLE_SHEET_ID', '1KLKLCQGbGyMnJ07YRwFoMK2kWaCEzrA8HbORREcTwC8');
define('GOOGLE_SHEET_RANGE', 'Registrations!A:G');

// Define the scopes needed for Google Sheets API
define('GOOGLE_SHEETS_SCOPES', ['https://www.googleapis.com/auth/spreadsheets']);

// Paystack API Configuration
define('PAYSTACK_PUBLIC_KEY', 'pk_test_bf167a8a82a4c776b4c98ce6ca4eb901f4d0db37');
define('PAYSTACK_SECRET_KEY', 'sk_test_30f5228eb1491659e11c1871bf1a5ead994fe450');

// Paystack Subaccount for splitting and percentage
// This subaccount will receive a percentage of the transaction.
// The remaining percentage will go to your main Paystack account.
define('PAYSTACK_SUBACCOUNT_CODE', 'ACCT_u9knhyzn5eq4iop'); // **IMPORTANT: Replace with your actual subaccount code**
define('PAYSTACK_SUBACCOUNT_PERCENTAGE', 80); // Percentage to go to the subaccount (e.g., 20 for 20%)

// Error Reporting (for development, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);