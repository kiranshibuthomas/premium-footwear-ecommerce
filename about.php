<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user information for the header
$user_query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

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
    <title>About Us - KickStep</title>
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
        <div class="container">
            <nav>
                <a href="home.php" class="logo shine">
                    <i class="fas fa-shoe-prints"></i>
                    KickStep
                </a>
                <div class="nav-links">
                    <a href="home.php">Home</a>
                    <a href="products.php">Shop</a>
                    <a href="about.php" class="active">About</a>
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
        </div>
    </header>

    <!-- About Hero Section -->
    <section class="about-hero">
        <div class="container">
            <div class="about-hero-content fade-in-up">
                <h1>Our Story</h1>
                <p>Step into the world of KickStep, where passion meets innovation in footwear fashion.</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-title fade-in-up">
                <h2>Who We Are</h2>
                <p>Discover the journey behind KickStep's success</p>
            </div>
            <div class="about-grid">
                <div class="about-card fade-in-up">
                    <i class="fas fa-history"></i>
                    <h3>Our History</h3>
                    <p>Founded in 2020, KickStep began with a simple mission: to provide premium footwear that combines style, comfort, and quality. What started as a small online store has grown into a beloved destination for shoe enthusiasts worldwide.</p>
                </div>
                <div class="about-card fade-in-up" style="animation-delay: 0.1s;">
                    <i class="fas fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>We strive to revolutionize the footwear industry by offering innovative designs, exceptional quality, and outstanding customer service. Every step we take is guided by our commitment to excellence.</p>
                </div>
                <div class="about-card fade-in-up" style="animation-delay: 0.2s;">
                    <i class="fas fa-eye"></i>
                    <h3>Our Vision</h3>
                    <p>To become the global leader in premium footwear, setting new standards in design, sustainability, and customer experience while maintaining our core values of quality and innovation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-title fade-in-up">
                <h2>Our Core Values</h2>
                <p>The principles that guide everything we do</p>
            </div>
            <div class="value-item fade-in-up">
                <i class="fas fa-star"></i>
                <div class="value-content">
                    <h3>Quality First</h3>
                    <p>We never compromise on the quality of our products, ensuring that every pair of shoes meets our high standards.</p>
                </div>
            </div>
            <div class="value-item fade-in-up" style="animation-delay: 0.1s;">
                <i class="fas fa-heart"></i>
                <div class="value-content">
                    <h3>Customer Satisfaction</h3>
                    <p>Your happiness is our priority. We go above and beyond to ensure you have the best shopping experience.</p>
                </div>
            </div>
            <div class="value-item fade-in-up" style="animation-delay: 0.2s;">
                <i class="fas fa-leaf"></i>
                <div class="value-content">
                    <h3>Sustainability</h3>
                    <p>We're committed to reducing our environmental footprint through sustainable practices and eco-friendly materials.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-title fade-in-up">
                <h2>Meet Our Team</h2>
                <p>The passionate individuals behind KickStep's success</p>
            </div>
            <div class="team-grid">
                <div class="team-member fade-in-up">
                    <img src="uploads/team/ceo.jpg" alt="CEO" onerror="this.src='https://via.placeholder.com/180x180?text=CEO'">
                    <h3>James Varghese</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.1s;">
                    <img src="uploads/team/designer.jpg" alt="Designer" onerror="this.src='https://via.placeholder.com/180x180?text=Designer'">
                    <h3>Jojo Manuel P</h3>
                    <p>Leader</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.2s;">
                    <img src="uploads/team/manager.jpg" alt="Manager" onerror="this.src='https://via.placeholder.com/180x180?text=Manager'">
                    <h3>Kiran Shibu Thomas</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.3s;">
                    <img src="uploads/team/marketing.jpg" alt="Marketing Director" onerror="this.src='https://via.placeholder.com/180x180?text=Marketing'">
                    <h3>Ashwin Sabu</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.4s;">
                    <img src="uploads/team/tech.jpg" alt="Tech Lead" onerror="this.src='https://via.placeholder.com/180x180?text=Tech'">
                    <h3>Akshaya Sabu</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.5s;">
                    <img src="uploads/team/customer.jpg" alt="Customer Service" onerror="this.src='https://via.placeholder.com/180x180?text=Service'">
                    <h3>Jesson Joju</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.6s;">
                    <img src="uploads/team/sales.jpg" alt="Sales Director" onerror="this.src='https://via.placeholder.com/180x180?text=Sales'">
                    <h3>Adersh V K</h3>
                    <p>Member</p>
                </div>
                <div class="team-member fade-in-up" style="animation-delay: 0.7s;">
                    <img src="uploads/team/quality.jpg" alt="Quality Control" onerror="this.src='https://via.placeholder.com/180x180?text=Quality'">
                    <h3>Fathima Shibu</h3>
                    <p>Member</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column fade-in-up">
                    <h3>KickStep</h3>
                    <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/" class="floating"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://x.com/" class="floating" style="animation-delay: 0.1s;"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/" class="floating" style="animation-delay: 0.2s;"><i class="fab fa-instagram"></i></a>
                        <a href="https://in.pinterest.com/" class="floating" style="animation-delay: 0.3s;"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.1s;">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="home.php">Home</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.2s;">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="contact.php">FAQs</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                        <li><a href="#">Returns & Exchanges</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.3s;">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Shoe Street, Fashion City</li>
                        <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                        <li><i class="fas fa-envelope"></i> info@kickstep.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright fade-in-up" style="animation-delay: 0.4s;">
                <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Optional: Add header scroll effect if you want it on about page too
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    });
    </script>
</body>
</html> 