<?php
require_once '../includes/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    
    header('Location: orders.php');
    exit();
}

// Fetch all orders with user information
$orders = $conn->query("
    SELECT o.*, u.email as user_email,
           COUNT(oi.id) as total_items,
           GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Orders</title>
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
                        <a href="dashboard.php" class="admin-nav-link">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manageproducts.php" class="admin-nav-link">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="orders.php" class="admin-nav-link active">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">Manage Orders</h1>
            </header>

            <!-- Orders Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                            <td>
                                <div class="order-items-tooltip">
                                    <?php echo $order['total_items']; ?> items
                                    <span class="tooltip-text">
                                        <?php echo htmlspecialchars($order['items']); ?>
                                    </span>
                                </div>
                            </td>
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
                            <td>
                                <button class="admin-btn admin-btn-secondary" onclick="showOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="admin-modal">
        <div class="admin-modal-content">
            <h2>Order Details</h2>
            <div class="order-details">
                <div class="admin-form-group">
                    <label class="admin-form-label">Order ID</label>
                    <div id="order-id" class="admin-form-value"></div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Customer Email</label>
                    <div id="order-email" class="admin-form-value"></div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Items</label>
                    <div id="order-items" class="admin-form-value"></div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Total Amount</label>
                    <div id="order-amount" class="admin-form-value"></div>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Shipping Address</label>
                    <div id="order-address" class="admin-form-value"></div>
                </div>

                <form action="orders.php" method="POST" class="admin-form-group">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="status-order-id">
                    
                    <label class="admin-form-label">Update Status</label>
                    <select name="status" class="admin-form-input" onchange="this.form.submit()">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </form>
            </div>

            <div class="admin-modal-actions">
                <button class="admin-btn admin-btn-secondary" onclick="hideModal('orderModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        function showOrderDetails(order) {
            document.getElementById('order-id').textContent = '#' + String(order.id).padStart(5, '0');
            document.getElementById('order-email').textContent = order.user_email;
            document.getElementById('order-items').textContent = order.items;
            document.getElementById('order-amount').textContent = '$' + parseFloat(order.total_amount).toFixed(2);
            document.getElementById('order-address').textContent = order.shipping_address;
            document.getElementById('status-order-id').value = order.id;
            
            const statusSelect = document.querySelector('select[name="status"]');
            statusSelect.value = order.status;
            
            document.getElementById('orderModal').style.display = 'flex';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('admin-modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>