<?php
// includes/google_sheets.php

// Load configuration
require_once __DIR__ . '/config.php';

// Ensure Composer autoloader is included for Google API Client
require_once __DIR__ . '/../vendor/autoload.php';

// Function to append data to a Google Sheet
function appendDataToGoogleSheet($spreadsheetId, $range, $values) {
    // Check if the Google API Client Library is available
    if (!class_exists('Google\Client')) {
        error_log("Google API Client Library not found. Install it via Composer: composer require google/apiclient");
        throw new Exception("Google API Client Library not found. Please install it via Composer.");
    }

    // Initialize Google Client
    $client = new Google\Client();
    $client->setApplicationName('Event Registration App');
    $client->setScopes(GOOGLE_SHEETS_SCOPES);

    // Authenticate using the service account key file
    try {
        if (!file_exists(GOOGLE_SERVICE_ACCOUNT_KEY_FILE)) {
            error_log("Service account key file not found at: " . GOOGLE_SERVICE_ACCOUNT_KEY_FILE);
            throw new Exception("Service account key file not found at: " . GOOGLE_SERVICE_ACCOUNT_KEY_FILE);
        }
        $client->setAuthConfig(GOOGLE_SERVICE_ACCOUNT_KEY_FILE);
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        throw new Exception("Failed to authenticate with Google Sheets: " . $e->getMessage());
    }

    // Initialize Google Sheets service
    try {
        $service = new Google\Service\Sheets($client);

        // Prepare the data to append
        $body = new Google\Service\Sheets\ValueRange([
            'values' => [$values] // Wrap values in an array as the API expects an array of arrays
        ]);

        $params = [
            'valueInputOption' => 'RAW' // RAW means values are entered as-is
        ];

        // Append data to the Google Sheet
        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        return $result; // Returns an object with updated range, updated rows, etc.
    } catch (Google\Service\Exception $e) {
        // Handle Google API-specific errors
        error_log("Google Sheets API Error: " . $e->getMessage());
        throw new Exception("Failed to append data to Google Sheet (Reference: " . uniqid() . "): " . $e->getMessage());
    } catch (Exception $e) {
        // Handle other general exceptions
        error_log("General Google Sheets Error: " . $e->getMessage());
        throw new Exception("An unexpected error occurred with Google Sheets (Reference: " . uniqid() . "): " . $e->getMessage());
    }
}