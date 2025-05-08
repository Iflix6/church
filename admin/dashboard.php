<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get counts for dashboard
// Total members
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'member'";
$result = $conn->query($query);
$member_count = $result->fetch_assoc()['total'];

// Total donations
$query = "SELECT SUM(amount) as total FROM donations WHERE status = 'approved'";
$result = $conn->query($query);
$donation_total = $result->fetch_assoc()['total'] ?: 0;

// Pending donations
$query = "SELECT COUNT(*) as total FROM donations WHERE status = 'pending'";
$result = $conn->query($query);
$pending_donations = $result->fetch_assoc()['total'];

// Total events
$query = "SELECT COUNT(*) as total FROM events";
$result = $conn->query($query);
$event_count = $result->fetch_assoc()['total'];

// Unread messages
$query = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_messages = $result->fetch_assoc()['total'];

// Recent members
$query = "SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);
$recent_members = $result;

// Recent donations
$query = "SELECT d.*, u.name FROM donations d 
          JOIN users u ON d.user_id = u.id 
          ORDER BY d.donation_date DESC LIMIT 5";
$result = $conn->query($query);
$recent_donations = $result;

// Upcoming events
$query = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5";
$result = $conn->query($query);
$upcoming_events = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="dashboard-welcome">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p>This is your admin dashboard where you can manage members, events, donations, and more.</p>
            </section>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Members</h3>
                        <p class="stat-value"><?php echo $member_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Total Donations</h3>
                        <p class="stat-value">₦<?php echo number_format($donation_total, 2); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Pending Donations</h3>
                        <p class="stat-value"><?php echo $pending_donations; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3>Total Events</h3>
                        <p class="stat-value"><?php echo $event_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✉️</div>
                    <div class="stat-info">
                        <h3>Unread Messages</h3>
                        <p class="stat-value"><?php echo $unread_messages; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <h3>Recent Members</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($recent_members->num_rows > 0) {
                                    while($member = $recent_members->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($member['name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($member['email']) . '</td>';
                                        echo '<td>' . htmlspecialchars($member['phone']) . '</td>';
                                        echo '<td>' . date('M j, Y', strtotime($member['created_at'])) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4">No members found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="members.php" class="btn">View All Members</a>
                </section>
                
                <section class="dashboard-card">
                    <h3>Recent Donations</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Purpose</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($recent_donations->num_rows > 0) {
                                    while($donation = $recent_donations->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($donation['name']) . '</td>';
                                        echo '<td>₦' . number_format($donation['amount'], 2) . '</td>';
                                        echo '<td>' . htmlspecialchars($donation['purpose']) . '</td>';
                                        echo '<td>' . date('M j, Y', strtotime($donation['donation_date'])) . '</td>';
                                        echo '<td><span class="status-badge status-' . $donation['status'] . '">' . ucfirst($donation['status']) . '</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No donations found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="donations.php" class="btn">View All Donations</a>
                </section>
                
                <section class="dashboard-card">
                    <h3>Upcoming Events</h3>
                    <div class="events-list">
                        <?php
                        if($upcoming_events->num_rows > 0) {
                            while($event = $upcoming_events->fetch_assoc()) {
                                echo '<div class="event-item">';
                                echo '<h4>' . htmlspecialchars($event['title']) . '</h4>';
                                echo '<p class="event-date">' . date('F j, Y', strtotime($event['event_date'])) . '</p>';
                                echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No upcoming events found.</p>';
                        }
                        ?>
                    </div>
                    <a href="events.php" class="btn">Manage Events</a>
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
