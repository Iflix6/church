<?php
session_start();
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Church Management System</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin/dashboard.php">Admin Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="members/dashboard.php">Member Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="hero">
                <h2>Welcome to Our Church Community</h2>
                <p>Join us in worship, fellowship, and service.</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="cta-buttons">
                        <a href="auth/login.php" class="btn">Login</a>
                        <a href="auth/register.php" class="btn">Register</a>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="upcoming-events">
                <h2>Upcoming Events</h2>
                <div class="events-container">
                    <?php
                    // Fetch upcoming events from database
                    $query = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if($result->num_rows > 0) {
                        while($event = $result->fetch_assoc()) {
                            echo '<div class="event-card">';
                            echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                            echo '<p class="event-date">' . date('F j, Y', strtotime($event['event_date'])) . '</p>';
                            echo '<p>' . htmlspecialchars($event['description']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No upcoming events at this time.</p>';
                    }
                    ?>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Church Management System. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>