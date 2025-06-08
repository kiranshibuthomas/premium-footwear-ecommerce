<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Check if order exists and belongs to user
    $check_order = "SELECT o.*, GROUP_CONCAT(oi.product_id) as product_ids, 
                           GROUP_CONCAT(oi.quantity) as quantities
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.id = ? AND o.user_id = ? AND o.status = 'pending'
                    GROUP BY o.id";
    $stmt = $conn->prepare($check_order);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Order not found or cannot be cancelled");
    }

    $order = $result->fetch_assoc();
    
    // Update order status to cancelled
    $update_order = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_order);
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to cancel order");
    }

    // Restore product stock
    $product_ids = explode(',', $order['product_ids']);
    $quantities = explode(',', $order['quantities']);
    
    for ($i = 0; $i < count($product_ids); $i++) {
        $update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("ii", $quantities[$i], $product_ids[$i]);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to restore product stock");
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 