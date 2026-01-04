<?php
/**
 * Reply to Feedback/Query
 * Luviora Hotel Management System - Admin Dashboard
 */

require_once 'auth_check.php';
require_once '../config/database.php';
require_once '../config/email.php';

$db = getDB();
$error = '';
$success = '';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: feedback.php');
    exit;
}

// Get feedback/query details
$stmt = $db->prepare("SELECT * FROM feedback_queries WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: feedback.php');
    exit;
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $adminReply = trim($_POST['admin_reply'] ?? '');
        $status = $_POST['status'] ?? 'in_progress';

        if (empty($adminReply)) {
            $error = 'Reply message is required.';
        } else {
            // Get admin ID from session
            $adminId = $_SESSION['admin_id'] ?? null;

            // Update feedback_queries with reply
            $stmt = $db->prepare("
                UPDATE feedback_queries
                SET admin_reply = ?, admin_reply_by = ?, admin_reply_at = NOW(), status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $adminReply,
                $adminId,
                $status,
                $id
            ]);

            // Send email to the user
            $emailSent = sendReplyEmail($item, $adminReply);

            if ($emailSent) {
                $success = 'Reply sent successfully and email delivered to ' . htmlspecialchars($item['email']) . '!';
            } else {
                $success = 'Reply saved successfully, but email could not be sent. Please contact the user manually.';
            }

            // Refresh item data
            $stmt = $db->prepare("SELECT * FROM feedback_queries WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
        }
    } catch (PDOException $e) {
        error_log("Reply Error: " . $e->getMessage());
        $error = 'An error occurred while sending the reply.';
    }
}

