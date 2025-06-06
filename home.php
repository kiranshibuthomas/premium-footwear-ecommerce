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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div id="particles"></div>

    <!-- Header -->
    <header id="header">
        <nav>
            <a href="#" class="logo shine">
                <i class="fas fa-shoe-prints"></i>
                KickStep
            </a>
            <div class="nav-links">
                <a href="home.php" class="active">Home</a>
                <a href="products.php">Shop</a>
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
                            <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text fade-in-up">
                <h1 class="shine">Step Into Your New Style</h1>
                <p>Discover the latest trends in premium footwear with our exclusive collection. From sports shoes to casual wear, we've got every step covered with style and comfort.</p>
                <a href="products.php" class="btn shine">
                    <i class="fas fa-shopping-bag"></i>
                    Shop Now
                </a>
            </div>
            <!-- Placeholder for the 3D Shoe Animation -->
            <div class="hero-shoe-animation">
                <img src="uploads/ui/Spin.png" alt="3D Spinning Shoe">
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured" id="products">
        <div class="section-title fade-in-up">
            <h2>Featured Products</h2>
            <p>Check out our most popular shoes that customers love and wear with pride</p>
        </div>
        <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
            <div class="product-card fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
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
    </section>

    <!-- Categories -->
    <section class="categories" id="categories">
        <div class="section-title fade-in-up">
            <h2>Shop by Category</h2>
            <p>Find your perfect pair by browsing our extensive collection of premium categories</p>
        </div>
        <div class="category-grid">
            <?php
            $categories = [
                ['name' => 'Running', 'image' => 'running.png', 'count' => '50+ Premium Products'],
                ['name' => 'Casual', 'image' => 'casual.png', 'count' => '78+ Comfort Styles'],
                ['name' => 'Formal', 'image' => 'formal.png', 'count' => '32+ Luxury Designs'],
                ['name' => 'Sports', 'image' => 'sports.png', 'count' => '45+ Performance Shoes']
            ];
            foreach ($categories as $index => $category): ?>
            <div class="category-card fade-in-up" style="animation-delay: <?php echo $index * 0.2; ?>s">
                <img src="uploads/categories/<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?> Shoes">
                <div class="category-content">
                    <h3><?php echo $category['name']; ?></h3>
                    <p><?php echo $category['count']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter" id="newsletter">
        <div class="fade-in-up">
            <h2>Stay Updated</h2>
            <p>Subscribe to our newsletter to receive updates on new arrivals, exclusive offers, and insider access to limited edition drops.</p>
            <form class="email-form" id="newsletter-form">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                    Subscribe
                </button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column fade-in-up">
                <h3>KickStep</h3>
                <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes that define your journey.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/" class="floating"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/" class="floating" style="animation-delay: 0.2s"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/" class="floating" style="animation-delay: 0.4s"><i class="fab fa-instagram"></i></a>
                    <a href="https://in.pinterest.com/" class="floating" style="animation-delay: 0.6s"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-column fade-in-up" style="animation-delay: 0.2s">
                <h3>Shop</h3>
                <ul>
                    <li><a href="products.php">All Products</a></li>
                    <li><a href="products.php">New Arrivals</a></li>
                    <li><a href="products.php">Best Sellers</a></li>
                    <li><a href="products.php">Sale Items</a></li>
                </ul>
            </div>
            
            <div class="footer-column fade-in-up" style="animation-delay: 0.4s">
                <h3>Support</h3>
                <ul>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="about.php">FAQs</a></li>
                    <li><a href="orders.php">Shipping & Returns</a></li>
                </ul>
            </div>
            
            <div class="footer-column fade-in-up" style="animation-delay: 0.6s">
                <h3>Company</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="products.php">Our Stores</a></li>
                    <li><a href="about.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright fade-in-up" style="animation-delay: 0.8s">
            <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.top = Math.random() * 100 + 'vh';
                particle.style.animationDelay = Math.random() * 5 + 's';
                container.appendChild(particle);
            }
        }

        // Loading animation
        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            createParticles();
            setTimeout(() => {
                loadingOverlay.classList.add('hidden');
            }, 1000);
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Add to cart functionality with enhanced animations
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const card = this.closest('.product-card');
                
                // Add click animation
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.style.transform = '';
                }, 200);
                
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
                        // Add success animation
                        this.style.background = '#4CAF50';
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        
                        // Update cart badge with animation
                        const cartBadge = document.querySelector('.badge');
                        const currentCount = parseInt(cartBadge.textContent);
                        cartBadge.textContent = currentCount + 1;
                        
                        // Add cart animation
                        cartBadge.style.animation = 'none';
                        cartBadge.offsetHeight; // Trigger reflow
                        cartBadge.style.animation = 'pulse 0.6s ease-in-out';
                        
                        // Add success ripple effect
                        const ripple = document.createElement('div');
                        ripple.className = 'ripple';
                        this.appendChild(ripple);
                        setTimeout(() => ripple.remove(), 1000);
                        
                        // Reset button after animation
                        setTimeout(() => {
                            this.style.background = '';
                            this.innerHTML = '<i class="fas fa-plus"></i>';
                        }, 1500);
                        
                        // Show success message
                        showNotification('Product added to cart!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to add product to cart', 'error');
                });
            });
        });

        // Newsletter form submission with animation
        document.getElementById('newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            
            if (email) {
                // Add loading animation
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
                button.disabled = true;
                
                // Simulate API call
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-check"></i> Subscribed!';
                    showNotification('Thank you for subscribing! Welcome to KickStep family.', 'success');
                    this.reset();
                    
                    // Reset button after animation
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
                        button.disabled = false;
                    }, 2000);
                }, 1500);
            }
        });

        // Enhanced notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type} fade-in-up`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            // Trigger animation
            requestAnimationFrame(() => {
                notification.style.transform = 'translateX(0)';
            });
            
            // Auto remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Enhanced product card hover effects
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)';
            });
        });

        // Enhanced category card hover effects
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)';
            });
        });

        // Intersection Observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        // Observe all elements with fade-in-up class
        document.querySelectorAll('.fade-in-up').forEach(element => {
            observer.observe(element);
        });

        // --- Image Cycling with Animation ---
        const heroShoeImg = document.querySelector('.hero-shoe-animation img');
        const images = [
            'uploads/ui/shoe1.png',
            'uploads/ui/shoe2.png',
            'uploads/ui/shoe6.png',
            'uploads/ui/shoe4.png',
            'uploads/ui/shoe5.png',
            'uploads/ui/shoe3.png',
            'uploads/ui/shoe7.png',
            'uploads/ui/Spin.png'
            // Add more image paths here if you have more
        ];
        let currentImageIndex = 0;

        function changeShoeImage() {
            heroShoeImg.classList.add('woosh-out');

            setTimeout(() => {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                heroShoeImg.src = images[currentImageIndex];

                heroShoeImg.classList.remove('woosh-out');
                heroShoeImg.classList.add('woosh-in');

                setTimeout(() => {
                    heroShoeImg.classList.remove('woosh-in');
                }, 300); // Match woosh-in animation duration (0.3s)

            }, 300); // Match woosh-out animation duration (0.3s)
        }

        // Start cycling every 10 seconds
        setInterval(changeShoeImage, 10000); // Changed interval to 10000ms (10 seconds)

        // Initial image load (optional, use if first image isn't in HTML)
        // heroShoeImg.src = images[currentImageIndex];

        // --- End Image Cycling ---

    </script>
</body>
</html> 