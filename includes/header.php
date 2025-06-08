<?php
require_once 'config.php';
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($title) ? $title : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
                    <?php echo SITE_NAME; ?>
                </a>
                
                <div class="nav-links">
                    <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
                    <a href="<?php echo SITE_URL; ?>/products.php">Products</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/">Admin</a>
                    <?php endif; ?>
                </div>
                
                <div class="nav-icons">
                    <?php if (isLoggedIn()): ?>
                        <div class="user-dropdown">
                            <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="badge"><?php echo $_SESSION['cart_count'] ?? 0; ?></span>
                            </a>
                            <span class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                            <div class="dropdown-content">
                                <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>
                                <a href="<?php echo SITE_URL; ?>/profile.php">Profile</a>
                                <a href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline">Login</a>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn">Register</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <main class="container">