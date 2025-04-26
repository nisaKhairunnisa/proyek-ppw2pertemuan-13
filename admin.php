<?php
session_start();
require 'db.php';

// Redirect if not admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : '';
$card_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_card = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid form submission");
        }

        // Validate and sanitize inputs
        $title = isset($_POST['title']) ? trim(htmlspecialchars($_POST['title'])) : '';
        $description = isset($_POST['description']) ? trim(htmlspecialchars($_POST['description'])) : '';
        $image_url = isset($_POST['image_url']) ? filter_var(trim($_POST['image_url']), FILTER_VALIDATE_URL) : false;
        
        if (empty($title)) {
            throw new Exception("Title is required");
        }
        
        if ($image_url === false) {
            throw new Exception("Valid Image URL is required");
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO featured_cards (title, description, image_url) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $image_url]);
            $_SESSION['success'] = "Card created successfully!";
        } 
        elseif ($action === 'update' && $card_id > 0) {
            $stmt = $conn->prepare("UPDATE featured_cards SET title=?, description=?, image_url=? WHERE id=?");
            $stmt->execute([$title, $description, $image_url, $card_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Card not found or no changes made");
            }
            
            $_SESSION['success'] = "Card updated successfully!";
        }
        
        header("Location: admin.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
        header("Location: admin.php" . ($card_id ? "?action=edit&id=$card_id" : ""));
        exit();
    }
} 
elseif ($action === 'delete' && $card_id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM featured_cards WHERE id=?");
        $stmt->execute([$card_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Card not found");
        }
        
        $_SESSION['success'] = "Card deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: admin.php");
    exit();
}

// Fetch all cards
$stmt = $conn->prepare("SELECT * FROM featured_cards ORDER BY created_at DESC");
$stmt->execute();
$cards = $stmt->fetchAll();

// Fetch card for editing
if ($action === 'edit' && $card_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM featured_cards WHERE id = ?");
    $stmt->execute([$card_id]);
    $current_card = $stmt->fetch();
    
    if (!$current_card) {
        $_SESSION['error'] = "Card not found";
        header("Location: admin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - InteriorHome</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .card-image-preview {
      max-height: 200px;
      max-width: 100%;
      display: <?= isset($current_card['image_url']) && !empty($current_card['image_url']) ? 'block' : 'none' ?>;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Navbar -->
  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-gray-800">InteriorHome</a>
      <div class="flex items-center space-x-4">
        <?php if(isset($_SESSION['username'])): ?>
          <span class="hidden md:inline text-gray-600">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <?php endif; ?>
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

  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Admin Dashboard</h1>
      <a href="admin.php?action=create" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
        Add New Card
      </a>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <?php unset($_SESSION['success']) ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <?php unset($_SESSION['error']) ?>
      </div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
      <!-- Card Form -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">
          <?= isset($current_card) ? 'Edit Featured Card' : 'Add New Featured Card' ?>
        </h2>
        <form method="POST" action="admin.php?action=<?= isset($current_card) ? 'update' : 'create' ?><?= isset($current_card) ? '&id='.$current_card['id'] : '' ?>">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <?php if (isset($current_card)): ?>
            <input type="hidden" name="id" value="<?= $current_card['id'] ?>">
          <?php endif; ?>
          
          <div class="mb-4">
            <label class="block text-gray-700 mb-2">Title*</label>
            <input type="text" name="title" required 
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   value="<?= isset($current_card['title']) ? htmlspecialchars($current_card['title']) : (isset($_SESSION['form_data']['title']) ? htmlspecialchars($_SESSION['form_data']['title']) : '') ?>">
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3"
                      class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= 
                      isset($current_card['description']) ? htmlspecialchars($current_card['description']) : (isset($_SESSION['form_data']['description']) ? htmlspecialchars($_SESSION['form_data']['description']) : '') ?></textarea>
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 mb-2">Image URL*</label>
            <input type="url" name="image_url" id="image_url" required
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   value="<?= isset($current_card['image_url']) ? htmlspecialchars($current_card['image_url']) : (isset($_SESSION['form_data']['image_url']) ? htmlspecialchars($_SESSION['form_data']['image_url']) : '') ?>"
                   onchange="updateImagePreview(this.value)">
            <img id="image_preview" src="<?= isset($current_card['image_url']) ? htmlspecialchars($current_card['image_url']) : '' ?>" 
                 alt="Card preview" class="card-image-preview mt-2 rounded">
          </div>
          
          <div class="flex items-center space-x-4">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
              <?= isset($current_card) ? 'Update Card' : 'Add Card' ?>
            </button>
            
            <a href="admin.php" class="text-gray-600 hover:underline">Cancel</a>
          </div>
        </form>
        <?php unset($_SESSION['form_data']) ?>
      </div>
    <?php endif; ?>

    <!-- Cards List -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">Featured Cards</h2>
      
      <?php if (empty($cards)): ?>
        <div class="text-center py-8">
          <p class="text-gray-600 mb-4">No featured cards found.</p>
          <a href="admin.php?action=create" class="text-indigo-600 hover:underline">Create your first card</a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($cards as $card): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
              <img src="<?= htmlspecialchars($card['image_url']) ?>" alt="<?= htmlspecialchars($card['title']) ?>" 
                   class="w-full h-48 object-cover">
              <div class="p-4">
                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($card['title']) ?></h3>
                <p class="text-gray-600 mb-4"><?= htmlspecialchars($card['description']) ?></p>
                <div class="flex justify-end space-x-2">
                  <a href="admin.php?action=edit&id=<?= $card['id'] ?>" 
                     class="text-indigo-600 hover:text-indigo-900 px-3 py-1 rounded hover:bg-indigo-50 transition">
                     <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="admin.php?action=delete&id=<?= $card['id'] ?>" 
                     class="text-red-600 hover:text-red-900 px-3 py-1 rounded hover:bg-red-50 transition"
                     onclick="return confirm('Are you sure you want to delete this card?')">
                     <i class="fas fa-trash"></i> Delete
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function updateImagePreview(url) {
      const preview = document.getElementById('image_preview');
      if (url) {
        preview.src = url;
        preview.style.display = 'block';
      } else {
        preview.style.display = 'none';
      }
    }
    
    // Initialize image preview if editing with image
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($current_card['image_url']) && !empty($current_card['image_url'])): ?>
        updateImagePreview("<?= htmlspecialchars($current_card['image_url']) ?>");
      <?php endif; ?>
    });

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