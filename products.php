<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user information
$user_query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get sorting parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build the query
$query = "SELECT * FROM products WHERE stock > 0";
if ($category !== 'all') {
    $query .= " AND category = ?";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY created_at DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($category !== 'all') {
    $stmt->bind_param("s", $category);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get distinct categories
$category_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
$categories = $conn->query($category_query)->fetch_all(MYSQLI_ASSOC);

// Get cart count
$cart_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - KickStep</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Loading Overlay (Optional: Add if needed for a consistent loading animation) -->
    <!-- <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div> -->

    <!-- Animated Background (Optional: Add if needed for a consistent background) -->
    <!-- <div class="bg-animation"></div>
    <div id="particles"></div> -->

    <!-- Header -->
    <header id="header">
        <nav>
            <a href="home.php" class="logo shine">
                <i class="fas fa-shoe-prints"></i>
                KickStep
            </a>
            <div class="nav-links">
                <a href="home.php">Home</a>
                <a href="products.php" class="active">Shop</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </div>
            <div class="nav-icons">
                <a href="cart.php" class="floating">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge"><?php echo $cart_count; ?></span>
                </a>
                <div class="user-dropdown">
                    <a href="#"><i class="fas fa-user"></i></a>
                    <div class="dropdown-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <ul>
                            <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> <span>My Orders</span></a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="filters">
                <div class="filter-group">
                    <label>Category:</label>
                    <!-- Custom Category Dropdown -->
                    <div class="custom-dropdown" id="categoryDropdown">
                        <div class="dropdown-display">
                            <span class="selected-value">All Categories</span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </div>
                        <ul class="dropdown-options">
                            <li data-value="all" class="selected">All Categories</li>
                            <?php foreach ($categories as $cat): ?>
                            <li data-value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- Removed native select -->
                    <?php /*
                    <select class="filter-select" onchange="window.location.href='?category='+this.value+'&sort=<?php echo $sort; ?>'">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    */ ?>
                </div>
                <div class="filter-group">
                    <label>Sort by:</label>
                    <!-- Custom Sort by Dropdown -->
                    <div class="custom-dropdown" id="sortByDropdown">
                        <div class="dropdown-display">
                             <span class="selected-value">
                                <?php
                                    switch ($sort) {
                                        case 'oldest': echo 'Oldest First'; break;
                                        case 'price_low': echo 'Price: Low to High'; break;
                                        case 'price_high': echo 'Price: High to Low'; break;
                                        default: echo 'Newest First'; break; // newest
                                    }
                                ?>
                             </span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </div>
                        <ul class="dropdown-options">
                            <li data-value="newest" <?php echo $sort === 'newest' ? 'class="selected"' : ''; ?>>Newest First</li>
                            <li data-value="oldest" <?php echo $sort === 'oldest' ? 'class="selected"' : ''; ?>>Oldest First</li>
                            <li data-value="price_low" <?php echo $sort === 'price_low' ? 'class="selected"' : ''; ?>>Price: Low to High</li>
                            <li data-value="price_high" <?php echo $sort === 'price_high' ? 'class="selected"' : ''; ?>>Price: High to Low</li>
                        </ul>
                    </div>
                    <!-- Removed native select -->
                     <?php /*
                    <select class="filter-select" onchange="window.location.href='?category=<?php echo $category; ?>&sort='+this.value">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                    */ ?>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="empty-products">
                <i class="fas fa-box-open"></i>
                <h2>No Products Found</h2>
                <p>We couldn't find any products matching your criteria.</p>
            </div>
            <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image_path'] ? 'uploads/products/' . htmlspecialchars($product['image_path']) : 'https://via.placeholder.com/300x300?text=Product+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             loading="lazy">
                        <?php if ($product['stock'] < 5): ?>
                            <div class="product-tag">Limited Stock!</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <div class="product-bottom">
                            <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                            <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>KickStep</h3>
                    <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.facebook.com/"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                        <a href="#https://in.pinterest.com/"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="products.php">New Arrivals</a></li>
                    
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="contact.php">FAQs</a></li>
                        
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="products.php">Our Stores</a></li>
                        
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        const badge = document.querySelector('.badge');
                        badge.textContent = parseInt(badge.textContent) + 1;
                        badge.style.animation = 'none';
                        badge.offsetHeight; // Trigger reflow
                        badge.style.animation = 'pulse 0.6s ease-in-out';

                        // Animate the button (using modern theme hover effect)
                        this.style.transform = 'scale(1.1) rotate(90deg)';
                        this.style.boxShadow = '0 10px 25px rgba(255, 107, 53, 0.4)';
                        setTimeout(() => {
                            this.style.transform = '';
                            this.style.boxShadow = '';
                        }, 300);

                        // Show success notification (if implemented using the modern theme style)
                        // showNotification('Product added to cart!', 'success');

                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the product to cart');
                });
            });
        });
        
        // Optional: Add header scroll effect if you want it on products page too
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    });

    // --- Custom Dropdown Functionality ---
    document.addEventListener('DOMContentLoaded', function() {
        const customDropdowns = document.querySelectorAll('.custom-dropdown');

        customDropdowns.forEach(dropdown => {
            const display = dropdown.querySelector('.dropdown-display');
            const optionsList = dropdown.querySelector('.dropdown-options');
            const options = optionsList.querySelectorAll('li');
            const selectedValueSpan = dropdown.querySelector('.selected-value');

            // Toggle dropdown on display click
            display.addEventListener('click', function() {
                dropdown.classList.toggle('open');
            });

            // Select option on click
            options.forEach(option => {
                option.addEventListener('click', function() {
                    const selectedValue = this.dataset.value;
                    const selectedText = this.textContent;

                    // Update displayed value
                    selectedValueSpan.textContent = selectedText;

                    // Update selected class on options list
                    options.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');

                    // Close dropdown
                    dropdown.classList.remove('open');

                    // Trigger filtering/sorting based on the dropdown
                    const urlParams = new URLSearchParams(window.location.search);
                    if (dropdown.id === 'categoryDropdown') {
                        urlParams.set('category', selectedValue);
                    } else if (dropdown.id === 'sortByDropdown') {
                        urlParams.set('sort', selectedValue);
                    }
                    window.location.search = urlParams.toString();
                });
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            customDropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('open');
                }
            });
        });
    });
    // --- End Custom Dropdown Functionality ---

    </script>
</body>
</html> 