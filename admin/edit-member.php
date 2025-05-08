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
$error = '';
$success = '';

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

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_password = $_POST['new_password'];
    
    // Validate input
    if(empty($name) || empty($email) || empty($phone)) {
        $error = 'Name, email, and phone are required fields';
    } else {
        // Check if email already exists (for another user)
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $email, $member_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Update member information
            if(empty($new_password)) {
                // Update without changing password
                $query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssssi', $name, $email, $phone, $address, $member_id);
            } else {
                // Update with new password
                if(strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters long';
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('sssssi', $name, $email, $phone, $address, $hashed_password, $member_id);
                }
            }
            
            if(empty($error)) {
                if($stmt->execute()) {
                    $success = 'Member updated successfully';
                    
                    // Refresh member data
                    $query = "SELECT * FROM users WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('i', $member_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $member = $result->fetch_assoc();
                } else {
                    $error = 'Failed to update member';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="edit-member-section">
                <div class="section-header">
                    <h2>Edit Member</h2>
                    <div class="action-buttons">
                        <a href="members.php" class="btn">Back to Members</a>
                        <a href="view-member.php?id=<?php echo $member_id; ?>" class="btn">View Member</a>
                    </div>
                </div>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="edit-form-container">
                    <form action="edit-member.php?id=<?php echo $member_id; ?>" method="post" class="edit-form">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($member['address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                            <p class="form-note">Leave blank to keep current password</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="created_at">Member Since</label>
                            <input type="text" id="created_at" value="<?php echo date('F j, Y', strtotime($member['created_at'])); ?>" readonly>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn">Update Member</button>
                        </div>
                    </form>
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
