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

// Process event deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    
    // Check if event exists
    $query = "SELECT id FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        // Delete event
        $query = "DELETE FROM events WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $event_id);
        
        if($stmt->execute()) {
            $success = 'Event deleted successfully';
        } else {
            $error = 'Failed to delete event';
        }
    } else {
        $error = 'Event not found';
    }
}

// Process event form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    
    // Validate input
    if(empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($location)) {
        $error = 'All fields are required';
    } else {
        // Combine date and time
        $event_datetime = $event_date . ' ' . $event_time . ':00';
        
        // Insert event
        $query = "INSERT INTO events (title, description, event_date, location, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssss', $title, $description, $event_datetime, $location);
        
        if($stmt->execute()) {
            $success = 'Event added successfully';
        } else {
            $error = 'Failed to add event';
        }
    }
}

// Get all events
$query = "SELECT * FROM events ORDER BY event_date DESC";
$result = $conn->query($query);
$events = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="events-section">
                <h2>Manage Events</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="add-event-form">
                    <h3>Add New Event</h3>
                    <form action="events.php" method="post">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Event Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_date">Event Date</label>
                                <input type="date" id="event_date" name="event_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_time">Event Time</label>
                                <input type="time" id="event_time" name="event_time" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Add Event</button>
                    </form>
                </div>
                
                <h3>All Events</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($events->num_rows > 0) {
                                while($event = $events->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($event['title']) . '</td>';
                                    echo '<td>' . date('M j, Y g:i A', strtotime($event['event_date'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($event['location']) . '</td>';
                                    echo '<td>' . htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : '') . '</td>';
                                    echo '<td class="action-buttons">';
                                    echo '<a href="edit-event.php?id=' . $event['id'] . '" class="btn-small">Edit</a>';
                                    echo '<a href="events.php?action=delete&id=' . $event['id'] . '" class="btn-small btn-danger" onclick="return confirm(\'Are you sure you want to delete this event?\')">Delete</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5">No events found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
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