$pageTitle = "Reply to " . ($item['type'] === 'feedback' ? 'Feedback' : 'Query');
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
                    <h1><i class="fas fa-reply"></i> Reply to <?php echo ucfirst($item['type']); ?></h1>
                    <a href="feedback.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <!-- Original Message -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-envelope"></i> Original Message</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>From:</strong></label>
                                    <p><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Email:</strong></label>
                                    <p><a href="mailto:<?php echo htmlspecialchars($item['email']); ?>"><?php echo htmlspecialchars($item['email']); ?></a></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Type:</strong></label>
                                    <p><span class="badge bg-<?php echo $item['type'] === 'feedback' ? 'info' : 'warning'; ?>"><?php echo ucfirst($item['type']); ?></span></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Priority:</strong></label>
                                    <p><span class="badge bg-<?php echo $item['priority'] === 'urgent' ? 'danger' : ($item['priority'] === 'high' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($item['priority']); ?></span></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Status:</strong></label>
                                    <p><span class="badge bg-<?php echo $item['status'] === 'new' ? 'danger' : ($item['status'] === 'resolved' ? 'success' : 'primary'); ?>"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Subject:</strong></label>
                                    <p><?php echo htmlspecialchars($item['subject'] ?? 'N/A'); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Category:</strong></label>
                                    <p><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['category']))); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Date:</strong></label>
                                    <p><?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Message:</strong></label>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($item['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reply Form -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-pen"></i> Send Reply</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Update Status</label>
                                        <select name="status" id="status" class="form-select" required>
                                            <option value="read" <?php echo $item['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                            <option value="in_progress" <?php echo $item['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $item['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            <option value="closed" <?php echo $item['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_reply" class="form-label">Your Reply <span class="text-danger">*</span></label>
                                        <textarea name="admin_reply" id="admin_reply" class="form-control" rows="8" placeholder="Type your reply here..." required><?php echo htmlspecialchars($item['admin_reply'] ?? ''); ?></textarea>
                                        <small class="text-muted">This reply will be sent to: <?php echo htmlspecialchars($item['email']); ?></small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Reply
                                        </button>
                                        <a href="feedback.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                                
                                <?php if ($item['admin_reply']): ?>
                                <div class="mt-4 p-3 bg-success bg-opacity-10 rounded">
                                    <h6 class="text-success mb-2"><i class="fas fa-check-circle"></i> Reply Sent</h6>
                                    <p class="mb-0 small">Sent on: <?php echo date('M d, Y H:i', strtotime($item['admin_reply_at'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Send reply email to the user
 * @param array $item Feedback/query item
 * @param string $adminReply Admin's reply message
 * @return bool Success status
 */
function sendReplyEmail($item, $adminReply) {
    $recipientEmail = $item['email'];
    $recipientName = $item['first_name'] . ' ' . $item['last_name'];
    $type = ucfirst($item['type']); // 'Feedback' or 'Query'
    $subject = "Re: Your {$type} - " . ($item['subject'] ?: 'Luviora Hotel');

    // Create HTML email body
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #8B5E3C 0%, #C38370 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
            .original-message { background: #f5f5f5; padding: 15px; border-left: 4px solid #8B5E3C; margin: 20px 0; }
            .reply-box { background: #fff9f0; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #C38370; }
            .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #8B5E3C; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🏨 Luviora Hotel</h1>
                <p style="margin: 10px 0 0 0;">Response to Your ' . $type . '</p>
            </div>

            <div class="content">
                <p>Dear ' . htmlspecialchars($recipientName) . ',</p>

                <p>Thank you for contacting Luviora Hotel. We have reviewed your ' . strtolower($type) . ' and are pleased to provide you with the following response:</p>

                <div class="reply-box">
                    <h3 style="color: #8B5E3C; margin-top: 0;">📧 Our Response:</h3>
                    <p style="white-space: pre-wrap;">' . nl2br(htmlspecialchars($adminReply)) . '</p>
                </div>

                <div class="original-message">
                    <h4 style="margin-top: 0; color: #666;">Your Original Message:</h4>
                    <p><strong>Subject:</strong> ' . htmlspecialchars($item['subject'] ?: 'No subject') . '</p>
                    <p><strong>Category:</strong> ' . htmlspecialchars(ucwords(str_replace('_', ' ', $item['category']))) . '</p>
                    <p><strong>Message:</strong></p>
                    <p style="white-space: pre-wrap;">' . nl2br(htmlspecialchars($item['message'])) . '</p>
                    <p style="font-size: 12px; color: #999; margin-top: 10px;">Submitted on: ' . date('F d, Y \a\t h:i A', strtotime($item['created_at'])) . '</p>
                </div>

                <p>If you have any further questions or concerns, please don\'t hesitate to contact us.</p>

                <p style="margin-top: 30px;">
                    <strong>Best regards,</strong><br>
                    The Luviora Hotel Team<br>
                    <a href="mailto:' . FROM_EMAIL . '">' . FROM_EMAIL . '</a>
                </p>
            </div>

            <div class="footer">
                <p><strong>Luviora Hotel</strong></p>
                <p>123 Luxury Avenue, Paradise City | Phone: +1 (555) 123-4567</p>
                <p>Email: ' . FROM_EMAIL . ' | Website: www.luviorahotel.com</p>
                <p style="margin-top: 15px; font-size: 11px; color: #999;">
                    This email was sent in response to your ' . strtolower($type) . ' submitted on ' . date('M d, Y', strtotime($item['created_at'])) . '.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';

    // Create plain text version
    $textBody = "Dear {$recipientName},\n\n";
    $textBody .= "Thank you for contacting Luviora Hotel.\n\n";
    $textBody .= "OUR RESPONSE:\n";
    $textBody .= str_repeat('-', 50) . "\n";
    $textBody .= $adminReply . "\n";
    $textBody .= str_repeat('-', 50) . "\n\n";
    $textBody .= "YOUR ORIGINAL MESSAGE:\n";
    $textBody .= "Subject: " . ($item['subject'] ?: 'No subject') . "\n";
    $textBody .= "Category: " . ucwords(str_replace('_', ' ', $item['category'])) . "\n";
    $textBody .= "Message: " . $item['message'] . "\n";
    $textBody .= "Submitted: " . date('F d, Y \a\t h:i A', strtotime($item['created_at'])) . "\n\n";
    $textBody .= "Best regards,\n";
    $textBody .= "The Luviora Hotel Team\n";
    $textBody .= FROM_EMAIL;

    // Try to send email using the configured email function
    try {
        $emailSent = sendEmail($recipientEmail, $recipientName, $subject, $htmlBody);
        return $emailSent;
    } catch (Exception $e) {
        error_log("Failed to send reply email: " . $e->getMessage());
        return false;
    }
}
?>
