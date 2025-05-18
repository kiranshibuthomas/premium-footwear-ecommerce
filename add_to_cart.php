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

// Check if product exists and is in stock
$check_product = "SELECT id, stock FROM products WHERE id = ? AND stock > 0";
$stmt = $conn->prepare($check_product);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not available']);
    exit();
}

// Check if product is already in cart
$check_cart = "SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($check_cart);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity
    $update_cart = "UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($update_cart);
    $stmt->bind_param("ii", $user_id, $product_id);
} else {
    // Add new item
    $add_to_cart = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($add_to_cart);
    $stmt->bind_param("ii", $user_id, $product_id);
}

if ($stmt->execute()) {
    // Get updated cart count
    $count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $cart_count = $count_result->fetch_assoc()['count'];
    
    echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cart_count' => $cart_count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
}
?> 