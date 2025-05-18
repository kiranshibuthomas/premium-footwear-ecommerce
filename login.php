<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
} elseif (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    if (empty($email) || empty($password)) {
        $error_message = "All fields are required";
    } else {
        if ($user_type === 'admin') {
            // Admin login
            $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($admin = $result->fetch_assoc()) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid credentials";
                }
            } else {
                $error_message = "Invalid credentials";
            }
        } else {
            // User login
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header("Location: home.php");
                    exit();
                } else {
                    $error_message = "Invalid credentials";
                }
            } else {
                $error_message = "Invalid credentials";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KickStep</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-shoe-prints"></i>
        </div>
        <h1>Welcome Back to KickStep</h1>
        
        <?php if (isset($_SESSION['logout_message'])): ?>
            <div class="success">
                <?php 
                echo htmlspecialchars($_SESSION['logout_message']);
                unset($_SESSION['logout_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="user-type-toggle">
                <input type="radio" id="user" name="user_type" value="user" checked>
                <label for="user">Customer</label>
                <input type="radio" id="admin" name="user_type" value="admin">
                <label for="admin">Administrator</label>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       placeholder="Enter your email"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password"
                       required>
            </div>

            <button type="submit" class="btn">
                <span>Login</span>
            </button>

            <div class="links">
                <a href="register.php">Create Account</a>
                <span class="divider">|</span>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </form>
    </div>
</body>
</html> 