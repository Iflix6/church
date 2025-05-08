<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../auth/login.php');
    exit;
}

// Get all events
$query = "SELECT * FROM events ORDER BY event_date ASC";
$result = $conn->query($query);
$events = $result;

// Get past events
$query = "SELECT * FROM events WHERE event_date < NOW() ORDER BY event_date DESC";
$result = $conn->query($query);
$past_events = $result;

// Get upcoming events
$query = "SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC";
$result = $conn->query($query);
$upcoming_events = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="events-section">
                <h2>Church Events</h2>
                
                <div class="event-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="upcoming">Upcoming Events</button>
                        <button class="tab-btn" data-tab="past">Past Events</button>
                        <button class="tab-btn" data-tab="all">All Events</button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-pane active" id="upcoming">
                            <div class="events-grid">
                                <?php
                                if($upcoming_events->num_rows > 0) {
                                    while($event = $upcoming_events->fetch_assoc()) {
                                        echo '<div class="event-card">';
                                        echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                                        echo '<p class="event-date">' . date('F j, Y - g:i A', strtotime($event['event_date'])) . '</p>';
                                        echo '<p class="event-location"><strong>Location:</strong> ' . htmlspecialchars($event['location']) . '</p>';
                                        echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No upcoming events at this time.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="tab-pane" id="past">
                            <div class="events-grid">
                                <?php
                                if($past_events->num_rows > 0) {
                                    while($event = $past_events->fetch_assoc()) {
                                        echo '<div class="event-card past-event">';
                                        echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                                        echo '<p class="event-date">' . date('F j, Y - g:i A', strtotime($event['event_date'])) . '</p>';
                                        echo '<p class="event-location"><strong>Location:</strong> ' . htmlspecialchars($event['location']) . '</p>';
                                        echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No past events found.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="tab-pane" id="all">
                            <div class="events-grid">
                                <?php
                                if($events->num_rows > 0) {
                                    while($event = $events->fetch_assoc()) {
                                        $isPast = strtotime($event['event_date']) < time();
                                        echo '<div class="event-card ' . ($isPast ? 'past-event' : '') . '">';
                                        echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                                        echo '<p class="event-date">' . date('F j, Y - g:i A', strtotime($event['event_date'])) . '</p>';
                                        echo '<p class="event-location"><strong>Location:</strong> ' . htmlspecialchars($event['location']) . '</p>';
                                        echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>No events found.</p>';
                                }
                                ?>
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
