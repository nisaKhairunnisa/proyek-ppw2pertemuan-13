<?php
session_start();
require 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$action = $_GET['action'] ?? '';
$design_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_design = null;
$categories = ['Living Room', 'Bedroom', 'Kitchen', 'Bathroom', 'Office', 'Garden'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid form submission");
        }

        // Validate and sanitize inputs
        $title = trim(htmlspecialchars($_POST['title'] ?? ''));
        $description = trim(htmlspecialchars($_POST['description'] ?? ''));
        $category = trim(htmlspecialchars($_POST['category'] ?? ''));
        $image_url = filter_var(trim($_POST['image_url'] ?? ''), FILTER_VALIDATE_URL);
        
        // Validation
        if (empty($title)) {
            throw new Exception("Title is required");
        }
        if (strlen($title) > 100) {
            throw new Exception("Title must be less than 100 characters");
        }
        if ($image_url === false && !empty($_POST['image_url'])) {
            throw new Exception("Invalid image URL format");
        }

        // Process CRUD operations
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO designs (user_id, title, description, image_url, category) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], 
                $title, 
                $description, 
                $image_url ?: null, 
                $category ?: null
            ]);
            $_SESSION['success'] = "Design created successfully!";
        } 
        elseif ($action === 'update' && $design_id > 0) {
            $stmt = $conn->prepare("UPDATE designs SET title=?, description=?, image_url=?, category=?, updated_at=NOW() 
                                  WHERE id=? AND user_id=?");
            $stmt->execute([
                $title, 
                $description, 
                $image_url ?: null, 
                $category ?: null,
                $design_id,
                $_SESSION['user_id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Design not found or you don't have permission");
            }
            $_SESSION['success'] = "Design updated successfully!";
        }
        
        header("Location: crud.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
        header("Location: crud.php" . ($design_id ? "?action=edit&id=$design_id" : ""));
        exit();
    }
} 
elseif ($action === 'delete' && $design_id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM designs WHERE id=? AND user_id=?");
        $stmt->execute([$design_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Design not found or you don't have permission");
        }
        $_SESSION['success'] = "Design deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: crud.php");
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// Fetch designs for current user
$stmt = $conn->prepare("SELECT * FROM designs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$designs = $stmt->fetchAll();

// Count total designs
$total_stmt = $conn->prepare("SELECT COUNT(*) FROM designs WHERE user_id = ?");
$total_stmt->execute([$_SESSION['user_id']]);
$total_designs = $total_stmt->fetchColumn();
$total_pages = ceil($total_designs / $limit);

// Fetch design for editing
if ($action === 'edit' && $design_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM designs WHERE id = ? AND user_id = ?");
    $stmt->execute([$design_id, $_SESSION['user_id']]);
    $current_design = $stmt->fetch();
    
    if (!$current_design) {
        $_SESSION['error'] = "Design not found or you don't have permission";
        header("Location: crud.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Designs | InteriorHome</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .design-card {
      transition: all 0.3s ease;
    }
    .design-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .image-preview-container {
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f3f4f6;
      border-radius: 0.375rem;
      overflow: hidden;
    }
    .image-preview {
      max-height: 100%;
      max-width: 100%;
      display: <?= isset($current_design['image_url']) && !empty($current_design['image_url']) ? 'block' : 'none' ?>;
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Navigation -->
  <nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-gray-800">InteriorHome</a>
      <div class="flex items-center space-x-4">
        <span class="hidden md:inline text-gray-600">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        <ul class="hidden md:flex space-x-6 text-gray-700">
          <li><a href="index.php" class="hover:text-indigo-600 transition">Home</a></li>
          <li><a href="developer.php" class="hover:text-indigo-600 transition">Developer</a></li>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="admin.php" class="hover:text-indigo-600 transition">Admin</a></li>
          <?php endif; ?>
          <li><a href="profile.php" class="hover:text-indigo-600 transition">Profile</a></li>
          <li><a href="crud.php" class="hover:text-indigo-600 transition font-medium">My Designs</a></li>
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
        <li><a href="crud.php" class="block hover:text-indigo-600 transition font-medium">My Designs</a></li>
        <li><a href="logout.php" class="block hover:text-indigo-600 transition">Logout</a></li>
      </ul>
    </div>
  </nav>

  <div class="container mx-auto px-4 py-8">
    <!-- Header and Messages -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Manage Your Designs</h1>
        <p class="text-gray-600">Create and manage your interior design portfolio</p>
      </div>
      <div class="flex space-x-3">
        <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">
          <i class="fas fa-home mr-2"></i>Home
        </a>
        <a href="crud.php?action=create" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
          <i class="fas fa-plus mr-2"></i>New Design
        </a>
      </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
        <div class="flex items-center">
          <i class="fas fa-check-circle mr-2"></i>
          <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <!-- Design Form -->
    <?php if ($action === 'create' || $action === 'edit'): ?>
      <div class="bg-white rounded-lg shadow-md p-6 mb-8 design-card">
        <h2 class="text-xl font-semibold mb-4 flex items-center">
          <i class="fas fa-<?= $current_design ? 'edit' : 'plus' ?> mr-2 text-indigo-600"></i>
          <?= $current_design ? 'Edit Design' : 'Add New Design' ?>
        </h2>
        
        <form method="POST" action="crud.php?action=<?= $current_design ? 'update' : 'create' ?>">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <?php if ($current_design): ?>
            <input type="hidden" name="id" value="<?= $current_design['id'] ?>">
          <?php endif; ?>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div>
                <label class="block text-gray-700 mb-2 font-medium">Title*</label>
                <input type="text" name="title" required 
                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      value="<?= htmlspecialchars($current_design['title'] ?? ($_SESSION['form_data']['title'] ?? '')) ?>"
                      placeholder="Modern Living Room Design">
              </div>
              
              <div>
                <label class="block text-gray-700 mb-2 font-medium">Category</label>
                <select name="category" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  <option value="">Select a category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" 
                      <?= ($current_design['category'] ?? ($_SESSION['form_data']['category'] ?? '')) === $cat ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cat) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div>
                <label class="block text-gray-700 mb-2 font-medium">Image URL</label>
                <input type="url" name="image_url" id="image_url"
                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      value="<?= htmlspecialchars($current_design['image_url'] ?? ($_SESSION['form_data']['image_url'] ?? '')) ?>"
                      placeholder="https://example.com/image.jpg"
                      onchange="updateImagePreview(this.value)">
                <p class="text-xs text-gray-500 mt-1">Paste a direct image URL</p>
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2 font-medium">Preview</label>
              <div class="image-preview-container">
                <img id="image_preview" src="<?= htmlspecialchars($current_design['image_url'] ?? '') ?>" 
                     alt="Design preview" class="image-preview">
                <span id="no_image" class="text-gray-400 <?= isset($current_design['image_url']) && !empty($current_design['image_url']) ? 'hidden' : '' ?>">
                  Image preview will appear here
                </span>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-gray-700 mb-2 font-medium">Description</label>
            <textarea name="description" rows="4"
                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      placeholder="Describe your design..."><?= 
                      htmlspecialchars($current_design['description'] ?? ($_SESSION['form_data']['description'] ?? '')) ?></textarea>
          </div>
          
          <div class="flex justify-end space-x-4 mt-6">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
              <i class="fas fa-save mr-2"></i>
              <?= $current_design ? 'Update Design' : 'Save Design' ?>
            </button>
            <a href="crud.php" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
              <i class="fas fa-times mr-2"></i>Cancel
            </a>
          </div>
        </form>
        <?php unset($_SESSION['form_data']); ?>
      </div>
    <?php endif; ?>

    <!-- Designs List -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <h2 class="text-xl font-semibold flex items-center">
          <i class="fas fa-palette mr-2 text-indigo-600"></i>
          Your Design Portfolio
        </h2>
        <div class="flex items-center space-x-2 mt-2 md:mt-0">
          <span class="text-gray-600">Total: <?= $total_designs ?> design<?= $total_designs !== 1 ? 's' : '' ?></span>
          <?php if ($total_designs > 0): ?>
            <span class="hidden md:inline">|</span>
            <a href="crud.php?action=create" class="text-indigo-600 hover:underline hidden md:inline">
              <i class="fas fa-plus mr-1"></i>Add New
            </a>
          <?php endif; ?>
        </div>
      </div>
      
      <?php if (empty($designs)): ?>
        <div class="text-center py-12">
          <div class="mx-auto w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-palette text-indigo-600 text-3xl"></i>
          </div>
          <h3 class="text-xl font-medium text-gray-700 mb-2">No designs found</h3>
          <p class="text-gray-500 mb-6">Start by creating your first design</p>
          <a href="crud.php?action=create" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>Create Design
          </a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <?php foreach ($designs as $design): ?>
            <div class="bg-white rounded-lg shadow-md border border-gray-100 overflow-hidden design-card">
              <?php if (!empty($design['image_url'])): ?>
                <img src="<?= htmlspecialchars($design['image_url']) ?>" alt="<?= htmlspecialchars($design['title']) ?>" 
                     class="w-full h-48 object-cover">
              <?php else: ?>
                <div class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400">
                  <i class="fas fa-image text-4xl"></i>
                </div>
              <?php endif; ?>
              
              <div class="p-4">
                <h3 class="font-semibold text-lg mb-1 truncate"><?= htmlspecialchars($design['title']) ?></h3>
                <?php if (!empty($design['category'])): ?>
                  <span class="inline-block bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full mb-2">
                    <?= htmlspecialchars($design['category']) ?>
                  </span>
                <?php endif; ?>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($design['description']) ?></p>
                <div class="flex justify-between items-center text-sm text-gray-500">
                  <span><?= date('M j, Y', strtotime($design['created_at'])) ?></span>
                  <div class="flex space-x-2">
                    <a href="crud.php?action=edit&id=<?= $design['id'] ?>" 
                       class="text-indigo-600 hover:text-indigo-800" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="crud.php?action=delete&id=<?= $design['id'] ?>" 
                       class="text-red-600 hover:text-red-800" title="Delete"
                       onclick="return confirm('Are you sure you want to delete this design?')">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="flex justify-center mt-8">
            <nav class="flex items-center space-x-2">
              <?php if ($page > 1): ?>
                <a href="crud.php?page=<?= $page - 1 ?>" class="px-4 py-2 border rounded-lg text-indigo-600 hover:bg-indigo-50 transition">
                  <i class="fas fa-chevron-left mr-1"></i> Previous
                </a>
              <?php endif; ?>
              
              <?php 
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              if ($start > 1) echo '<span class="px-3 py-1">...</span>';
              for ($i = $start; $i <= $end; $i++): ?>
                <a href="crud.php?page=<?= $i ?>" class="px-4 py-2 border rounded-lg <?= $i === $page ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-indigo-600 hover:bg-indigo-50' ?> transition">
                  <?= $i ?>
                </a>
              <?php endfor;
              if ($end < $total_pages) echo '<span class="px-3 py-1">...</span>'; 
              ?>
              
              <?php if ($page < $total_pages): ?>
                <a href="crud.php?page=<?= $page + 1 ?>" class="px-4 py-2 border rounded-lg text-indigo-600 hover:bg-indigo-50 transition">
                  Next <i class="fas fa-chevron-right ml-1"></i>
                </a>
              <?php endif; ?>
            </nav>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Update image preview
    function updateImagePreview(url) {
      const preview = document.getElementById('image_preview');
      const noImage = document.getElementById('no_image');
      
      if (url) {
        preview.src = url;
        preview.style.display = 'block';
        noImage.classList.add('hidden');
      } else {
        preview.style.display = 'none';
        noImage.classList.remove('hidden');
      }
    }
    
    // Initialize image preview if editing with image
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($current_design['image_url']) && !empty($current_design['image_url'])): ?>
        updateImagePreview("<?= htmlspecialchars($current_design['image_url']) ?>");
      <?php endif; ?>
      
      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      const mobileMenu = document.getElementById('mobile-menu');
      
      menuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
      });
      
      // Close mobile menu when clicking outside
      document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
          mobileMenu.classList.add('hidden');
        }
      });
    });
  </script>
</body>
</html>