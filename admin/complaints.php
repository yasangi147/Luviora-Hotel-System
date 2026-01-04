<?php
require_once 'auth_check.php';
require_once '../config/database.php';
$db = getDB();
$items = $db->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Complaints | Luviora Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/admin-style.css"></head>
<body><?php include 'includes/sidebar.php'; ?>
<div class="main-content"><?php include 'includes/header.php'; ?>
<div class="content-wrapper"><div class="container-fluid">
<div class="page-header"><h1><i class="fas fa-exclamation-circle"></i> Complaints</h1>
<a href="feedback.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a></div>
<div class="data-table-card"><div class="table-header"><h3>All Records (<?php echo count($items); ?>)</h3></div>
<div class="table-responsive"><table class="table">
<thead><tr><th>Guest</th><th>Issue</th><th>Status</th></tr></thead>
<tbody>
<?php if (empty($items)): ?>
<tr><td colspan="10" style="text-align: center; padding: 40px;">No records found</td></tr>
<?php else: ?>
<?php foreach ($items as $item): ?>
<tr><?php foreach ($item as $key => $value): if (!is_numeric($key)): ?>
<td><?php echo htmlspecialchars($value ?? 'N/A'); ?></td>
<?php endif; endforeach; ?></tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody></table></div></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>