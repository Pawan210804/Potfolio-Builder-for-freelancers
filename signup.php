<?php
// signup.php - This file handles both the display of the signup form and its processing logic.

// -------------------------------------------------------------------------
// PHP Error Reporting Configuration (for development only)
// These lines MUST be at the very top of your script to catch all errors.
// REMOVE or restrict these in a production environment for security.
// -------------------------------------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a session to store messages (like errors or success messages)
// and submitted form data across redirects.
session_start();

// -------------------------------------------------------------------------
// Database Connection Details for XAMPP (MySQLi)
// IMPORTANT: Please update 'your_database_name' below with your actual database name.
// The default XAMPP MySQL username is 'root' and the password is often empty ('').
// -------------------------------------------------------------------------
$host = 'localhost';          // Your database host (usually 'localhost' for XAMPP)
$db   = 'signup_db';         // <--- IMPORTANT: Change this to your actual database name in phpMyAdmin
$user = 'root';               // Your database username (default for XAMPP is 'root')
$pass = '';                   // Your database password (default for XAMPP is empty)

// Enable MySQLi error reporting for better debugging of database issues.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize variables for messages and form data.
// These will hold data from session or be set during processing.
$successMessage = '';
$errors = [];
$formData = [
    'name' => '',
    'email' => ''
];

// -------------------------------------------------------------------------
// Process Form Submission (This part runs ONLY when the form is submitted via POST)
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and Sanitize Form Data from $_POST superglobal.
    // htmlspecialchars() converts special characters to HTML entities to prevent XSS attacks.
    // trim() removes leading/trailing whitespace.
    $formData['name']  = htmlspecialchars(trim($_POST['name'] ?? ''));
    $formData['email'] = htmlspecialchars(trim($_POST['email'] ?? '')); // CORRECTED: Removed extra '$_'
    $password          = $_POST['password'] ?? ''; // Raw password for validation and hashing

    // ---------------------------------------------------------------------
    // Server-side Validation
    // This is critical for security and data integrity. All inputs must be validated.
    // ---------------------------------------------------------------------

    // Validate Name
    if (empty($formData['name'])) {
        $errors[] = "Your Name is required.";
    } elseif (strlen($formData['name']) < 2) {
        $errors[] = "Name must be at least 2 characters long.";
    } elseif (!preg_match("/^[a-zA-Z\s\-']{2,}$/", $formData['name'])) { // Allows letters, spaces, hyphens, apostrophes
        $errors[] = "Name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.";
    }

    // Validate Email
    if (empty($formData['email'])) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format. Please enter a valid email address.";
    }

    // Validate Password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long for security.";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must include at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must include at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must include at least one number.";
    } elseif (!preg_match("/[^a-zA-Z0-9\s]/", $password)) { // Checks for at least one special character
        $errors[] = "Password must include at least one special character (e.g., !, @, #, $).";
    }

    // If validation errors are found, store them in the session and set up for HTML display.
    if (!empty($errors)) {
        $_SESSION['errors']    = $errors;
        $_SESSION['form_data'] = $formData; // Keep form data to repopulate fields
        // The script will now fall through to the HTML section to display the form and errors.
    } else {
        // -----------------------------------------------------------------
        // If no validation errors, proceed with database operations.
        // -----------------------------------------------------------------
        try {
            // Establish Database Connection using MySQLi.
            $conn = new mysqli($host, $user, $pass, $db);

            // Check if the connection was successful. If not, throw an exception.
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }

            // -------------------------------------------------------------
            // Check if Email Already Exists in the 'student' table.
            // This prevents duplicate user registrations.
            // Using prepared statements for security.
            // -------------------------------------------------------------
            $check_email_sql = "SELECT id FROM student WHERE email = ?"; // <--- Table name 'student'
            $check_email_stmt = $conn->prepare($check_email_sql);
            if (!$check_email_stmt) {
                throw new Exception("Failed to prepare email check statement: " . $conn->error);
            }
            $check_email_stmt->bind_param("s", $formData['email']); // Bind email as a string
            $check_email_stmt->execute();
            $check_email_stmt->store_result(); // Store result to check number of rows

            if ($check_email_stmt->num_rows > 0) {
                // Email already exists, add an error.
                $errors[] = "This email is already registered. Please use a different email or log in.";
            }
            $check_email_stmt->close(); // Close the prepared statement

            // If no errors after email existence check, proceed with password hashing and user insertion.
            if (empty($errors)) {
                // ---------------------------------------------------------
                // Hash the Password Securely.
                // PASSWORD_DEFAULT uses the strongest available algorithm (e.g., bcrypt).
                // This is crucial for security; never store plain text passwords.
                // ---------------------------------------------------------
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // ---------------------------------------------------------
                // Insert New User Data into the 'student' table.
                // Using prepared statements is essential to prevent SQL Injection attacks.
                // Ensure column names (name, email, password) match your database table.
                // ---------------------------------------------------------
                $insert_sql = "INSERT INTO student (name, email, password) VALUES (?, ?, ?)"; // <--- Table name 'student'
                $insert_stmt = $conn->prepare($insert_sql);

                if (!$insert_stmt) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }

                // Bind parameters: "sss" indicates three string parameters for name, email, and hashed_password.
                $insert_stmt->bind_param("sss", $formData['name'], $formData['email'], $hashed_password);

                // Execute the insert statement
                if ($insert_stmt->execute()) {
                    // Registration successful!
                    $successMessage = "Account created successfully! You can now log in.";
                    // Redirect to login.php on successful registration.
                    // This 'header()' redirect must occur before any HTML output.
                    header("Location: login.php"); // Assuming you have a 'login.php' file for login.
                    exit(); // Essential to stop script execution after a header redirect.
                } else {
                    // If execution fails, throw an exception with the database error.
                    throw new Exception("Error during registration: " . $insert_stmt->error);
                }
                $insert_stmt->close(); // Close the prepared statement
            }

            $conn->close(); // Close the database connection.
        } catch (Exception $e) {
            // Catch any exceptions (e.g., database connection errors, query errors).
            $errors[] = "An unexpected error occurred: " . $e->getMessage();
        }
    }

    // After processing (whether successful or with errors, unless a redirect happened),
    // store final messages and form data in the session.
    // This allows the HTML section below to display them on the current page load.
    $_SESSION['errors'] = $errors;
    $_SESSION['success_message'] = $successMessage;
    $_SESSION['form_data'] = $formData;

    // The script will now fall through to the HTML part to render the page.

} // End of if ($_SERVER["REQUEST_METHOD"] == "POST") block

