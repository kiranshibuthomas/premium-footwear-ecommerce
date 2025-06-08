<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
if (!isAdmin()) {
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($status !== 'all') {
    $where_conditions[] = "m.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR m.subject LIKE ? OR m.message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM messages m
    JOIN users u ON m.user_id = u.id
    $where_clause
";
$stmt = $conn->prepare($count_query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_messages = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $per_page);

// Get messages
$query = "
    SELECT m.*, u.name as user_name, u.email as user_email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    $where_clause
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($query);
$types .= 'ii';
$params[] = $per_page;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get message counts by status
$status_counts = [
    'all' => $total_messages,
    'unread' => 0,
    'read' => 0
];

$count_query = "SELECT status, COUNT(*) as count FROM messages WHERE status IN ('unread', 'read') GROUP BY status";
$result = $conn->query($count_query);
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .messages-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .messages-filters {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .status-filter {
            display: flex;
            gap: 0.5rem;
        }

        .status-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 20px;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-btn:hover {
            background: #e9ecef;
        }

        .status-btn.active {
            background: var(--primary-color);
            color: #fff;
        }

        .status-btn .count {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
        }

        .search-form input {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 250px;
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .messages-table {
            width: 100%;
            border-collapse: collapse;
        }

        .messages-table th,
        .messages-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .messages-table th {
            font-weight: 600;
            color: #666;
            background: #f8f9fa;
        }

        .messages-table tr:hover {
            background: #f8f9fa;
        }

        .message-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-unread { background: #fff3cd; color: #856404; }
        .status-read { background: #cce5ff; color: #004085; }
        .status-replied { background: #d4edda; color: #155724; }

        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination a.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .no-messages {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-messages i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
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
                <h1 class="admin-title">Messages</h1>
            </header>

            <div class="messages-container">
                <div class="messages-header">
                    <div class="messages-filters">
                        <div class="status-filter">
                            <a href="?status=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="status-btn <?php echo $status === 'all' ? 'active' : ''; ?>">
                                All
                                <span class="count"><?php echo $status_counts['all']; ?></span>
                            </a>
                            <a href="?status=unread<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="status-btn <?php echo $status === 'unread' ? 'active' : ''; ?>">
                                Unread
                                <span class="count"><?php echo $status_counts['unread']; ?></span>
                            </a>
                            <a href="?status=read<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="status-btn <?php echo $status === 'read' ? 'active' : ''; ?>">
                                Read
                                <span class="count"><?php echo $status_counts['read']; ?></span>
                            </a>
                        </div>
                        <form class="search-form" method="GET">
                            <?php if ($status !== 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Search messages..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="admin-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <p>No messages found.</p>
                </div>
                <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($message['user_name']); ?></div>
                                <div style="font-size: 0.875rem; color: #666;">
                                    <?php echo htmlspecialchars($message['user_email']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_classes = [
                                    'unread' => 'warning',
                                    'read' => 'info'
                                ];
                                $status_class = $status_classes[$message['status']] ?? 'secondary';
                                ?>
                                <span class="admin-badge admin-badge-<?php echo $status_class; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?></td>
                            <td>
                                <a href="view_message.php?id=<?php echo $message['id']; ?>" 
                                   class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $status !== 'all' ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $status !== 'all' ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $status !== 'all' ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 