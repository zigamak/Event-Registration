<?php
// index.php - Event Registration Form with Paystack Integration

// Start a session to store messages (e.g., success or error messages)
session_start();

// Initialize variables for form fields to retain values on submission error
$name = $_SESSION['form_data']['name'] ?? '';
$email = $_SESSION['form_data']['email'] ?? '';
$phone = $_SESSION['form_data']['phone'] ?? '';
$category = $_SESSION['form_data']['category'] ?? '';

// Get and clear messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
unset($_SESSION['form_data']); // Clear form data after displaying errors

// Include config to get Paystack Public Key
require_once __DIR__ . '/includes/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS (for any overrides or specific styles) -->
    <link rel="stylesheet" href="./css/style.css">
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5; /* Light gray background */
        }
        /* Custom styles for focus states and shadows */
        .form-input-focus:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5); /* Blue focus ring */
            border-color: #4299e1; /* Blue border */
        }
        .card-shadow {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            color: white;
            font-size: 1.5rem;
            flex-direction: column;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-text {
            color: #dc2626; /* Red for error messages */
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-xl card-shadow w-full max-w-md">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Event Registration</h2>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form id="registrationForm" class="space-y-6">
            <!-- Name Field -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 form-input-focus"
                       placeholder="John Doe" required>
            </div>

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 form-input-focus"
                       placeholder="you@example.com" required>
                <p id="emailError" class="error-text hidden">Please enter a valid email address.</p>
            </div>

            <!-- Phone Number Field -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 form-input-focus"
                       placeholder="+234 801 234 5678" required>
            </div>

            <!-- Category Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Category</label>
                <div class="mt-1 space-y-3">
                    <div class="flex items-center">
                        <input id="category_member" name="category" type="radio" value="member" data-amount="2000000"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded-full"
                               <?php echo ($category === 'member') ? 'checked' : ''; ?> required>
                        <label for="category_member" class="ml-3 block text-sm font-medium text-gray-700">
                            Member (<span class="font-semibold">20,000 NGN</span>)
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="category_non_member" name="category" type="radio" value="non-member" data-amount="3000000"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded-full"
                               <?php echo ($category === 'non-member') ? 'checked' : ''; ?> required>
                        <label for="category_non_member" class="ml-3 block text-sm font-medium text-gray-700">
                            Non-Member (<span class="font-semibold">30,000 NGN / $20 USD</span>)
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="category_vip" name="category" type="radio" value="vip" data-amount="7000000"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded-full"
                               <?php echo ($category === 'vip') ? 'checked' : ''; ?> required>
                        <label for="category_vip" class="ml-3 block text-sm font-medium text-gray-700">
                            VIP (<span class="font-semibold">70,000 NGN</span>)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" id="payButton"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Pay & Register
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="spinner"></div>
        <p>Processing your payment...</p>
    </div>

    <!-- Paystack Standard Checkout Script -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        const registrationForm = document.getElementById('registrationForm');
        const payButton = document.getElementById('payButton');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const emailError = document.getElementById('emailError');

        registrationForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            // Get form values
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const selectedCategoryRadio = document.querySelector('input[name="category"]:checked');

            // Client-side validation
            if (!name || !email || !phone || !selectedCategoryRadio) {
                alert('Please fill in all required fields.');
                return;
            }

            if (!validateEmail(email)) {
                emailError.classList.remove('hidden');
                return;
            } else {
                emailError.classList.add('hidden');
            }

            const category = selectedCategoryRadio.value;
            const amount = parseInt(selectedCategoryRadio.dataset.amount);

            // Show loading overlay
            loadingOverlay.classList.remove('hidden');
            payButton.disabled = true;

            // Initialize Paystack payment with split configuration
            let handler = PaystackPop.setup({
                key: '<?php echo PAYSTACK_PUBLIC_KEY; ?>',
                email: email,
                amount: amount,
                currency: 'NGN',
                ref: '' + Math.floor((Math.random() * 1000000000) + 1),
                metadata: {
                    custom_fields: [
                        {
                            display_name: "Full Name",
                            variable_name: "full_name",
                            value: name
                        },
                        {
                            display_name: "Phone Number",
                            variable_name: "phone_number",
                            value: phone
                        },
                        {
                            display_name: "Category",
                            variable_name: "category",
                            value: category
                        }
                    ]
                },
                split: {
                    type: 'percentage',
                    bearer: 'subaccount', // Subaccount bears transaction fees
                    subaccounts: [
                        {
                            subaccount: '<?php echo PAYSTACK_SUBACCOUNT_CODE; ?>',
                            share: <?php echo PAYSTACK_SUBACCOUNT_PERCENTAGE; ?>
                        }
                    ]
                },
                callback: function(response) {
                    window.location.href = 'payment_callback.php?reference=' + response.reference;
                },
                onClose: function() {
                    loadingOverlay.classList.add('hidden');
                    payButton.disabled = false;
                    alert('Payment window closed. Please try again.');
                },
            });

            handler.openIframe();
        });

        // Email validation function
        function validateEmail(email) {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    </script>
</body>
</html>