// -------------------------------------------------------------------------
// Retrieve and Clear Session Data for HTML Display
// These lines ensure that messages and form data (if any) are available for the
// HTML section below, and then cleared so they don't reappear on subsequent refreshes.
// -------------------------------------------------------------------------
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']); // Clear after retrieval

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']); // Clear after retrieval

$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [
    'name' => '',
    'email' => ''
];
unset($_SESSION['form_data']); // Clear after retrieval

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Account</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles to enhance Tailwind's defaults and define overall layout */
        body {
            font-family: "Inter", sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f3f4f6;
            padding: 1rem;
        }

        /* Styling for the message containers */
        .message-box {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .message-box.error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
        }
        .message-box.success {
            background-color: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        .message-box ul {
            list-style: disc;
            padding-left: 1.25rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Main container for the signup form card -->
    <div class="main-container flex justify-center items-center w-full min-h-screen bg-gray-100 p-4 sm:p-6 lg:p-8">
        <!-- Signup card -->
        <div class="signup-card bg-white p-6 sm:p-8 rounded-xl shadow-lg w-full max-w-sm sm:max-w-md lg:max-w-lg">
            <!-- Signup form header -->
            <h1 class="signup-title text-3xl font-bold text-center text-gray-800 mb-6">Create Your Account</h1>

            <!-- Success Message Display Area -->
            <?php if (!empty($successMessage)): ?>
                <div id="success-display" class="message-box success">
                    <p><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            <?php endif; ?>

            <!-- Error Message Display Area -->
            <?php if (!empty($errors)): ?>
                <div id="error-display" class="message-box error">
                    <p class="font-bold mb-2">Please fix the following issues:</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Signup Form: Action points to itself for processing -->
            <form id="signup-form" class="signup-form space-y-4" action="signup.php" method="POST">
                <!-- Name Input Field -->
                <div>
                    <label for="name" class="form-label block text-gray-700 text-sm font-semibold mb-2">Your Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="e.g., Jane Doe"
                        class="form-input shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                        value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                        required
                    />
                </div>

                <!-- Email Input Field -->
                <div>
                    <label for="email" class="form-label block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        class="form-input shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                        value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                        required
                    />
                </div>

                <!-- Password Input Field -->
                <div>
                    <label for="password" class="form-label block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        class="form-input shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                        required
                    />
                </div>

                <!-- Signup Button -->
                <div>
                    <button
                        type="submit"
                        class="signup-button w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-200 ease-in-out transform hover:scale-105"
                    >
                        Sign Up
                    </button>
                </div>
            </form>

            <!-- Optional: Link to the login page -->
            <p class="login-prompt text-center text-gray-600 text-sm mt-6">
                Already have an account?
                <a href="login.php" class="login-link text-blue-600 hover:text-blue-800 font-semibold transition duration-200 ease-in-out">Log in here</a>
            </p>
        </div>
    </div>
</body>
</html>
