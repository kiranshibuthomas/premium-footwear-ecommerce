<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user information
$user_query = "SELECT name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch cart items with product details
$cart_query = "SELECT ci.*, p.name, p.price, p.image_path, p.stock 
               FROM cart_items ci 
               JOIN products p ON ci.product_id = p.id 
               WHERE ci.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = [];
$total = 0;

while ($item = $cart_result->fetch_assoc()) {
    $item['subtotal'] = $item['quantity'] * $item['price'];
    $total += $item['subtotal'];
    $cart_items[] = $item;
}

// Get cart count for header
$count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$count_result = $stmt->get_result();
$cart_count = $count_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - KickStep</title>
    <link rel="stylesheet" href="css/dark-theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .cart-section {
            padding: 80px 0;
            min-height: calc(100vh - 400px);
        }

        .cart-container {
            background-color: var(--darker);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cart-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            gap: 20px;
            margin-bottom: 20px;
        }

        .cart-header span {
            color: var(--gray);
            font-weight: 500;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .product-image {
            width: 100px;
            height: 100px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details h3 {
            color: var(--light);
            margin-bottom: 5px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background-color: var(--primary-dark);
            transform: scale(1.1);
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            background-color: var(--dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light);
            padding: 5px;
            border-radius: 5px;
        }

        .remove-btn {
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            color: var(--primary-dark);
            transform: scale(1.1);
        }

        .cart-summary {
            margin-top: 40px;
            text-align: right;
        }

        .cart-total {
            font-size: 24px;
            color: var(--light);
            margin-bottom: 20px;
        }

        .cart-total span {
            color: var(--primary);
            font-weight: 700;
        }

        .checkout-btn {
            display: inline-block;
            padding: 15px 40px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(229, 62, 62, 0.2);
        }

        .shipping-form {
            margin: 20px 0;
            text-align: left;
        }

        .shipping-form h3 {
            color: var(--light);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .shipping-address {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background-color: var(--dark);
            color: var(--light);
            font-size: 14px;
            resize: vertical;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .shipping-address:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(229, 62, 62, 0.1);
        }

        .empty-cart {
            text-align: center;
            padding: 60px 0;
        }

        .empty-cart i {
            font-size: 60px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            color: var(--light);
            margin-bottom: 20px;
        }

        .empty-cart p {
            color: var(--gray);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .cart-header {
                display: none;
            }

            .cart-item {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 20px;
                background-color: var(--dark);
                border-radius: 10px;
                margin-bottom: 10px;
            }

            .product-info {
                flex-direction: column;
                text-align: center;
            }

            .quantity-controls {
                justify-content: center;
            }

            .remove-btn {
                width: 100%;
                padding: 10px;
                background-color: rgba(229, 62, 62, 0.1);
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <a href="home.php" class="logo">
                    <i class="fas fa-shoe-prints"></i>
                    KickStep
                </a>
                <div class="nav-links">
                    <a href="home.php">Home</a>
                    <a href="products.php">Shop</a>
                    <a href="about.php">About</a>
                    <a href="#">Contact</a>
                </div>
                <div class="nav-icons">
                    <a href="#"><i class="fas fa-search"></i></a>
                    <a href="cart.php" class="active">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge"><?php echo $cart_count; ?></span>
                    </a>
                    <div class="user-dropdown">
                        <a href="#"><i class="fas fa-user"></i></a>
                        <div class="dropdown-menu">
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <ul>
                                <li>
                                    <a href="profile.php">
                                        <i class="fas fa-user-circle"></i>
                                        My Profile
                                    </a>
                                </li>
                                <li>
                                    <a href="orders.php">
                                        <i class="fas fa-shopping-bag"></i>
                                        My Orders
                                    </a>
                                </li>
                                <li>
                                    <a href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <?php if (empty($cart_items)): ?>
            <div class="cart-container">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your Cart is Empty</h2>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="home.php" class="btn">Continue Shopping</a>
                </div>
            </div>
            <?php else: ?>
            <div class="cart-container">
                <div class="cart-header">
                    <span>Product</span>
                    <span>Price</span>
                    <span>Quantity</span>
                    <span>Total</span>
                    <span></span>
                </div>
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                    <div class="product-info">
                        <div class="product-image">
                            <img src="<?php echo $item['image_path'] ? 'uploads/products/' . htmlspecialchars($item['image_path']) : 'https://via.placeholder.com/100x100?text=Product+Image'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="product-category">Premium Footwear</p>
                        </div>
                    </div>
                    <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                    <div class="quantity-controls">
                        <button class="quantity-btn decrease" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                        <button class="quantity-btn increase" <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="subtotal">$<?php echo number_format($item['subtotal'], 2); ?></div>
                    <button class="remove-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
                
                <div class="cart-summary">
                    <div class="cart-total">
                        Total: <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="shipping-form">
                        <h3>Shipping Address</h3>
                        <textarea id="shipping-address" class="shipping-address" rows="3" placeholder="Enter your shipping address" required></textarea>
                    </div>
                    <button class="checkout-btn" onclick="processCheckout()">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>KickStep</h3>
                    <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="#">New Arrivals</a></li>
                        <li><a href="#">Best Sellers</a></li>
                        <li><a href="#">On Sale</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Stores</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> KickStep. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartItems = document.querySelectorAll('.cart-item');
        
        cartItems.forEach(item => {
            const productId = item.dataset.productId;
            const quantityInput = item.querySelector('.quantity-input');
            const decreaseBtn = item.querySelector('.decrease');
            const increaseBtn = item.querySelector('.increase');
            const removeBtn = item.querySelector('.remove-btn');
            const subtotalElement = item.querySelector('.subtotal');
            const priceElement = item.querySelector('.price');
            const price = parseFloat(priceElement.textContent.replace('$', ''));
            
            // Update quantity
            function updateQuantity(newQuantity) {
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${newQuantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        quantityInput.value = newQuantity;
                        subtotalElement.textContent = `$${(price * newQuantity).toFixed(2)}`;
                        updateTotal();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            
            // Decrease quantity
            decreaseBtn.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value);
                if (currentQuantity > 1) {
                    updateQuantity(currentQuantity - 1);
                }
            });
            
            // Increase quantity
            increaseBtn.addEventListener('click', () => {
                const currentQuantity = parseInt(quantityInput.value);
                const maxStock = parseInt(quantityInput.getAttribute('max'));
                if (currentQuantity < maxStock) {
                    updateQuantity(currentQuantity + 1);
                }
            });
            
            // Manual quantity input
            quantityInput.addEventListener('change', () => {
                let newQuantity = parseInt(quantityInput.value);
                const maxStock = parseInt(quantityInput.getAttribute('max'));
                
                if (newQuantity < 1) newQuantity = 1;
                if (newQuantity > maxStock) newQuantity = maxStock;
                
                updateQuantity(newQuantity);
            });
            
            // Remove item
            removeBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to remove this item?')) {
                    fetch('remove_from_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            item.remove();
                            updateCartCount(data.cart_count);
                            updateTotal();
                            
                            // If cart is empty, reload page to show empty cart message
                            if (data.cart_count === 0) {
                                window.location.reload();
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
        
        // Update total price
        function updateTotal() {
            const subtotals = document.querySelectorAll('.subtotal');
            let total = 0;
            
            subtotals.forEach(subtotal => {
                total += parseFloat(subtotal.textContent.replace('$', ''));
            });
            
            const totalElement = document.querySelector('.cart-total span');
            totalElement.textContent = `$${total.toFixed(2)}`;
        }
        
        // Update cart count in header
        function updateCartCount(count) {
            const badge = document.querySelector('.badge');
            badge.textContent = count;
            
            // Animate the badge
            badge.style.transform = 'scale(1.5)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 300);
        }
    });

    function processCheckout() {
        const shippingAddress = document.getElementById('shipping-address').value.trim();
        
        if (!shippingAddress) {
            alert('Please enter your shipping address');
            return;
        }
        
        fetch('process_checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `shipping_address=${encodeURIComponent(shippingAddress)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = 'orders.php';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your order');
        });
    }
    </script>
</body>
</html> 