<?php
session_start();
require 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'index.php'));
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate input
        if (empty($_POST['username']) || empty($_POST['password'])) {
            throw new Exception("Username and password are required");
        }

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Prepared statement for security
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // Redirect to intended page or appropriate dashboard
            header("Location: " . ($_SESSION['redirect_url'] ?? ($user['role'] === 'admin' ? 'admin.php' : 'index.php')));
            unset($_SESSION['redirect_url']);
            exit();
        } else {
            throw new Exception("Invalid username or password");
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = $e->getMessage();
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Interior Home Design</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .error-message {
      color: #e53e3e;
      margin-bottom: 1rem;
      padding: 0.5rem;
      background-color: #fed7d7;
      border-radius: 0.25rem;
    }
  </style>
</head>
<body class="bg-gray-50 font-['Roboto'] flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Login to InteriorHome</h2>
    
    <?php if (isset($_SESSION['login_error'])): ?>
      <div class="error-message">
        <?= htmlspecialchars($_SESSION['login_error']); ?>
      </div>
      <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>

    <form action="login.php" method="POST" class="space-y-6">
      <div>
        <label for="username" class="block text-gray-700 mb-2">Username</label>
        <input type="text" id="username" name="username" required 
               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div>
        <label for="password" class="block text-gray-700 mb-2">Password</label>
        <input type="password" id="password" name="password" required 
               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition">
        Login
      </button>
    </form>
    <p class="mt-4 text-center text-gray-600">
      Don't have an account? <a href="signup.php" class="text-indigo-600 hover:underline">Sign Up</a>
    </p>
    <p class="mt-2 text-center text-gray-600">
      <a href="index.php" class="text-indigo-600 hover:underline">Back to Home</a>
    </p>
  </div>
</body>
</html>