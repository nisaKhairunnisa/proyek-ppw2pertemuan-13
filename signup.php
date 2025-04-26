<?php
session_start();
require 'db.php';

// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process signup form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid form submission");
        }

        // Validate required fields
        $required = ['username', 'email', 'password', 'confirm_password'];
        $missing = array_filter($required, fn($field) => empty($_POST[$field]));
        
        if (!empty($missing)) {
            throw new Exception("All fields are required");
        }

        // Sanitize and validate inputs
        $username = trim($_POST['username']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate username (3-20 chars, alphanumeric + underscore)
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            throw new Exception("Username must be 3-20 characters (letters, numbers, underscores)");
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate password (min 8 chars, at least 1 number and 1 letter)
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must be at least 8 characters with letters and numbers");
        }

        // Check password match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insert new user with prepared statement
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash]);

        // Set success message and redirect
        $_SESSION['signup_success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['signup_error'] = $e->getMessage();
        $_SESSION['old_input'] = $_POST; // Save input for repopulation
        header("Location: signup.php");
        exit();
    }
}

// Generate CSRF token for new form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Interior Home Design</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
        }
        .password-weak { background-color: #ef4444; width: 25%; }
        .password-medium { background-color: #f59e0b; width: 50%; }
        .password-strong { background-color: #10b981; width: 75%; }
        .password-very-strong { background-color: #3b82f6; width: 100%; }
    </style>
</head>
<body class="bg-gray-50 font-['Roboto'] flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Create an Account</h2>
        
        <?php if (isset($_SESSION['signup_error'])): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md">
                <?= htmlspecialchars($_SESSION['signup_error']); ?>
                <?php unset($_SESSION['signup_error']); ?>
            </div>
        <?php endif; ?>

        <form id="signup-form" action="signup.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div>
                <label for="username" class="block text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       value="<?= htmlspecialchars($_SESSION['old_input']['username'] ?? '') ?>"
                       pattern="[a-zA-Z0-9_]{3,20}"
                       title="3-20 characters (letters, numbers, underscores)">
                <p class="text-xs text-gray-500 mt-1">3-20 characters (letters, numbers, underscores)</p>
            </div>
            
            <div>
                <label for="email" class="block text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       value="<?= htmlspecialchars($_SESSION['old_input']['email'] ?? '') ?>">
            </div>
            
            <div>
                <label for="password" class="block text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           minlength="8"
                           oninput="checkPasswordStrength(this.value)">
                    <div id="password-strength-bar" class="password-strength mt-1 rounded-full"></div>
                    <div id="password-strength-text" class="text-xs mt-1"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters with letters and numbers</p>
            </div>
            
            <div>
                <label for="confirm_password" class="block text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       minlength="8"
                       oninput="checkPasswordMatch()">
                <div id="password-match" class="text-xs mt-1 hidden">
                    <i class="fas fa-check text-green-500 mr-1"></i>
                    <span>Passwords match</span>
                </div>
                <div id="password-mismatch" class="text-xs mt-1 hidden">
                    <i class="fas fa-times text-red-500 mr-1"></i>
                    <span>Passwords don't match</span>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition">
                Sign Up
            </button>
        </form>
        
        <div class="mt-6 text-center text-gray-600">
            <p>Already have an account? <a href="login.php" class="text-indigo-600 hover:underline">Login</a></p>
            <p class="mt-2"><a href="index.php" class="text-indigo-600 hover:underline">Back to Home</a></p>
        </div>
    </div>

    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            // Reset
            strengthBar.className = 'password-strength mt-1 rounded-full';
            strengthText.textContent = '';
            
            if (password.length === 0) return;
            
            // Check strength
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update UI
            let strengthClass, strengthMessage;
            if (strength <= 2) {
                strengthClass = 'password-weak';
                strengthMessage = 'Weak';
            } else if (strength === 3) {
                strengthClass = 'password-medium';
                strengthMessage = 'Medium';
            } else if (strength === 4) {
                strengthClass = 'password-strong';
                strengthMessage = 'Strong';
            } else {
                strengthClass = 'password-very-strong';
                strengthMessage = 'Very Strong';
            }
            
            strengthBar.classList.add(strengthClass);
            strengthText.textContent = `Strength: ${strengthMessage}`;
            strengthText.className = `text-xs mt-1 ${strength === 1 ? 'text-red-500' : strength === 3 ? 'text-yellow-500' : 'text-green-500'}`;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const match = document.getElementById('password-match');
            const mismatch = document.getElementById('password-mismatch');
            
            if (confirmPassword.length === 0) {
                match.classList.add('hidden');
                mismatch.classList.add('hidden');
                return;
            }
            
            if (password === confirmPassword) {
                match.classList.remove('hidden');
                mismatch.classList.add('hidden');
            } else {
                match.classList.add('hidden');
                mismatch.classList.remove('hidden');
            }
        }
        
        // Clear old input when page loads
        window.onload = function() {
            <?php unset($_SESSION['old_input']); ?>
        };
    </script>
</body>
</html>