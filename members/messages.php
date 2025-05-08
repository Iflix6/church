<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Process new message form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate input
    if(empty($subject) || empty($message)) {
        $error = 'Subject and message are required';
    } else {
        // Insert message
        $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, created_at, is_read) 
                  VALUES (?, ?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iiss', $user_id, $receiver_id, $subject, $message);
        
        if($stmt->execute()) {
            $success = 'Message sent successfully';
        } else {
            $error = 'Failed to send message';
        }
    }
}

// Get received messages
$query = "SELECT m.*, u.name as sender_name FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          WHERE m.receiver_id = ? 
          ORDER BY m.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$received_messages = $stmt->get_result();

// Get sent messages
$query = "SELECT m.*, u.name as receiver_name FROM messages m 
          JOIN users u ON m.receiver_id = u.id 
          WHERE m.sender_id = ? 
          ORDER BY m.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$sent_messages = $stmt->get_result();

// Get admin users for sending messages
$query = "SELECT id, name FROM users WHERE role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->execute();
$admins = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="messages-section">
                <h2>Messages</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="messages-container">
                    <div class="new-message">
                        <h3>Send New Message</h3>
                        <form action="messages.php" method="post" class="message-form">
                            <div class="form-group">
                                <label for="receiver_id">To</label>
                                <select id="receiver_id" name="receiver_id" required>
                                    <option value="">Select Recipient</option>
                                    <?php
                                    while($admin = $admins->fetch_assoc()) {
                                        echo '<option value="' . $admin['id'] . '">' . htmlspecialchars($admin['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn">Send Message</button>
                        </form>
                    </div>
                    
                    <div class="message-tabs">
                        <div class="tab-buttons">
                            <button class="tab-btn active" data-tab="inbox">Inbox</button>
                            <button class="tab-btn" data-tab="sent">Sent</button>
                        </div>
                        
                        <div class="tab-content">
                            <div class="tab-pane active" id="inbox">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>From</th>
                                                <th>Subject</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if($received_messages->num_rows > 0) {
                                                while($message = $received_messages->fetch_assoc()) {
                                                    echo '<tr class="' . ($message['is_read'] ? '' : 'unread-row') . '">';
                                                    echo '<td>' . htmlspecialchars($message['sender_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($message['subject']) . '</td>';
                                                    echo '<td>' . date('M j, Y g:i A', strtotime($message['created_at'])) . '</td>';
                                                    echo '<td>' . ($message['is_read'] ? 'Read' : 'Unread') . '</td>';
                                                    echo '<td><a href="view-message.php?id=' . $message['id'] . '" class="btn-small">View</a></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5">No messages found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="tab-pane" id="sent">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>To</th>
                                                <th>Subject</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if($sent_messages->num_rows > 0) {
                                                while($message = $sent_messages->fetch_assoc()) {
                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($message['receiver_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($message['subject']) . '</td>';
                                                    echo '<td>' . date('M j, Y g:i A', strtotime($message['created_at'])) . '</td>';
                                                    echo '<td>' . ($message['is_read'] ? 'Read' : 'Unread') . '</td>';
                                                    echo '<td><a href="view-message.php?id=' . $message['id'] . '&sent=1" class="btn-small">View</a></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5">No sent messages found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Church Management System. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
