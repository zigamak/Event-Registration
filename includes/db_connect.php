<?php
// includes/db_connect.php

// Function to establish a PDO database connection
function getDbConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch rows as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log the error and terminate script execution
        error_log("Database connection failed: " . $e->getMessage());
        // In a real application, you might redirect to an error page or show a generic message
        die("Database connection failed. Please try again later.");
    }
}

// You will also need to create the 'registrations' table in your database.
/*
Example SQL for MySQL:

CREATE DATABASE IF NOT EXISTS event_registration;

USE event_registration;

CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE, -- Email should be unique per registration
    phone VARCHAR(50) NOT NULL,
    category ENUM('member', 'non-member', 'vip') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

*/
