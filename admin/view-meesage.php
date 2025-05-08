<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

// Check if message ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: messages.php');
    exit;
}

$message_id = intval($_GET['id']);
$is_sent = isset($_GET['sent']) && $_GET['sent'] == 1;

// Get message details
if($is_sent) {
    // Get sent message
    $query = "SELECT m.*, u.name as receiver_name, u.email as receiver_email FROM messages m 
              JOIN users u ON m.receiver_id = u.id 
              WHERE m.id = ? AND m.sender_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $message_id, $user_id);
} else {
    // Get received message
    $query = "SELECT m.*, u.name as sender_name, u.email as sender_email FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE m.id = ? AND m.receiver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $message_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Check if message exists
if($result->num_rows === 0) {
    header('Location: messages.php');
    exit;
}

$message = $result->fetch_assoc();

// Mark message as read if it's a received message
if(!$is_sent && !$message['is_read']) {
    $query = "UPDATE messages SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $message_id);
    $stmt->execute();
}

// Process reply form
if($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_sent) {
    $reply_message = trim($_POST['reply_message']);
    
    // Validate input
    if(empty($reply_message)) {
        $error = 'Reply message is required';
    } else {
        // Insert reply
        $subject = 'RE: ' . $message['subject'];
        $receiver_id = $message['sender_id'];
        
        $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, created_at, is_read) 
                  VALUES (?, ?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iiss', $user_id, $receiver_id, $subject, $reply_message);
        
        if($stmt->execute()) {
            header('Location: messages.php');
            exit;
        } else {
            $error = 'Failed to send reply';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="message-view-section">
                <div class="message-actions">
                    <a href="messages.php" class="btn">Back to Messages</a>
                </div>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="message-container">
                    <div class="message-header">
                        <h2><?php echo htmlspecialchars($message['subject']); ?></h2>
                        <div class="message-meta">
                            <?php if($is_sent): ?>
                                <p><strong>To:</strong> <?php echo htmlspecialchars($message['receiver_name']); ?> (<?php echo htmlspecialchars($message['receiver_email']); ?>)</p>
                            <?php else: ?>
                                <p><strong>From:</strong> <?php echo htmlspecialchars($message['sender_name']); ?> (<?php echo htmlspecialchars($message['sender_email']); ?>)</p>
                            <?php endif; ?>
                            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($message['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                    
                    <?php if(!$is_sent): ?>
                        <div class="message-reply">
                            <h3>Reply</h3>
                            <form action="view-message.php?id=<?php echo $message_id; ?>" method="post">
                                <div class="form-group">
                                    <textarea name="reply_message" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn">Send Reply</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Church Management System. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
