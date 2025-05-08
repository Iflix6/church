<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$error = '';
$success = '';

// Process attendance form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $attendance_date = $_POST['attendance_date'];
    $members = isset($_POST['members']) ? $_POST['members'] : [];
    
    // Validate input
    if(empty($event_id) || empty($attendance_date)) {
        $error = 'Event and date are required';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if attendance record already exists for this event and date
            $query = "SELECT id FROM attendance WHERE event_id = ? AND attendance_date = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('is', $event_id, $attendance_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                // Delete existing attendance records
                $attendance_id = $result->fetch_assoc()['id'];
                
                $query = "DELETE FROM attendance_members WHERE attendance_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $attendance_id);
                $stmt->execute();
            } else {
                // Insert new attendance record
                $query = "INSERT INTO attendance (event_id, attendance_date, created_at) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('is', $event_id, $attendance_date);
                $stmt->execute();
                $attendance_id = $conn->insert_id;
            }
            
            // Insert attendance members
            if(!empty($members)) {
                $query = "INSERT INTO attendance_members (attendance_id, user_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                
                foreach($members as $member_id) {
                    $stmt->bind_param('ii', $attendance_id, $member_id);
                    $stmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            $success = 'Attendance recorded successfully';
        } catch(Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Failed to record attendance: ' . $e->getMessage();
        }
    }
}

// Get events for dropdown
$query = "SELECT id, title FROM events ORDER BY title ASC";
$events = $conn->query($query);

// Get members for checklist
$query = "SELECT id, name FROM users WHERE role = 'member' ORDER BY name ASC";
$members = $conn->query($query);

// Get recent attendance records
$query = "SELECT a.id, a.attendance_date, e.title as event_title, 
          (SELECT COUNT(*) FROM attendance_members WHERE attendance_id = a.id) as member_count 
          FROM attendance a 
          JOIN events e ON a.event_id = e.id 
          ORDER BY a.attendance_date DESC LIMIT 10";
$recent_attendance = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Attendance - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="attendance-section">
                <h2>Track Attendance</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="attendance-form-container">
                    <h3>Record Attendance</h3>
                    <form action="attendance.php" method="post" class="attendance-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_id">Event</label>
                                <select id="event_id" name="event_id" required>
                                    <option value="">Select Event</option>
                                    <?php
                                    while($event = $events->fetch_assoc()) {
                                        echo '<option value="' . $event['id'] . '">' . htmlspecialchars($event['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="attendance_date">Date</label>
                                <input type="date" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Members Present</label>
                            <div class="search-box">
                                <input type="text" id="memberSearch" placeholder="Search members...">
                            </div>
                            
                            <div class="members-checklist">
                                <?php
                                while($member = $members->fetch_assoc()) {
                                    echo '<div class="member-checkbox">';
                                    echo '<input type="checkbox" id="member_' . $member['id'] . '" name="members[]" value="' . $member['id'] . '">';
                                    echo '<label for="member_' . $member['id'] . '">' . htmlspecialchars($member['name']) . '</label>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="checkbox-actions">
                                <button type="button" id="selectAll" class="btn-small">Select All</button>
                                <button type="button" id="deselectAll" class="btn-small">Deselect All</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Record Attendance</button>
                    </form>
                </div>
                
                <div class="recent-attendance">
                    <h3>Recent Attendance Records</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Members Present</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($recent_attendance->num_rows > 0) {
                                    while($attendance = $recent_attendance->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($attendance['event_title']) . '</td>';
                                        echo '<td>' . date('M j, Y', strtotime($attendance['attendance_date'])) . '</td>';
                                        echo '<td>' . $attendance['member_count'] . ' members</td>';
                                        echo '<td class="action-buttons">';
                                        echo '<a href="view-attendance.php?id=' . $attendance['id'] . '" class="btn-small">View Details</a>';
                                        echo '<a href="edit-attendance.php?id=' . $attendance['id'] . '" class="btn-small">Edit</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4">No attendance records found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="attendance-report.php" class="btn">View Full Attendance Report</a>
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
            const memberSearch = document.getElementById('memberSearch');
            const memberCheckboxes = document.querySelectorAll('.member-checkbox input[type="checkbox"]');
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            
            // Search functionality
            memberSearch.addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                
                document.querySelectorAll('.member-checkbox').forEach(function(item) {
                    const label = item.querySelector('label').textContent.toLowerCase();
                    
                    if(label.includes(searchText)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // Select all functionality
            selectAllBtn.addEventListener('click', function() {
                memberCheckboxes.forEach(function(checkbox) {
                    if(checkbox.parentElement.style.display !== 'none') {
                        checkbox.checked = true;
                    }
                });
            });
            
            // Deselect all functionality
            deselectAllBtn.addEventListener('click', function() {
                memberCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            });
        });
    </script>
</body>
</html>
