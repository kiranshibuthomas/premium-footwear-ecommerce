<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
if (!isAdmin()) {
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Get message ID
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$message_id) {
    header('Location: dashboard.php');
    exit();
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $delete = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $delete->bind_param("i", $message_id);
    
    if ($delete->execute()) {
        $_SESSION['success_message'] = "Message deleted successfully.";
        header('Location: messages.php');
        exit();
    } else {
        $error_message = "Failed to delete message. Please try again.";
    }
}

// Get message details
$query = "
    SELECT m.*, u.name as user_name, u.email as user_email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();

if (!$message) {
    header('Location: dashboard.php');
    exit();
}

// Mark message as read if it's unread
if ($message['status'] === 'unread') {
    $update = $conn->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
    $update->bind_param("i", $message_id);
    $update->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .message-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .message-info h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .message-meta {
            color: #666;
            font-size: 0.9rem;
        }

        .message-meta span {
            margin-right: 1rem;
        }

        .message-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-unread { background: #fff3cd; color: #856404; }
        .status-read { background: #cce5ff; color: #004085; }

        .message-content {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .user-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .user-details h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .user-details p {
            margin: 0;
            color: #666;
        }

        .message-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .back-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <a href="dashboard.php" class="admin-logo">
                <i class="fas fa-shopping-bag"></i>
                <span>Admin Panel</span>
            </a>
            <nav class="admin-nav">
                <ul>
                    <li class="admin-nav-item">
                        <a href="dashboard.php" class="admin-nav-link">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manageproduct.php" class="admin-nav-link">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="orders.php" class="admin-nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="messages.php" class="admin-nav-link active">
                            <i class="fas fa-envelope"></i>
                            Messages
                        </a>
                    </li>
                    <li class="admin-nav-item" style="margin-top: auto;">
                        <a href="logout.php" class="admin-nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">View Message</h1>
                <a href="messages.php" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Messages
                </a>
            </header>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <div class="message-container">
                <div class="message-header">
                    <div class="message-info">
                        <h1><?php echo htmlspecialchars($message['subject']); ?></h1>
                        <div class="message-meta">
                            <span><i class="fas fa-user"></i> From: <?php echo htmlspecialchars($message['user_name']); ?></span>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($message['user_email']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?></span>
                        </div>
                    </div>
                    <span class="message-status status-<?php echo $message['status']; ?>">
                        <?php echo ucfirst($message['status']); ?>
                    </span>
                </div>

                <div class="user-details">
                    <h3>User Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($message['user_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($message['user_email']); ?></p>
                </div>

                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>

                <div class="message-actions">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                        <button type="submit" name="delete_message" class="delete-btn">
                            <i class="fas fa-trash"></i>
                            Delete Message
                        </button>
                    </form>
                    <a href="messages.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Messages
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 