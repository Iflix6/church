<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../auth/login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get donation history
$query = "SELECT * FROM donations WHERE user_id = ? ORDER BY donation_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$donations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="dashboard-welcome">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p>This is your member dashboard where you can manage your profile, view events, make donations, and more.</p>
            </section>
            
            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <h3>Upcoming Events</h3>
                    <div class="events-list">
                        <?php
                        // Fetch upcoming events
                        $query = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $events = $stmt->get_result();
                        
                        if($events->num_rows > 0) {
                            while($event = $events->fetch_assoc()) {
                                echo '<div class="event-item">';
                                echo '<h4>' . htmlspecialchars($event['title']) . '</h4>';
                                echo '<p class="event-date">' . date('F j, Y', strtotime($event['event_date'])) . '</p>';
                                echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No upcoming events at this time.</p>';
                        }
                        ?>
                    </div>
                    <a href="events.php" class="btn">View All Events</a>
                </section>
                
                <section class="dashboard-card">
                    <h3>Recent Donations</h3>
                    <div class="donations-list">
                        <?php
                        if($donations->num_rows > 0) {
                            while($donation = $donations->fetch_assoc()) {
                                echo '<div class="donation-item">';
                                echo '<p class="donation-amount">₦' . number_format($donation['amount'], 2) . '</p>';
                                echo '<p class="donation-date">' . date('F j, Y', strtotime($donation['donation_date'])) . '</p>';
                                echo '<p class="donation-purpose">' . htmlspecialchars($donation['purpose']) . '</p>';
                                echo '<a href="donation-receipt.php?id=' . $donation['id'] . '" class="btn-small">View Receipt</a>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No donation history found.</p>';
                        }
                        ?>
                    </div>
                    <a href="donations.php" class="btn">Make a Donation</a>
                </section>
                
                <section class="dashboard-card">
                    <h3>Messages</h3>
                    <div class="messages-list">
                        <?php
                        // Fetch recent messages
                        $query = "SELECT m.*, u.name as sender_name FROM messages m 
                                  LEFT JOIN users u ON m.sender_id = u.id 
                                  WHERE m.receiver_id = ? ORDER BY m.created_at DESC LIMIT 5";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('i', $user_id);
                        $stmt->execute();
                        $messages = $stmt->get_result();
                        
                        if($messages->num_rows > 0) {
                            while($message = $messages->fetch_assoc()) {
                                echo '<div class="message-item ' . ($message['is_read'] ? 'read' : 'unread') . '">';
                                echo '<p class="message-sender">' . htmlspecialchars($message['sender_name']) . '</p>';
                                echo '<p class="message-date">' . date('M j, Y g:i A', strtotime($message['created_at'])) . '</p>';
                                echo '<p class="message-subject">' . htmlspecialchars($message['subject']) . '</p>';
                                echo '<a href="view-message.php?id=' . $message['id'] . '" class="btn-small">Read</a>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No messages found.</p>';
                        }
                        ?>
                    </div>
                    <a href="messages.php" class="btn">View All Messages</a>
                </section>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Church Management System. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
