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

// Get user information
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Process profile update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if(empty($name) || empty($email) || empty($phone)) {
        $error = 'Name, email, and phone are required fields';
    } else {
        // Check if email already exists (for another user)
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Update profile information
            $query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssssi', $name, $email, $phone, $address, $user_id);
            
            if($stmt->execute()) {
                // Update session variables
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                
                $success = 'Profile updated successfully';
                
                // Update password if provided
                if(!empty($current_password) && !empty($new_password)) {
                    if($new_password !== $confirm_password) {
                        $error = 'New passwords do not match';
                    } elseif(strlen($new_password) < 6) {
                        $error = 'Password must be at least 6 characters long';
                    } else {
                        // Verify current password
                        if(password_verify($current_password, $user['password'])) {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            // Update password
                            $query = "UPDATE users SET password = ? WHERE id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param('si', $hashed_password, $user_id);
                            
                            if($stmt->execute()) {
                                $success = 'Profile and password updated successfully';
                            } else {
                                $error = 'Failed to update password';
                            }
                        } else {
                            $error = 'Current password is incorrect';
                        }
                    }
                }
                
                // Refresh user data
                $query = "SELECT * FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = 'Failed to update profile';
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
    <title>My Profile - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="profile-section">
                <h2>My Profile</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="profile.php" method="post" class="profile-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <h3>Change Password</h3>
                    <p class="form-note">Leave blank if you don't want to change your password</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Church Management System. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
