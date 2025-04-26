<?php
session_start();
require 'db.php';

// Jika user sudah login, dapatkan data user
$logged_in = false;
$username = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $username = $user['username'];
    $logged_in = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Developer Profile - InteriorHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .developer-card {
      transition: all 0.3s ease;
    }
    .developer-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .skill-badge {
      transition: all 0.2s ease;
    }
    .skill-badge:hover {
      transform: scale(1.05);
    }
  </style>
</head>
<body class="bg-gray-50 font-['Roboto']">
  <!-- Navbar -->
  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-gray-800">InteriorHome</a>
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
      <div class="md:hidden">
        <button id="menu-btn" class="text-gray-700 focus:outline-none">
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

  <!-- Developer Profile Section -->
  <main class="container mx-auto px-6 py-12">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-4xl font-bold text-center text-gray-800 mb-12">Web Developer Profile</h1>
      
      <div class="bg-white rounded-xl shadow-lg overflow-hidden developer-card">
        <div class="md:flex">
          <div class="md:w-1/3 bg-indigo-50 p-8 flex flex-col items-center">
            <img src="DSC07082.jpg" alt="Developer Avatar" class="w-48 h-48 rounded-full object-cover border-4 border-white shadow-md mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Khairunnisa' Almaududy</h2>
            <p class="text-indigo-600 mb-4">Full Stack Developer</p>
            
            <div class="flex space-x-4 mb-6">
              <a href="https://github.com/" target="_blank" class="text-gray-700 hover:text-indigo-600 text-xl">
                <i class="fab fa-github"></i>
              </a>
              <a href="https://linkedin.com/" target="_blank" class="text-gray-700 hover:text-indigo-600 text-xl">
                <i class="fab fa-linkedin"></i>
              </a>
              <a href="mailto:khairunnisa@example.com" class="text-gray-700 hover:text-indigo-600 text-xl">
                <i class="fas fa-envelope"></i>
              </a>
            </div>
            
            <a href="contact.php" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
              Contact Me
            </a>
          </div>
          
          <div class="md:w-2/3 p-8">
            <div class="mb-8">
              <h3 class="text-xl font-semibold text-gray-800 mb-4">About Me</h3>
              <p class="text-gray-600 leading-relaxed">
                Passionate full-stack developer with expertise in creating beautiful and functional web applications. 
                Specialized in PHP, JavaScript, and modern web frameworks. Dedicated to building user-friendly 
                interfaces and robust backend systems.
              </p>
            </div>
            
            <div class="mb-8">
              <h3 class="text-xl font-semibold text-gray-800 mb-4">Technical Skills</h3>
              <div class="flex flex-wrap gap-3">
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">PHP</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">JavaScript</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">HTML/CSS</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">MySQL</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">Tailwind CSS</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">Git</span>
                <span class="skill-badge bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">Responsive Design</span>
              </div>
            </div>
            
            <div>
              <h3 class="text-xl font-semibold text-gray-800 mb-4">About This Project</h3>
              <p class="text-gray-600 leading-relaxed mb-4">
                InteriorHome is a web application designed to showcase interior design portfolios. 
                It features user authentication, CRUD operations for design management, and responsive design.
              </p>
              <p class="text-gray-600 leading-relaxed">
                Built with PHP for the backend, MySQL for database, and Tailwind CSS for styling. 
                The application follows modern web development practices with security considerations.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-gray-300 py-8">
    <div class="container mx-auto px-6 text-center">
      <p>&copy; <?= date('Y') ?> InteriorHome. All rights reserved.</p>
      <p class="mt-2">Developed with ❤ by Khairunnisa' Almaududy</p>
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