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

// Fetch orders with items
$orders_query = "SELECT o.*, 
                COUNT(oi.id) as item_count,
                GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
                GROUP_CONCAT(p.id) as product_ids,
                GROUP_CONCAT(oi.quantity) as quantities
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
    <link rel="stylesheet" href="css/modern-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <header id="header">
        <nav>
            <a href="home.php" class="logo">
                <i class="fas fa-shoe-prints"></i>
                KickStep
            </a>
            <div class="nav-links">
                <a href="home.php">Home</a>
                <a href="products.php">Shop</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
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
                                    <span>My Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="orders.php" class="active">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>My Orders</span>
                                </a>
                            </li>
                            <li>
                                <a href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
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
                    <a href="products.php" class="btn">
                        <i class="fas fa-shopping-cart"></i>
                        Start Shopping
                    </a>
                </div>
                <?php else: ?>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h3>
                                <p class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-status">
                                <?php
                                $status_classes = [
                                    'pending' => 'status-pending',
                                    'processing' => 'status-processing',
                                    'shipped' => 'status-shipped',
                                    'delivered' => 'status-delivered',
                                    'cancelled' => 'status-cancelled'
                                ];
                                $status_class = $status_classes[$order['status']] ?? 'status-pending';
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                            <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                            
                            <div class="order-items">
                                <h4>Order Items:</h4>
                                <ul>
                                    <?php 
                                    $items = explode(', ', $order['items']);
                                    foreach ($items as $item) {
                                        echo "<li>" . htmlspecialchars($item) . "</li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>

                        <?php if ($order['status'] === 'pending'): ?>
                            <div class="order-actions">
                                <button class="cancel-order-btn" 
                                        data-order-id="<?php echo $order['id']; ?>"
                                        data-product-ids="<?php echo htmlspecialchars($order['product_ids']); ?>"
                                        data-quantities="<?php echo htmlspecialchars($order['quantities']); ?>">
                                    Cancel Order
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>KickStep</h3>
                <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                    <a href="https://in.pinterest.com/"><i class="fab fa-pinterest"></i></a>
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
                    <li><a href="orders.php">Shipping & Returns</a></li>
                  
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Company</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="about.php">Our Stores</a></li>
                  
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
        </div>
    </footer>

    <!-- Header Scroll Effect -->
    <script>
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Loading overlay
        window.addEventListener('load', function() {
            const loadingOverlay = document.querySelector('.loading-overlay');
            loadingOverlay.classList.add('hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Handle order cancellation
            document.querySelectorAll('.cancel-order-btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to cancel this order?')) {
                        const orderId = this.dataset.orderId;
                        
                        fetch('cancel_order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'order_id=' + orderId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Order cancelled successfully!');
                                location.reload();
                            } else {
                                alert(data.message || 'Failed to cancel order');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while cancelling the order');
                        });
                    }
                });
            });
        });
    </script>

    <style>
        .order-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
        }

        .order-details {
            display: grid;
            gap: 20px;
        }

        .order-items-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .order-items-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--glass-border);
        }

        .order-items-list li:last-child {
            border-bottom: none;
        }

        .order-info {
            display: grid;
            gap: 20px;
        }

        .order-total {
            font-size: 1.2rem;
            color: var(--accent);
            text-align: right;
        }

        .order-total strong {
            color: var(--text-light);
            margin-right: 10px;
        }

        .order-address {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-actions {
            margin-top: 15px;
            text-align: right;
        }

        .cancel-order-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .cancel-order-btn:hover {
            background: #c82333;
        }
    </style>
</body>
</html> 