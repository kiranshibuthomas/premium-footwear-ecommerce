<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$product_id = $_POST['product_id'];
$user_id = $_SESSION['user_id'];

// Remove item from cart
$remove_item = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($remove_item);
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    // Get updated cart count
    $count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $cart_count = $count_result->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Item removed from cart',
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
}
?> 