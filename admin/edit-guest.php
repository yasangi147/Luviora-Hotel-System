<?php
require_once 'auth_check.php';
require_once '../config/database.php';
$db = getDB();
$userId = $_GET['id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE user_id = ?");
    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['status'], $userId]);
    header('Location: guest-details.php?id=' . $userId);
    exit;
}
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$guest = $stmt->fetch();
if (!$guest) { header('Location: guests.php'); exit; }
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Edit Guest | Luviora Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/admin-style.css"></head>
<body><?php include 'includes/sidebar.php'; ?>
<div class="main-content"><?php include 'includes/header.php'; ?>
<div class="content-wrapper"><div class="container-fluid">
<div class="page-header"><h1><i class="fas fa-edit"></i> Edit Guest</h1></div>
<div class="row"><div class="col-md-8">
<div class="data-table-card"><form method="POST">
<div class="mb-3"><label class="form-label">Name</label>
<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($guest['name']); ?>" required></div>
<div class="mb-3"><label class="form-label">Email</label>
<input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($guest['email']); ?>" required></div>
<div class="mb-3"><label class="form-label">Phone</label>
<input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($guest['phone'] ?? ''); ?>"></div>
<div class="mb-3"><label class="form-label">Status</label>
<select name="status" class="form-select" required>
<option value="active" <?php echo $guest['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
<option value="inactive" <?php echo $guest['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
<option value="suspended" <?php echo $guest['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
</select></div>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<a href="guest-details.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Cancel</a>
</form></div></div></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>