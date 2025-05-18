<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
if (!isAdmin()) {
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Get total revenue
$result = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status != 'cancelled'");
$revenue = $result->fetch_assoc()['total_revenue'] ?? 0;

// Get total orders
$result = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $result->fetch_assoc()['total_orders'];

// Get total products
$result = $conn->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $result->fetch_assoc()['total_products'];

// Get low stock products (less than 10 items)
$result = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE stock < 10");
$low_stock = $result->fetch_assoc()['low_stock'];

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.email as user_email,
           COUNT(oi.id) as total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

// Get top selling products
$top_products = $conn->query("
    SELECT p.*, 
           COUNT(oi.id) as times_ordered,
           SUM(oi.quantity) as total_quantity
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <a href="dashboard.php" class="admin-nav-link active">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manageproduct.php" class="admin-nav-link">
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
                    <li class="admin-nav-item" style="margin-top: auto;">
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
                <h1 class="admin-title">Dashboard</h1>
            </header>

            <!-- Stats Cards -->
            <div class="admin-cards">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Total Revenue</h3>
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="admin-card-value" id="revenue-value">$<?php echo number_format($revenue, 2); ?></div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Total Orders</h3>
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="admin-card-value" id="orders-value"><?php echo number_format($total_orders); ?></div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Total Products</h3>
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="admin-card-value" id="products-value"><?php echo number_format($total_products); ?></div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Low Stock Alert</h3>
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="admin-card-value" id="low-stock-value"><?php echo number_format($low_stock); ?></div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <h2 class="admin-section-title">Recent Orders</h2>
                    <a href="orders.php" class="admin-btn admin-btn-secondary">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                <td><?php echo $order['total_items']; ?> items</td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'pending' => 'warning',
                                        'processing' => 'primary',
                                        'shipped' => 'info',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $status_class = $status_classes[$order['status']] ?? 'secondary';
                                    ?>
                                    <span class="admin-badge admin-badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <h2 class="admin-section-title">Top Selling Products</h2>
                    <a href="manageproduct.php" class="admin-btn admin-btn-secondary">
                        Manage Products
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Orders</th>
                                <th>Quantity Sold</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="../<?php echo $product['image_url']; ?>" 
                                             alt="<?php echo $product['name']; ?>"
                                             class="product-thumbnail">
                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo number_format($product['times_ordered']); ?></td>
                                <td><?php echo number_format($product['total_quantity']); ?></td>
                                <td>
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="admin-badge admin-badge-success"><?php echo $product['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Function to update dashboard stats
        function updateDashboardStats() {
            fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update revenue
                    document.getElementById('revenue-value').textContent = '$' + parseFloat(data.revenue).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    // Update orders
                    document.getElementById('orders-value').textContent = parseInt(data.total_orders).toLocaleString();

                    // Update products
                    document.getElementById('products-value').textContent = parseInt(data.total_products).toLocaleString();

                    // Update low stock
                    document.getElementById('low-stock-value').textContent = parseInt(data.low_stock).toLocaleString();
                })
                .catch(error => console.error('Error updating stats:', error));
        }

        // Update stats every 30 seconds
        setInterval(updateDashboardStats, 30000);

        // Add subtle animation when values change
        const observeValue = (element) => {
            let oldValue = element.textContent;
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'characterData' || mutation.type === 'childList') {
                        const newValue = element.textContent;
                        if (newValue !== oldValue) {
                            element.style.animation = 'none';
                            element.offsetHeight; // Trigger reflow
                            element.style.animation = 'valueChange 0.5s ease-in-out';
                            oldValue = newValue;
                        }
                    }
                });
            });
            observer.observe(element, { characterData: true, childList: true, subtree: true });
        };

        // Observe all stat values for changes
        ['revenue-value', 'orders-value', 'products-value', 'low-stock-value'].forEach(id => {
            observeValue(document.getElementById(id));
        });
    </script>

    <style>
        @keyframes valueChange {
            0% {
                transform: scale(1);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .admin-card-value {
            color: #e53e3e !important; /* Red color for numbers */
            font-weight: bold;
        }
    </style>
</body>
</html>