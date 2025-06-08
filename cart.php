<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header id="header">
        <div class="container">
            <nav>
                <a href="home.php" class="logo shine">
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
                                <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> <span>My Orders</span></a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
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
                <div class="footer-column fade-in-up">
                    <h3>KickStep</h3>
                    <p>Premium footwear for every occasion. Step into comfort and style with our quality shoes.</p>
                    <div class="social-links">
                        <a href="#" class="floating"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="floating" style="animation-delay: 0.1s;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="floating" style="animation-delay: 0.2s;"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="floating" style="animation-delay: 0.3s;"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.1s;">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="home.php">Home</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.2s;">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                        <li><a href="#">Returns & Exchanges</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column fade-in-up" style="animation-delay: 0.3s;">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Shoe Street, Fashion City</li>
                        <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                        <li><i class="fas fa-envelope"></i> info@kickstep.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright fade-in-up" style="animation-delay: 0.4s;">
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

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

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