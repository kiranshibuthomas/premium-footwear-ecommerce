<?php
require_once 'db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get current user's name or email
 */
function getCurrentUserName() {
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    } elseif (isset($_SESSION['email'])) {
        return $_SESSION['email'];
    }
    return 'Guest';
}

/**
 * Require login to access page
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = "Please login to access this page.";
        header("Location: " . SITE_URL . "/index.php");
        exit();
    }
}

/**
 * Require admin access to page
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/admin/dashboard.php');
        exit();
    }
}

// Function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, email, name, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to update cart count in session
function updateCartCount() {
    if (!isLoggedIn()) {
        $_SESSION['cart_count'] = 0;
        return;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $_SESSION['cart_count'] = $row['total'] ?? 0;
}

// Update cart count on every page load
updateCartCount();

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Set user name in session if logged in but name not set
if (isLoggedIn() && !isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = getCurrentUserName();
} 