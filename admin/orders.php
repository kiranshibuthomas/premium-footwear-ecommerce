<?php
require_once '../includes/db.php';

// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['order_success_message'] = "Order status updated successfully.";
    } else {
        $error_message = "Failed to update order status. Please try again.";
    }
    
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

// Check if there was an error with the query
if (!$orders) {
    $error_message = "Error fetching orders: " . $conn->error;
}

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
                        <a href="manageproduct.php" class="admin-nav-link">
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
                    <li class="admin-nav-item">
                        <a href="messages.php" class="admin-nav-link">
                            <i class="fas fa-envelope"></i>
                            Messages
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
                <h1 class="admin-title">Manage Orders</h1>
            </header>

            <?php if (isset($error_message)): ?>
                <div class="admin-alert admin-alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['order_success_message'])): ?>
                <div class="admin-alert admin-alert-success">
                    <?php echo htmlspecialchars($_SESSION['order_success_message']); ?>
                    <?php unset($_SESSION['order_success_message']); ?>
                </div>
            <?php endif; ?>

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
            <span class="close" onclick="hideModal('orderModal')">&times;</span>
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
                    <label class="admin-form-label">Order Date</label>
                    <div id="order-date" class="admin-form-value"></div>
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

                <div class="admin-form-group">
                    <label class="admin-form-label">Current Status</label>
                    <div id="current-status" class="admin-form-value"></div>
                </div>

                <form action="orders.php" method="POST" class="admin-form-group">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="status-order-id">
                    
                    <label class="admin-form-label">Update Status</label>
                    <div class="status-update-container">
                        <select name="status" id="status-select" class="admin-form-input">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .admin-modal.show {
            opacity: 1;
        }

        .admin-modal-content {
            background: var(--dark);
            width: 90%;
            max-width: 700px;
            margin: 30px auto;
            padding: 2.5rem;
            border-radius: 12px;
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease;
            border: 1px solid var(--glass-border);
        }

        .admin-modal.show .admin-modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .admin-modal-content h2 {
            color: var(--light);
            font-size: 1.8rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light);
            opacity: 0.7;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .order-details {
            display: grid;
            gap: 1.8rem;
        }

        .admin-form-group {
            margin-bottom: 0;
        }

        .admin-form-label {
            color: var(--light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .admin-form-value {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem 1.2rem;
            border-radius: 8px;
            font-size: 1rem;
            color: var(--light);
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .admin-form-value:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }

        .status-update-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            padding: 1.2rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
        }

        .status-update-container select {
            flex: 1;
            background: var(--darker);
            color: var(--light);
            border: 1px solid var(--glass-border);
            padding: 0.8rem 1rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-update-container select:hover {
            border-color: var(--primary);
        }

        .status-update-container select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
        }

        .status-update-container button {
            white-space: nowrap;
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-update-container button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .status-update-container button:active {
            transform: translateY(0);
        }

        #current-status .admin-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        #current-status .admin-badge i {
            font-size: 0.8rem;
        }

        /* Custom scrollbar for modal */
        .admin-modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .admin-modal-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .admin-modal-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .admin-modal-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .admin-modal-content {
                margin: 15px auto;
                padding: 1.5rem;
            }

            .status-update-container {
                flex-direction: column;
            }
            
            .status-update-container button {
                width: 100%;
                justify-content: center;
            }

            .admin-form-value {
                padding: 0.8rem 1rem;
            }
        }
    </style>

    <script>
        function showOrderDetails(order) {
            const modal = document.getElementById('orderModal');
            modal.style.display = 'block';
            // Trigger reflow
            modal.offsetHeight;
            modal.classList.add('show');

            document.getElementById('order-id').textContent = '#' + String(order.id).padStart(5, '0');
            document.getElementById('order-email').textContent = order.user_email;
            document.getElementById('order-date').textContent = new Date(order.created_at).toLocaleString();
            document.getElementById('order-items').textContent = order.items;
            document.getElementById('order-amount').textContent = '$' + parseFloat(order.total_amount).toFixed(2);
            document.getElementById('order-address').textContent = order.shipping_address;
            document.getElementById('status-order-id').value = order.id;
            document.getElementById('current-status').innerHTML = `
                <span class="admin-badge admin-badge-${getStatusClass(order.status)}">
                    <i class="fas fa-${getStatusIcon(order.status)}"></i>
                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                </span>
            `;
            document.getElementById('status-select').value = order.status;
        }

        function getStatusClass(status) {
            const statusClasses = {
                'pending': 'warning',
                'processing': 'primary',
                'shipped': 'info',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            return statusClasses[status] || 'secondary';
        }

        function getStatusIcon(status) {
            const statusIcons = {
                'pending': 'clock',
                'processing': 'cog',
                'shipped': 'truck',
                'delivered': 'check-circle',
                'cancelled': 'times-circle'
            };
            return statusIcons[status] || 'circle';
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'admin-modal') {
                hideModal('orderModal');
            }
        }
    </script>
</body>
</html>