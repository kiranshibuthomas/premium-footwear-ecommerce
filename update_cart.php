<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$product_id = $_POST['product_id'];
$quantity = $_POST['quantity'];
$user_id = $_SESSION['user_id'];

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

// Check if product exists and has enough stock
$check_product = "SELECT stock FROM products WHERE id = ?";
$stmt = $conn->prepare($check_product);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $result->fetch_assoc();
if ($quantity > $product['stock']) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
    exit();
}

// Update cart quantity
$update_cart = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($update_cart);
$stmt->bind_param("iii", $quantity, $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?> 