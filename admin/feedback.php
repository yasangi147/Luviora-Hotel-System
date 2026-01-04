<?php
/**
 * Feedback & Queries Management
 * Luviora Hotel Management System - Admin Dashboard
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get filter parameters
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$priorityFilter = $_GET['priority'] ?? 'all';

// Build query
$query = "
    SELECT fq.*, u.name as user_name, u.email as user_email
    FROM feedback_queries fq
    LEFT JOIN users u ON fq.user_id = u.user_id
    WHERE 1=1
";

$params = [];

if ($typeFilter !== 'all') {
    $query .= " AND fq.type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== 'all') {
    $query .= " AND fq.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter !== 'all') {
    $query .= " AND fq.priority = ?";
    $params[] = $priorityFilter;
}

if ($searchQuery) {
    $query .= " AND (fq.first_name LIKE ? OR fq.last_name LIKE ? OR fq.email LIKE ? OR fq.message LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY fq.priority DESC, fq.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$feedbackQueries = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries");
$totalCount = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries WHERE type = 'feedback'");
$totalFeedback = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries WHERE type = 'query'");
$totalQueries = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries WHERE status = 'new'");
$newCount = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries WHERE status = 'resolved'");
$resolvedCount = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM feedback_queries WHERE priority = 'urgent'");
$urgentCount = $stmt->fetch()['total'];

$pageTitle = "Feedback & Queries";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-comments"></i> Feedback & Queries</h1>
                        <p>Manage guest feedback and queries</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-content">
                                <h3><?php echo $totalCount; ?></h3>
                                <p>Total Messages</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-info">
                            <div class="stat-content">
                                <h3><?php echo $totalFeedback; ?></h3>
                                <p>Feedback</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-comment-dots"></i></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-content">
                                <h3><?php echo $totalQueries; ?></h3>
                                <p>Queries</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-danger">
                            <div class="stat-content">
                                <h3><?php echo $newCount; ?></h3>
                                <p>New Messages</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-bell"></i></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-success">
                            <div class="stat-content">
                                <h3><?php echo $resolvedCount; ?></h3>
                                <p>Resolved</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-card-danger">
                            <div class="stat-content">
                                <h3><?php echo $urgentCount; ?></h3>
                                <p>Urgent</p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3 align-items-center">
                                <div class="col-lg-3 col-md-6">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <select name="type" class="form-select">
                                        <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                                        <option value="feedback" <?php echo $typeFilter === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                                        <option value="query" <?php echo $typeFilter === 'query' ? 'selected' : ''; ?>>Query</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <select name="status" class="form-select">
                                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <select name="priority" class="form-select">
                                        <option value="all" <?php echo $priorityFilter === 'all' ? 'selected' : ''; ?>>All Priority</option>
                                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-12">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary flex-fill">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="feedback.php" class="btn btn-secondary flex-fill">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Messages Table -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>From</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbackQueries)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p class="mt-3 text-muted">No messages found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($feedbackQueries as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['type'] === 'feedback' ? 'info' : 'warning'; ?>">
                                            <?php echo ucfirst($item['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($item['subject'] ?? $item['message'], 0, 40)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['priority'] === 'urgent' ? 'danger' : ($item['priority'] === 'high' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($item['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['status'] === 'new' ? 'danger' : ($item['status'] === 'resolved' ? 'success' : 'primary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    <td>
                                        <a href="feedback-reply.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-reply"></i> Reply
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

