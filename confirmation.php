<?php
// confirmation.php - Displays payment confirmation or error messages

session_start();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .card-shadow {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-xl card-shadow w-full max-w-md text-center">
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-6 rounded relative mb-6">
                <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="text-2xl font-bold text-green-800 mb-2">Payment Successful!</h2>
                <p class="text-lg"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php elseif ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-6 rounded relative mb-6">
                <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="text-2xl font-bold text-red-800 mb-2">Payment Failed!</h2>
                <p class="text-lg"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php else: ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-6 rounded relative mb-6">
                <h2 class="text-2xl font-bold text-blue-800 mb-2">Confirmation Status</h2>
                <p class="text-lg">No specific message available. Please check your registration status or try again.</p>
            </div>
        <?php endif; ?>

        <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-150 ease-in-out text-lg">
            Go Back to Registration
        </a>
    </div>
</body>
</html>
