<?php
require_once 'auth_check.php';
require_once '../config/database.php';
$db = getDB();

// Get all maintenance issues
try {
    $items = $db->query("
        SELECT mi.*,
               r.room_number, r.room_name, r.room_type,
               u1.name as reported_by_name,
               u2.name as assigned_to_name
        FROM maintenance_issues mi
        JOIN rooms r ON mi.room_id = r.room_id
        JOIN users u1 ON mi.reported_by = u1.user_id
        LEFT JOIN users u2 ON mi.assigned_to = u2.user_id
        ORDER BY
            FIELD(mi.priority, 'urgent', 'high', 'medium', 'low'),
            mi.reported_at DESC
    ")->fetchAll();

    $stats = [
        'total' => count($items),
        'urgent' => count(array_filter($items, fn($i) => $i['priority'] === 'urgent')),
        'in_progress' => count(array_filter($items, fn($i) => $i['status'] === 'in_progress')),
        'completed_today' => $db->query("SELECT COUNT(*) as cnt FROM maintenance_issues WHERE DATE(completed_at) = CURDATE()")->fetch()['cnt']
    ];
} catch (PDOException $e) {
    error_log("Maintenance Error: " . $e->getMessage());
    $items = [];
    $stats = ['total' => 0, 'urgent' => 0, 'in_progress' => 0, 'completed_today' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management | Luviora Admin</title>
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
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-tools"></i> Maintenance Management</h1>
                    </div>
                    <div style="margin-right: -480px;">
                        <a href="maintenance-create.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Create Issue</a>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-tools text-primary"></i>
                            <div>
                                <h4><?php echo $stats['total']; ?></h4>
                                <p>Total Issues</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                            <div>
                                <h4><?php echo $stats['urgent']; ?></h4>
                                <p>Urgent Issues</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-spinner text-warning"></i>
                            <div>
                                <h4><?php echo $stats['in_progress']; ?></h4>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <h4><?php echo $stats['completed_today']; ?></h4>
                                <p>Completed Today</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Issues Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> All Maintenance Issues (<?php echo count($items); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Issue ID</th>
                                    <th>Room</th>
                                    <th>Issue Title</th>
                                    <th>Priority</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Reported By</th>
                                    <th>Assigned To</th>
                                    <th>Reported At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-tools" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #666;">No maintenance issues found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong>#<?php echo $item['issue_id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['room_number']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($item['room_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['issue_title']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                            echo $item['priority'] === 'urgent' ? 'danger' :
                                                ($item['priority'] === 'high' ? 'warning' :
                                                ($item['priority'] === 'medium' ? 'info' : 'secondary'));
                                        ?>">
                                            <?php echo ucfirst($item['priority']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst($item['category']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                            echo $item['status'] === 'completed' ? 'success' :
                                                ($item['status'] === 'in_progress' ? 'primary' : 'secondary');
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['reported_by_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($item['reported_at'])); ?></td>
                                    <td>
                                        <a href="maintenance-edit.php?id=<?php echo $item['issue_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
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