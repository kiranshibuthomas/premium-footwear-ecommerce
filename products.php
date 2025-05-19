<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    <link rel="stylesheet" href="css/dark-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .products-section {
            padding: 80px 0;
            min-height: calc(100vh - 400px);
        }

        .filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--darker);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-group label {
            color: var(--gray);
        }

        .filter-select {
            padding: 8px 15px;
            border-radius: 5px;
            background-color: var(--dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light);
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .product-card {
            background-color: var(--darker);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            position: relative;
            padding-top: 100%;
            background-color: var(--dark);
        }

        .product-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            background-color: rgba(229, 62, 62, 0.9);
            color: white;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            color: var(--light);
            margin: 0 0 5px 0;
            font-size: 18px;
        }

        .product-category {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .product-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price {
            color: var(--primary);
            font-weight: 600;
            font-size: 20px;
        }

        .add-to-cart {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .add-to-cart:hover {
            background-color: var(--primary-dark);
            transform: scale(1.1);
        }

        .empty-products {
            text-align: center;
            padding: 60px 0;
        }

        .empty-products i {
            font-size: 60px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-products h2 {
            color: var(--light);
            margin-bottom: 20px;
        }

        .empty-products p {
            color: var(--gray);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                gap: 15px;
            }

            .filter-group {
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <a href="home.php" class="logo">
                    <i class="fas fa-shoe-prints"></i>
                    KickStep
                </a>
                <div class="nav-links">
                    <a href="home.php">Home</a>
                    <a href="products.php" class="active">Shop</a>
         
                    
                    <a href="about.php">About</a>
                    <a href="#">Contact</a>
                </div>
                <div class="nav-icons">
                    <a href="#"><i class="fas fa-search"></i></a>
                    <a href="cart.php">
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
                                <li>
                                    <a href="profile.php">
                                        <i class="fas fa-user-circle"></i>
                                        My Profile
                                    </a>
                                </li>
                                <li>
                                    <a href="orders.php">
                                        <i class="fas fa-shopping-bag"></i>
                                        My Orders
                                    </a>
                                </li>
                                <li>
                                    <a href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="filters">
                <div class="filter-group">
                    <label>Category:</label>
                    <select class="filter-select" onchange="window.location.href='?category='+this.value+'&sort=<?php echo $sort; ?>'">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sort by:</label>
                    <select class="filter-select" onchange="window.location.href='?category=<?php echo $category; ?>&sort='+this.value">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
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
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="#">New Arrivals</a></li>
                        <li><a href="#">Best Sellers</a></li>
                        <li><a href="#">On Sale</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Stores</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Privacy Policy</a></li>
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
                        
                        // Animate the button
                        this.style.transform = 'scale(1.2)';
                        setTimeout(() => {
                            this.style.transform = 'scale(1)';
                        }, 200);
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
    });
    </script>
</body>
</html> 