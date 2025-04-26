<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get user data for display
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Interior Home Design</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .carousel-item {
      transition: opacity 0.5s ease;
    }
  </style>
</head>
<body class="bg-gray-50 font-['Roboto']">

  <!-- Dynamic Navbar -->
  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-gray-800">InteriorHome</a>
      <div class="flex items-center space-x-4">
        <?php if(isset($_SESSION['user_id'])): ?>
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
        <?php else: ?>
          <ul class="hidden md:flex space-x-6 text-gray-700">
            <li><a href="index.php" class="hover:text-indigo-600 transition">Home</a></li>
            <li><a href="developer.php" class="hover:text-indigo-600 transition">Developer</a></li>
            <li><a href="login.php" class="hover:text-indigo-600 transition">Login</a></li>
            <li><a href="signup.php" class="hover:text-indigo-600 transition">Sign Up</a></li>
          </ul>
        <?php endif; ?>
      </div>
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

  <!-- Carousel -->
  <section class="container mx-auto mt-8">
    <div class="relative overflow-hidden rounded-lg shadow-lg">
      <div class="carousel">
        <div class="carousel-item active">
          <img src="https://images.pexels.com/photos/1866149/pexels-photo-1866149.jpeg" alt="Living Room" class="w-full h-64 md:h-96 object-cover" loading="lazy" />
        </div>
        <div class="carousel-item">
          <img src="https://images.pexels.com/photos/271743/pexels-photo-271743.jpeg" alt="Bedroom" class="w-full h-64 md:h-96 object-cover" loading="lazy" />
        </div>
        <div class="carousel-item">
          <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Kitchen" class="w-full h-64 md:h-96 object-cover" loading="lazy" />
        </div>
      </div>
      <!-- Carousel controls -->
      <button id="prev" class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2 shadow hover:bg-opacity-100 transition">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button id="next" class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2 shadow hover:bg-opacity-100 transition">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </section>

  <!-- Featured Designs -->
  <section class="container mx-auto mt-12 px-6">
  <h2 class="text-3xl font-bold text-gray-800 mb-6">Our Interior Designs</h2>
  <?php
  // Fetch featured cards from database
  $stmt = $conn->prepare("SELECT * FROM featured_cards ORDER BY created_at DESC LIMIT 3");
  $stmt->execute();
  $featured_cards = $stmt->fetchAll();
  ?>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <?php foreach ($featured_cards as $card): ?>
      <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
        <img src="<?= htmlspecialchars($card['image_url']) ?>" 
             alt="<?= htmlspecialchars($card['title']) ?>" 
             class="w-full h-48 object-cover" 
             loading="lazy">
        <div class="p-4">
          <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($card['title']) ?></h3>
          <p class="text-gray-600"><?= htmlspecialchars($card['description']) ?></p>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="mt-4 text-right">
              <a href="admin.php?action=edit&id=<?= $card['id'] ?>" 
                 class="text-sm text-indigo-600 hover:underline">Edit</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

  <!-- Footer -->
  <footer class="bg-gray-800 text-gray-300 mt-16 py-8">
    <div class="container mx-auto px-6 text-center">
      <p>&copy; <?= date('Y') ?> InteriorHome. All rights reserved.</p>
      <?php if(isset($_SESSION['user_id'])): ?>
        <p class="mt-2 text-sm">Logged in as: <?= htmlspecialchars($user['username']) ?></p>
      <?php endif; ?>
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

    // Carousel functionality
    const carousel = {
      items: document.querySelectorAll('.carousel-item'),
      index: 0,
      init() {
        this.showItem(this.index);
        document.getElementById('prev').addEventListener('click', () => this.prev());
        document.getElementById('next').addEventListener('click', () => this.next());
        this.interval = setInterval(() => this.next(), 5000);
      },
      showItem(index) {
        this.items.forEach((item, i) => {
          item.classList.toggle('hidden', i !== index);
          item.classList.toggle('active', i === index);
        });
        this.index = index;
      },
      prev() {
        this.showItem((this.index - 1 + this.items.length) % this.items.length);
        this.resetInterval();
      },
      next() {
        this.showItem((this.index + 1) % this.items.length);
        this.resetInterval();
      },
      resetInterval() {
        clearInterval(this.interval);
        this.interval = setInterval(() => this.next(), 5000);
      }
    };

    // Initialize carousel
    document.addEventListener('DOMContentLoaded', () => carousel.init());
  </script>
</body>
</html>