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

// Fetch all products
$query = "SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC";
$result = $conn->query($query);
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

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
    <title>KickStep - Premium Footwear</title>
    <link rel="stylesheet" href="css/dark-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <a href="#" class="logo">
                    <i class="fas fa-shoe-prints"></i>
                    KickStep
                </a>
                <div class="nav-links">
                    <a href="home.php" class="active">Home</a>
                    <a href="products.php">Shop</a>
                    <a href="#">New Arrivals</a>
                    <a href="#">Brands</a>
                    <a href="#">About</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Step Into <br>Your New Style</h1>
                <p>Discover the latest trends in footwear with our premium collection. From sports shoes to formal wear, we've got you covered.</p>
                <a href="products.php" class="btn">Shop Now</a>
            </div>
            <div class="hero-image">
                <img src="uploads/products/" alt="Featured Shoe">
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured">
        <div class="container">
            <div class="section-title">
                <h2>Featured Products</h2>
                <p>Check out our most popular shoes that customers love</p>
            </div>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image_path'] ? 'uploads/products/' . htmlspecialchars($product['image_path']) : 'https://via.placeholder.com/300x200?text=Product+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             loading="lazy">
                        <?php if ($product['stock'] < 5): ?>
                            <div class="product-tag">Limited Stock!</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-category">Premium Footwear</div>
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
        </div>
    </section>

    <!-- Categories -->
    <section class="categories">
        <div class="container">
            <div class="section-title">
                <h2>Shop by Category</h2>
                <p>Find your perfect pair by browsing our extensive collection of categories</p>
            </div>
            <div class="category-grid">
                <div class="category-card">
                    <img src="uploads/categories/running.jpg" alt="Running Shoes">
                    <div class="category-content">
                        <h3>Running</h3>
                        <p>50+ Products</p>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="uploads/categories/casual.jpg" alt="Casual Shoes">
                    <div class="category-content">
                        <h3>Casual</h3>
                        <p>78+ Products</p>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="uploads/categories/formal.jpg" alt="Formal Shoes">
                    <div class="category-content">
                        <h3>Formal</h3>
                        <p>32+ Products</p>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="uploads/categories/sports.jpg" alt="Sports Shoes">
                    <div class="category-content">
                        <h3>Sports</h3>
                        <p>45+ Products</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter">
        <div class="container">
            <h2>Stay Updated</h2>
            <p>Subscribe to our newsletter to receive updates on new arrivals, special offers and other discount information.</p>
            <form class="email-form" id="newsletter-form">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit">Subscribe</button>
            </form>
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

    <script src="script/animations.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
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
                        const cartBadge = document.querySelector('.badge');
                        cartBadge.textContent = parseInt(cartBadge.textContent) + 1;
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // Newsletter form submission
        const newsletterForm = document.getElementById('newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                
                // Here you would typically send this to your backend
                alert('Thank you for subscribing! We will keep you updated.');
                this.reset();
            });
        }
    });
    </script>
</body>
</html> 