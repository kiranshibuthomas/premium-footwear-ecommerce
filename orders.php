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

// Fetch orders with items
$orders_query = "SELECT o.*, 
                COUNT(oi.id) as item_count,
                GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders_result = $stmt->get_result();

// Get cart count for header
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
    <title>My Orders - KickStep</title>
    <link rel="stylesheet" href="css/dark-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .orders-section {
            padding: 80px 0;
            min-height: calc(100vh - 400px);
        }

        .orders-container {
            background-color: var(--darker);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .section-title {
            margin-bottom: 30px;
            text-align: center;
        }

        .section-title h2 {
            color: var(--light);
            font-size: 32px;
            margin-bottom: 10px;
        }

        .section-title p {
            color: var(--gray);
        }

        .order-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-id {
            color: var(--primary);
            font-weight: 600;
        }

        .order-date {
            color: var(--gray);
            font-size: 14px;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .status-processing { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .status-shipped { background-color: rgba(102, 16, 242, 0.1); color: #6610f2; }
        .status-delivered { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .status-cancelled { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }

        .order-items {
            color: var(--light);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .order-total {
            color: var(--primary);
            font-weight: 600;
            font-size: 18px;
            text-align: right;
        }

        .order-address {
            color: var(--gray);
            font-size: 14px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-orders {
            text-align: center;
            padding: 60px 0;
        }

        .empty-orders i {
            font-size: 60px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-orders h2 {
            color: var(--light);
            margin-bottom: 20px;
        }

        .empty-orders p {
            color: var(--gray);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                                    <a href="orders.php" class="active">
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

    <!-- Orders Section -->
    <section class="orders-section">
        <div class="container">
            <div class="section-title">
                <h2>My Orders</h2>
                <p>Track and manage your orders</p>
            </div>
            
            <div class="orders-container">
                <?php if ($orders_result->num_rows === 0): ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h2>No Orders Yet</h2>
                    <p>Looks like you haven't placed any orders yet.</p>
                    <a href="products.php" class="btn">Start Shopping</a>
                </div>
                <?php else: ?>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>
                        <div class="order-items">
                            <strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?>
                        </div>
                        <div class="order-total">
                            Total: $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <div class="order-address">
                            <strong>Shipping Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
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
</body>
</html> 