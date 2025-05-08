<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Check if member ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: members.php');
    exit;
}

$member_id = intval($_GET['id']);

// Get member information
$query = "SELECT * FROM users WHERE id = ? AND role = 'member'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $member_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if member exists
if($result->num_rows === 0) {
    header('Location: members.php');
    exit;
}

$member = $result->fetch_assoc();

// Get donation history
$query = "SELECT * FROM donations WHERE user_id = ? ORDER BY donation_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $member_id);
$stmt->execute();
$donations = $stmt->get_result();

// Get attendance history
$query = "SELECT a.attendance_date, e.title as event_title 
          FROM attendance_members am 
          JOIN attendance a ON am.attendance_id = a.id 
          JOIN events e ON a.event_id = e.id 
          WHERE am.user_id = ? 
          ORDER BY a.attendance_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $member_id);
$stmt->execute();
$attendance = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Member - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="view-member-section">
                <div class="section-header">
                    <h2>Member Profile</h2>
                    <div class="action-buttons">
                        <a href="members.php" class="btn">Back to Members</a>
                        <a href="edit-member.php?id=<?php echo $member_id; ?>" class="btn">Edit Member</a>
                        <a href="members.php?action=delete&id=<?php echo $member_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this member?')">Delete Member</a>
                    </div>
                </div>
                
                <div class="member-profile">
                    <div class="profile-card">
                        <h3>Personal Information</h3>
                        <div class="profile-info">
                            <div class="info-row">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['phone']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['address'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Since:</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($member['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="member-actions">
                        <a href="messages.php?new=1&to=<?php echo $member_id; ?>" class="btn">Send Message</a>
                    </div>
                </div>
                
                <div class="member-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="donations">Donation History</button>
                        <button class="tab-btn" data-tab="attendance">Attendance History</button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-pane active" id="donations">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Purpose</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($donations->num_rows > 0) {
                                            while($donation = $donations->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . date('M j, Y', strtotime($donation['donation_date'])) . '</td>';
                                                echo '<td>₦' . number_format($donation['amount'], 2) . '</td>';
                                                echo '<td>' . htmlspecialchars($donation['purpose']) . '</td>';
                                                echo '<td>' . htmlspecialchars($donation['reference']) . '</td>';
                                                echo '<td><span class="status-badge status-' . $donation['status'] . '">' . ucfirst($donation['status']) . '</span></td>';
                                                echo '<td><a href="view-donation.php?id=' . $donation['id'] . '" class="btn-small">View Details</a></td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="6">No donation history found.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane" id="attendance">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Event</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($attendance->num_rows > 0) {
                                            while($record = $attendance->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . date('M j, Y', strtotime($record['attendance_date'])) . '</td>';
                                                echo '<td>' . htmlspecialchars($record['event_title']) . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="2">No attendance records found.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
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
