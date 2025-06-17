<?php
// login.php - This file handles both the display of the login form and its processing logic.

// -------------------------------------------------------------------------
// PHP Error Reporting Configuration (for development only)
// These lines MUST be at the very top of your script to catch all errors.
// REMOVE or restrict these in a production environment for security.
// -------------------------------------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a session to manage user login state and messages across pages.
session_start();

// -------------------------------------------------------------------------
// Database Connection Details for XAMPP (MySQLi)
// IMPORTANT: These should match the credentials used in your signup.php.
// Update 'your_database_name' with your actual database name.
// -------------------------------------------------------------------------
$host = 'localhost';          // Your database host (usually 'localhost' for XAMPP)
$db   = 'signup_db';         // <--- IMPORTANT: Change this to your actual database name
$user = 'root';               // Your database username (default for XAMPP is 'root')
$pass = '';                   // Your database password (default for XAMPP is empty)

// Enable MySQLi error reporting for better debugging of database issues.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize variables for messages and form data.
$loginErrors = [];
$loginFormData = [
    'email' => ''
];
$successMessage = ''; // For potential messages from previous pages (e.g., signup success)

// Retrieve messages and form data from session on page load
// These are cleared after retrieval to prevent them from persisting on refresh.
if (isset($_SESSION['login_errors'])) {
    $loginErrors = $_SESSION['login_errors'];
    unset($_SESSION['login_errors']);
}
if (isset($_SESSION['login_form_data'])) {
    $loginFormData = $_SESSION['login_form_data'];
    unset($_SESSION['login_form_data']);
}
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}


// -------------------------------------------------------------------------
// Process Login Form Submission (This part runs ONLY when the form is submitted via POST)
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and Sanitize Form Data
    $loginFormData['email'] = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password               = $_POST['password'] ?? ''; // Raw password for verification

    // Server-side Validation
    if (empty($loginFormData['email'])) {
        $loginErrors[] = "Email address is required.";
    } elseif (!filter_var($loginFormData['email'], FILTER_VALIDATE_EMAIL)) {
        $loginErrors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $loginErrors[] = "Password is required.";
    }

    // If validation errors are found, store them in session and prepare for display.
    if (!empty($loginErrors)) {
        $_SESSION['login_errors']    = $loginErrors;
        $_SESSION['login_form_data'] = $loginFormData; // Repopulate email field
        // Script will fall through to HTML part to display errors.
    } else {
        // -----------------------------------------------------------------
        // Attempt User Authentication
        // -----------------------------------------------------------------
        try {
            // Establish Database Connection
            $conn = new mysqli($host, $user, $pass, $db);

            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }

            // Prepare statement to fetch user by email from the 'student' table
            $sql = "SELECT id, name, email, password FROM student WHERE email = ?"; // <--- Table name 'student'
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("s", $loginFormData['email']); // Bind email as a string
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc(); // Fetch user data

                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Login successful!
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    // Redirect to a dashboard or home page for logged-in users
                    header("Location: dashboard.php"); // You will need to create 'dashboard.php'
                    exit(); // Essential to stop script execution after redirect
                } else {
                    $loginErrors[] = "Invalid email or password."; // Generic error for security
                }
            } else {
                $loginErrors[] = "Invalid email or password."; // Generic error for security
            }

            $stmt->close(); // Close the statement
            $conn->close(); // Close the connection

        } catch (Exception $e) {
            // Catch any exceptions (e.g., database connection errors, query errors)
            $loginErrors[] = "An unexpected error occurred: " . $e->getMessage();
        }

        // If errors occurred during authentication, store them for display.
        if (!empty($loginErrors)) {
            $_SESSION['login_errors']    = $loginErrors;
            $_SESSION['login_form_data'] = $loginFormData; // Keep email for repopulation
            // Script will fall through to HTML part to display errors.
        }
    }
    // If a redirect happened (successful login), the script would have exited.
    // Otherwise, we continue to render the HTML with current error/form data.
}

// -------------------------------------------------------------------------
// HTML Section (Displays the Login Form and Messages)
// -------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to Your Account</title>
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
    <!-- Main container for the login form card -->
    <div class="main-container flex justify-center items-center w-full min-h-screen bg-gray-100 p-4 sm:p-6 lg:p-8">
        <!-- Login card -->
        <div class="login-card bg-white p-6 sm:p-8 rounded-xl shadow-lg w-full max-w-sm sm:max-w-md lg:max-w-lg">
            <!-- Login form header -->
            <h1 class="login-title text-3xl font-bold text-center text-gray-800 mb-6">Welcome Back!</h1>

            <!-- Success Message Display Area (e.g., "Account created successfully!") -->
            <?php if (!empty($successMessage)): ?>
                <div id="success-display" class="message-box success">
                    <p><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            <?php endif; ?>

            <!-- Error Message Display Area -->
            <?php if (!empty($loginErrors)): ?>
                <div id="error-display" class="message-box error">
                    <p class="font-bold mb-2">Please correct the following issues:</p>
                    <ul>
                        <?php foreach ($loginErrors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="login-form" class="login-form space-y-4" action="login.php" method="POST">
                <!-- Email Input Field -->
                <div>
                    <label for="email" class="form-label block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="your.email@example.com"
                        class="form-input shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                        value="<?php echo htmlspecialchars($loginFormData['email'] ?? ''); ?>"
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
                        placeholder="Enter your password"
                        class="form-input shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"
                        required
                    />
                </div>

                <!-- Login Button -->
                <div>
                    <button
                        type="submit"
                        class="login-button w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-200 ease-in-out transform hover:scale-105"
                    >
                        Log In
                    </button>
                </div>
            </form>

            <!-- Optional: Link to the signup page -->
            <p class="signup-prompt text-center text-gray-600 text-sm mt-6">
                Don't have an account?
                <a href="signup.php" class="signup-link text-blue-600 hover:text-blue-800 font-semibold transition duration-200 ease-in-out">Sign up here</a>
            </p>
        </div>
    </div>
</body>
</html>
