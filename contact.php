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
    <title>Contact Us - KickStep</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/modern-theme.css" rel="stylesheet">
    <style>
        /* Contact Page Specific Styles */
        body {
            position: relative;
            background: #0a0a0a;
            color: #fff;
        }

        .floating-shoe-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .floating-shoe-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('uploads/ui/Spin.png') no-repeat center center;
            background-size: 60%;
            opacity: 0.08;
            animation: floatShoe 6s ease-in-out infinite;
        }

        .contact-hero {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.8) 100%);
            padding: 4rem 2rem;
            z-index: 1;
        }

        .contact-hero::before {
            display: none; /* Remove the old shoe background */
        }

        .contact-hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            color: #fff;
        }

        .contact-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textShine 3s ease-in-out infinite;
        }

        .contact-hero p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }

        .contact-section {
            padding: 4rem 2rem;
            background: rgba(20, 20, 20, 0.7);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            position: relative;
            z-index: 2;
        }

        .contact-info-card {
            background: rgba(30, 30, 30, 0.7);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .contact-info-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
            text-shadow: 0 0 10px rgba(var(--primary-rgb), 0.5);
        }

        .contact-info-card:hover i {
            transform: scale(1.1);
        }

        .contact-info-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .contact-info-card p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .contact-form-container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 2rem;
            background: rgba(30, 30, 30, 0.7);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-form {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            color: #fff;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
            outline: none;
            background: rgba(0, 0, 0, 0.4);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: var(--primary-color);
            color: #fff;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            color: #fff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            animation: fadeIn 0.3s ease;
            border: 1px solid rgba(76, 175, 80, 0.3);
            backdrop-filter: blur(5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 2.5rem;
            }

            .contact-container {
                grid-template-columns: 1fr;
            }

            .contact-form-container {
                padding: 1.5rem;
            }
        }

        /* Update other styles to ensure proper z-indexing */
        header {
            position: relative;
            z-index: 10;
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        footer {
            position: relative;
            z-index: 1;
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Add the floating shoe background to the body */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('uploads/ui/Spin.png') no-repeat center center;
            background-size: 60%;
            opacity: 0.05;
            animation: floatShoe 6s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes floatShoe {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        /* Update header styles for dark theme */
        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-color);
        }

        .dropdown-menu {
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-name {
            color: #fff;
        }

        .user-email {
            color: rgba(255, 255, 255, 0.7);
        }

        .dropdown-menu ul li a {
            color: rgba(255, 255, 255, 0.8);
        }

        .dropdown-menu ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--primary-color);
        }

        /* Loading overlay update */
        .loading-overlay {
            background: rgba(10, 10, 10, 0.9);
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(20, 20, 20, 0.9);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div id="particles"></div>
    <div class="floating-shoe-bg"></div>

    <!-- Header -->
    <header id="header">
        <nav>
            <a href="#" class="logo shine">
                <i class="fas fa-shoe-prints"></i>
                KickStep
            </a>
            <div class="nav-links">
                <a href="home.php">Home</a>
                <a href="products.php">Shop</a>
                <a href="about.php">About</a>
                <a href="contact.php" class="active">Contact</a>
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

    <!-- Contact Hero Section -->
    <section class="contact-hero">
        <div class="contact-hero-content fade-in-up">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you! Whether you have a question about our products, need assistance with an order, or just want to say hello, we're here to help.</p>
        </div>
    </section>

    <!-- Contact Info Cards -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-info-card fade-in-up" style="animation-delay: 0.1s">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Our Location</h3>
                <p>123 Fashion Street<br>New York, NY 10001<br>United States</p>
            </div>
            <div class="contact-info-card fade-in-up" style="animation-delay: 0.2s">
                <i class="fas fa-phone-alt"></i>
                <h3>Phone Number</h3>
                <p>+1 (555) 123-4567<br>Mon-Fri: 9:00 AM - 6:00 PM<br>Sat-Sun: 10:00 AM - 4:00 PM</p>
            </div>
            <div class="contact-info-card fade-in-up" style="animation-delay: 0.3s">
                <i class="fas fa-envelope"></i>
                <h3>Email Address</h3>
                <p>support@kickstep.com<br>sales@kickstep.com<br>info@kickstep.com</p>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="contact-form-container fade-in-up">
            <div class="success-message" id="successMessage" style="display: none;">
                Thank you for your message! We'll get back to you soon.
            </div>
            <form class="contact-form" id="contactForm">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
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

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('.submit-btn');
            const successMessage = document.getElementById('successMessage');
            const formData = new FormData(this);
            
            // Add loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Send the message
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
                    successMessage.style.display = 'block';
                    successMessage.textContent = data.message;
                    
                    // Reset form
                    this.reset();
                    
                    // Reset button after animation
                    setTimeout(() => {
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                        submitBtn.disabled = false;
                        successMessage.style.display = 'none';
                    }, 3000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                submitBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                successMessage.style.display = 'block';
                successMessage.style.background = 'rgba(220, 53, 69, 0.2)';
                successMessage.style.border = '1px solid rgba(220, 53, 69, 0.3)';
                successMessage.textContent = error.message || 'Failed to send message. Please try again.';
                
                // Reset button after animation
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                    submitBtn.disabled = false;
                    successMessage.style.display = 'none';
                    successMessage.style.background = '';
                    successMessage.style.border = '';
                }, 3000);
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
    </script>
</body>
</html> 