<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if shipping address is provided
if (!isset($_POST['shipping_address']) || empty($_POST['shipping_address'])) {
    echo json_encode(['success' => false, 'message' => 'Shipping address is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$shipping_address = $_POST['shipping_address'];

// Start transaction
$conn->begin_transaction();

try {
    // Get cart items
    $cart_query = "SELECT ci.*, p.price, p.stock 
                   FROM cart_items ci 
                   JOIN products p ON ci.product_id = p.id 
                   WHERE ci.user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart is empty');
    }
    
    $total_amount = 0;
    $cart_items = [];
    
    // Calculate total and check stock
    while ($item = $cart_result->fetch_assoc()) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Not enough stock for some items");
        }
        $total_amount += $item['price'] * $item['quantity'];
        $cart_items[] = $item;
    }
    
    // Create order
    $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ids", $user_id, $total_amount, $shipping_address);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Add order items
    $insert_items = "INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
    $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
    
    $stmt_items = $conn->prepare($insert_items);
    $stmt_stock = $conn->prepare($update_stock);
    
    foreach ($cart_items as $item) {
        // Add to order_items
        $stmt_items->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt_items->execute();
        
        // Update stock
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }
    
    // Clear cart
    $clear_cart = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($clear_cart);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id
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