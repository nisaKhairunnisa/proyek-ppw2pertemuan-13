<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get user profile data
$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's designs count
$designs_stmt = $conn->prepare("SELECT COUNT(*) as design_count FROM designs WHERE user_id = ?");
$designs_stmt->execute([$_SESSION['user_id']]);
$designs_count = $designs_stmt->fetch()['design_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($user['username']) ?>'s Profile - Interior Home Design</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .profile-img {
      transition: transform 0.3s ease;
    }
    .profile-img:hover {
      transform: scale(1.05);
    }
  </style>
</head>
<body class="bg-gray-50 font-['Roboto']">

  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-gray-800">InteriorHome</a>
      <div class="flex items-center space-x-4">
        <span class="hidden md:inline text-gray-600">Welcome, <?= htmlspecialchars($user['username']) ?></span>
        <ul class="hidden md:flex space-x-6 text-gray-700">
          <li><a href="index.php" class="hover:text-indigo-600 transition">Home</a></li>
          <li><a href="developer.php" class="hover:text-indigo-600 transition">Developer</a></li>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <li><a href="admin.php" class="hover:text-indigo-600 transition">Admin</a></li>
          <?php endif; ?>
          <li><a href="profile.php" class="hover:text-indigo-600 transition">Profile</a></li>
          <li><a href="crud.php" class="hover:text-indigo-600 transition">My Designs</a></li>
          <li><a href="logout.php" class="hover:text-indigo-600 transition">Logout</a></li>
        </ul>
      </div>
      <div class="md:hidden">
        <button id="menu-btn" class="text-gray-700 focus:outline-none" aria-label="Toggle menu">
          <i class="fas fa-bars fa-lg"></i>
        </button>
      </div>
    </div>
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white px-6 pb-4">
      <ul class="space-y-4 text-gray-700">
        <li><a href="index.php" class="block hover:text-indigo-600 transition">Home</a></li>
        <li><a href="developer.php" class="block hover:text-indigo-600 transition">Developer</a></li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li><a href="admin.php" class="block hover:text-indigo-600 transition">Admin</a></li>
        <?php endif; ?>
        <li><a href="profile.php" class="block hover:text-indigo-600 transition">Profile</a></li>
        <li><a href="crud.php" class="block hover:text-indigo-600 transition">My Designs</a></li>
        <li><a href="logout.php" class="block hover:text-indigo-600 transition">Logout</a></li>
      </ul>
    </div>
  </nav>

  <main class="container mx-auto mt-12 px-6 max-w-4xl">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
      <a href="crud.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
        Manage Designs
      </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="p-6 md:p-8">
        <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
          <div class="flex-shrink-0">
            <img src="foto_anonim.jpg" alt="Profile Picture" 
                 class="profile-img w-32 h-32 md:w-40 md:h-40 rounded-full object-cover border-4 border-indigo-100">
          </div>
          <div class="flex-grow">
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user['username']) ?></h2>
              <p class="text-gray-600">Web Developer & Interior Design Enthusiast</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <h3 class="text-sm font-medium text-gray-500">Email</h3>
                <p class="mt-1 text-gray-800"><?= htmlspecialchars($user['email']) ?></p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Member Since</h3>
                <p class="mt-1 text-gray-800">
                  <?= date('F j, Y', strtotime($user['created_at'])) ?>
                </p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-500">Designs Created</h3>
                <p class="mt-1 text-gray-800"><?= $designs_count ?></p>
              </div>
            </div>

            <div class="flex space-x-4 text-gray-700 text-xl">
              <a href="https://github.com/" target="_blank" rel="noopener noreferrer" 
                 class="hover:text-indigo-600 transition" aria-label="GitHub">
                <i class="fab fa-github"></i>
              </a>
              <a href="https://linkedin.com/" target="_blank" rel="noopener noreferrer" 
                 class="hover:text-indigo-600 transition" aria-label="LinkedIn">
                <i class="fab fa-linkedin"></i>
              </a>
              <a href="mailto:<?= htmlspecialchars($user['email']) ?>" 
                 class="hover:text-indigo-600 transition" aria-label="Email">
                <i class="fas fa-envelope"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-800 mb-3">Recent Activity</h3>
        <div class="space-y-3">
          <div class="flex items-center space-x-3">
            <div class="flex-shrink-0 bg-indigo-100 p-2 rounded-full">
              <i class="fas fa-plus text-indigo-600"></i>
            </div>
            <p class="text-gray-600">
              Joined InteriorHome on <?= date('F j, Y', strtotime($user['created_at'])) ?>
            </p>
          </div>
          <?php if ($designs_count > 0): ?>
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0 bg-green-100 p-2 rounded-full">
                <i class="fas fa-palette text-green-600"></i>
              </div>
              <p class="text-gray-600">
                Created <?= $designs_count ?> design<?= $designs_count > 1 ? 's' : '' ?>
              </p>
            </div>
          <?php else: ?>
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0 bg-yellow-100 p-2 rounded-full">
                <i class="fas fa-lightbulb text-yellow-600"></i>
              </div>
              <p class="text-gray-600">
                You haven't created any designs yet. <a href="crud.php" class="text-indigo-600 hover:underline">Get started</a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <footer class="bg-gray-800 text-gray-300 mt-16 py-8">
    <div class="container mx-auto px-6 text-center">
      <p>&copy; <?= date('Y') ?> InteriorHome. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Mobile menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    menuBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
      menuBtn.setAttribute('aria-expanded', mobileMenu.classList.contains('hidden') ? 'false' : 'true');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
        mobileMenu.classList.add('hidden');
        menuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  </script>
</body>
</html>