<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Check if attendance ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: attendance.php');
    exit;
}

$attendance_id = intval($_GET['id']);

// Get attendance details
$query = "SELECT a.*, e.title as event_title, e.event_date as event_date 
          FROM attendance a 
          JOIN events e ON a.event_id = e.id 
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $attendance_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if attendance exists
if($result->num_rows === 0) {
    header('Location: attendance.php');
    exit;
}

$attendance = $result->fetch_assoc();

// Get members who attended
$query = "SELECT u.id, u.name, u.email, u.phone 
          FROM attendance_members am 
          JOIN users u ON am.user_id = u.id 
          WHERE am.attendance_id = ? 
          ORDER BY u.name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $attendance_id);
$stmt->execute();
$present_members = $stmt->get_result();

// Get members who did not attend
$query = "SELECT u.id, u.name, u.email, u.phone 
          FROM users u 
          WHERE u.role = 'member' 
          AND u.id NOT IN (
              SELECT user_id FROM attendance_members WHERE attendance_id = ?
          ) 
          ORDER BY u.name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $attendance_id);
$stmt->execute();
$absent_members = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="attendance-view-section">
                <div class="section-header">
                    <h2>Attendance Details</h2>
                    <div class="action-buttons">
                        <a href="attendance.php" class="btn">Back to Attendance</a>
                        <a href="edit-attendance.php?id=<?php echo $attendance_id; ?>" class="btn">Edit Attendance</a>
                        <button onclick="window.print()" class="btn">Print Report</button>
                    </div>
                </div>
                
                <div class="attendance-details">
                    <div class="detail-card">
                        <h3>Event Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Event:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($attendance['event_title']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Event Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($attendance['event_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Attendance Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($attendance['attendance_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Recorded On:</span>
                            <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($attendance['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="attendance-summary">
                        <div class="summary-card">
                            <h3>Attendance Summary</h3>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Present:</span>
                                    <span class="stat-value"><?php echo $present_members->num_rows; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Absent:</span>
                                    <span class="stat-value"><?php echo $absent_members->num_rows; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Members:</span>
                                    <span class="stat-value"><?php echo $present_members->num_rows + $absent_members->num_rows; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Attendance Rate:</span>
                                    <span class="stat-value">
                                        <?php 
                                        $total = $present_members->num_rows + $absent_members->num_rows;
                                        echo $total > 0 ? round(($present_members->num_rows / $total) * 100, 1) . '%' : '0%'; 
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="attendance-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="present">Present Members</button>
                        <button class="tab-btn" data-tab="absent">Absent Members</button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-pane active" id="present">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($present_members->num_rows > 0) {
                                            while($member = $present_members->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($member['name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($member['email']) . '</td>';
                                                echo '<td>' . htmlspecialchars($member['phone']) . '</td>';
                                                echo '<td><a href="view-member.php?id=' . $member['id'] . '" class="btn-small">View Profile</a></td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="4">No members present.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane" id="absent">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($absent_members->num_rows > 0) {
                                            while($member = $absent_members->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($member['name']) . '</td>';
                                                echo '<td>' . htmlspecialchars($member['email']) . '</td>';
                                                echo '<td>' . htmlspecialchars($member['phone']) . '</td>';
                                                echo '<td><a href="view-member.php?id=' . $member['id'] . '" class="btn-small">View Profile</a></td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="4">No members absent.</td></tr>';
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
