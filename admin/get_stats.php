<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
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

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'revenue' => $revenue,
    'total_orders' => $total_orders,
    'total_products' => $total_products,
    'low_stock' => $low_stock
]); 