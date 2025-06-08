<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Add this at the top of the file, after the require statements
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-error.log');

// Create a debug log file
$debug_log = dirname(__DIR__) . '/debug.log';
function write_debug($message) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($debug_log, $log_message, FILE_APPEND);
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $price = floatval($_POST['price']);
                    $stock = intval($_POST['stock']);
                    $category = trim($_POST['category']);
                    
                    // Validate inputs
                    if (empty($name)) throw new Exception("Product name is required");
                    if ($price <= 0) throw new Exception("Price must be greater than zero");
                    if ($stock < 0) throw new Exception("Stock cannot be negative");
                    
                    // Handle file upload
                    $image_path = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['image']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (!in_array($ext, $allowed)) {
                            throw new Exception("Invalid file format. Allowed formats: " . implode(', ', $allowed));
                        }
                        
                        $upload_dir = '../uploads/products/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $image_path = 'product_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_path)) {
                            throw new Exception("Failed to upload image");
                        }
                    }

                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssdss", $name, $description, $price, $stock, $category, $image_path);
                    
                    if ($stmt->execute()) {
                        $success_message = "Product added successfully!";
                    } else {
                        throw new Exception("Failed to add product");
                    }
                    break;

                case 'edit':
                    $id = intval($_POST['id']);
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $price = floatval($_POST['price']);
                    $stock = intval($_POST['stock']);
                    $category = trim($_POST['category']);
                    
                    // Validate inputs
                    if (empty($name)) throw new Exception("Product name is required");
                    if ($price <= 0) throw new Exception("Price must be greater than zero");
                    if ($stock < 0) throw new Exception("Stock cannot be negative");

                    // Get current product data
                    $stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $current_product = $result->fetch_assoc();
                    
                    $image_path = $current_product['image_path']; // Keep existing image by default
                    $image_updated = false;

                    // Handle file upload for edit
                    if (isset($_FILES['image'])) {
                        write_debug("File upload detected");
                        
                        // Check if a new file was actually selected (not just the form being submitted)
                        if ($_FILES['image']['error'] === 0 && $_FILES['image']['size'] > 0) {
                            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                            $filename = $_FILES['image']['name'];
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            
                            if (!in_array($ext, $allowed)) {
                                throw new Exception("Invalid file format. Allowed formats: " . implode(', ', $allowed));
                            }
                            
                            // Use absolute path for uploads directory
                            $upload_dir = dirname(__DIR__) . '/uploads/products/';
                            
                            // Create directory if it doesn't exist
                            if (!file_exists($upload_dir)) {
                                if (!@mkdir($upload_dir, 0755, true)) {
                                    throw new Exception("Unable to create upload directory. Please contact support.");
                                }
                            }
                            
                            // Check directory permissions
                            if (!is_writable($upload_dir)) {
                                if (!@chmod($upload_dir, 0755)) {
                                    throw new Exception("Upload directory is not writable. Please contact support.");
                                }
                            }
                            
                            // Generate unique filename
                            $new_image_path = 'product_' . time() . '_' . uniqid() . '.' . $ext;
                            $full_path = $upload_dir . $new_image_path;
                            
                            // Try to move the uploaded file
                            if (@move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                                // Verify the file exists and is readable
                                if (file_exists($full_path) && is_readable($full_path)) {
                                    // Delete old image if exists
                                    if ($current_product['image_path']) {
                                        $old_file = $upload_dir . $current_product['image_path'];
                                        if (file_exists($old_file)) {
                                            @unlink($old_file);
                                        }
                                    }
                                    
                                    $image_path = $new_image_path;
                                    $image_updated = true;
                                } else {
                                    @unlink($full_path);
                                    throw new Exception("Failed to verify uploaded file. Please try again.");
                                }
                            } else {
                                throw new Exception("Failed to upload image. Please try again or contact support.");
                            }
                        } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                            // If there was an error other than no file selected
                            $error_messages = [
                                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                            ];
                            $error_message = isset($error_messages[$_FILES['image']['error']]) 
                                ? $error_messages[$_FILES['image']['error']] 
                                : 'Unknown upload error';
                            throw new Exception("Upload error: " . $error_message);
                        }
                    }
                    
                    // Update product with new image path
                    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, image_path=? WHERE id=?");
                    $stmt->bind_param("ssdsssi", $name, $description, $price, $stock, $category, $image_path, $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Product updated successfully!" . ($image_updated ? " Image has been updated." : "");
                    } else {
                        if ($image_updated) {
                            @unlink($full_path);
                        }
                        throw new Exception("Failed to update product. Please try again.");
                    }
                    break;

                case 'delete':
                    $id = intval($_POST['id']);
                    
                    // Instead of deleting, update the product to mark it as deleted
                    $stmt = $conn->prepare("UPDATE products SET stock = 0, name = CONCAT(name, ' (Deleted)') WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success_message = "Product has been removed from the store!";
                    } else {
                        throw new Exception("Failed to remove product");
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch all products
$products = $conn->query("SELECT * FROM products WHERE name NOT LIKE '%(Deleted)%' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link rel="stylesheet" href="../css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-details {
            padding: 15px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <a href="dashboard.php" class="admin-logo">
                <i class="fas fa-shopping-bag"></i>
                <span>Admin Panel</span>
            </a>
            <nav class="admin-nav">
                <ul>
                    <li class="admin-nav-item">
                        <a href="dashboard.php" class="admin-nav-link">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manageproduct.php" class="admin-nav-link active">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="orders.php" class="admin-nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="messages.php" class="admin-nav-link">
                            <i class="fas fa-envelope"></i>
                            Messages
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="logout.php" class="admin-nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Products</h1>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </header>

            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php 
                                if ($product['image_path']) {
                                    // Use absolute URL for images
                                    $image_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                                    $image_url .= dirname($_SERVER['PHP_SELF']) . '/../uploads/products/' . htmlspecialchars($product['image_path']);
                                    echo $image_url;
                                } else {
                                    echo '../uploads/products/default.jpg';
                                }
                            ?>" 
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            onerror="this.src='../uploads/products/default.jpg'">
                        </div>
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                            <p><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                            <div class="product-actions">
                                <button class="btn btn-secondary" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addModal')">&times;</span>
            <h2>Add New Product</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" step="0.01" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editModal')">&times;</span>
            <h2>Edit Product</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" required class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="edit_stock" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="edit_category" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*" class="form-control">
                    <small class="text-muted">Leave empty to keep current image</small>
                </div>

                <button type="submit" class="btn btn-primary">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function showEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('editModal').style.display = 'block';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function confirmDelete(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html> 