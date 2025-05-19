<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    <link rel="stylesheet" href="css/dark-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .about-hero {
            position: relative;
            min-height: 60vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, 
                        rgba(0, 0, 0, 0.9) 0%,
                        rgba(0, 0, 0, 0.8) 50%,
                        rgba(76, 175, 80, 0.2) 100%),
                        repeating-linear-gradient(45deg,
                        rgba(255, 255, 255, 0.03) 0px,
                        rgba(255, 255, 255, 0.03) 1px,
                        transparent 1px,
                        transparent 10px);
            margin-bottom: 4rem;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center,
                        rgba(76, 175, 80, 0.1) 0%,
                        transparent 70%);
            pointer-events: none;
        }

        .about-hero-content {
            position: relative;
            text-align: center;
            color: #fff;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            z-index: 1;
        }

        .about-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .about-hero p {
            font-size: 1.2rem;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
        }

        .about-section {
            padding: 4rem 0;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .about-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .about-card:hover {
            transform: translateY(-5px);
        }

        .about-card i {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }

        .about-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .team-section {
            padding: 4rem 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }

        .team-member img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
            border: 3px solid #4CAF50;
            transition: border-color 0.3s ease;
        }

        .team-member:hover img {
            border-color: #66bb6a;
        }

        .team-member h3 {
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .team-member p {
            color: #aaa;
            font-style: italic;
        }

        .values-section {
            padding: 4rem 0;
        }

        .value-item {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .value-item i {
            font-size: 2rem;
            color: #4CAF50;
            margin-right: 1.5rem;
        }

        .value-content h3 {
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .value-content p {
            color: #aaa;
        }
    </style>
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
                    <a href="home.php">Home</a>
                    <a href="products.php">Shop</a>
                    <a href="about.php" class="active">About</a>
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
                                <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
            <div class="about-hero-content">
                <h1>Our Story</h1>
                <p>Step into the world of KickStep, where passion meets innovation in footwear fashion.</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-title">
                <h2>Who We Are</h2>
                <p>Discover the journey behind KickStep's success</p>
            </div>
            <div class="about-grid">
                <div class="about-card">
                    <i class="fas fa-history"></i>
                    <h3>Our History</h3>
                    <p>Founded in 2020, KickStep began with a simple mission: to provide premium footwear that combines style, comfort, and quality. What started as a small online store has grown into a beloved destination for shoe enthusiasts worldwide.</p>
                </div>
                <div class="about-card">
                    <i class="fas fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>We strive to revolutionize the footwear industry by offering innovative designs, exceptional quality, and outstanding customer service. Every step we take is guided by our commitment to excellence.</p>
                </div>
                <div class="about-card">
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
            <div class="section-title">
                <h2>Our Core Values</h2>
                <p>The principles that guide everything we do</p>
            </div>
            <div class="value-item">
                <i class="fas fa-star"></i>
                <div class="value-content">
                    <h3>Quality First</h3>
                    <p>We never compromise on the quality of our products, ensuring that every pair of shoes meets our high standards.</p>
                </div>
            </div>
            <div class="value-item">
                <i class="fas fa-heart"></i>
                <div class="value-content">
                    <h3>Customer Satisfaction</h3>
                    <p>Your happiness is our priority. We go above and beyond to ensure you have the best shopping experience.</p>
                </div>
            </div>
            <div class="value-item">
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
            <div class="section-title">
                <h2>Meet Our Team</h2>
                <p>The passionate individuals behind KickStep's success</p>
            </div>
            <div class="team-grid">
                <div class="team-member">
                    <img src="uploads/team/ceo.jpg" alt="CEO" onerror="this.src='https://via.placeholder.com/180x180?text=CEO'">
                    <h3>James Varghese</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/designer.jpg" alt="Designer" onerror="this.src='https://via.placeholder.com/180x180?text=Designer'">
                    <h3>Jojo Manuel P</h3>
                    <p>Leader</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/manager.jpg" alt="Manager" onerror="this.src='https://via.placeholder.com/180x180?text=Manager'">
                    <h3>Kiran Shibu Thomas</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/marketing.jpg" alt="Marketing Director" onerror="this.src='https://via.placeholder.com/180x180?text=Marketing'">
                    <h3>Ashwin Sabu</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/tech.jpg" alt="Tech Lead" onerror="this.src='https://via.placeholder.com/180x180?text=Tech'">
                    <h3>Akshaya Sabu</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/customer.jpg" alt="Customer Service" onerror="this.src='https://via.placeholder.com/180x180?text=Service'">
                    <h3>Jesson Joju</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
                    <img src="uploads/team/sales.jpg" alt="Sales Director" onerror="this.src='https://via.placeholder.com/180x180?text=Sales'">
                    <h3>Adersh V K</h3>
                    <p>Member</p>
                </div>
                <div class="team-member">
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
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="home.php">Home</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                        <li><a href="#">Returns & Exchanges</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Shoe Street, Fashion City</li>
                        <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                        <li><i class="fas fa-envelope"></i> info@kickstep.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script/animations.js"></script>
</body>
</html> 