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

// Get current settings
$query = "SELECT * FROM settings WHERE id = 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

// If settings don't exist, create default
if(!$settings) {
    $query = "INSERT INTO settings (id, church_name, church_address, church_phone, church_email, bank_name, bank_account_name, bank_account_number) 
              VALUES (1, 'Church Management System', '123 Church Street, City, Country', '(123) 456-7890', 'info@churchsystem.com', 'First Bank', 'Church Management System', '1234567890')";
    $conn->query($query);
    
    $query = "SELECT * FROM settings WHERE id = 1";
    $result = $conn->query($query);
    $settings = $result->fetch_assoc();
}

// Process settings form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $church_name = trim($_POST['church_name']);
    $church_address = trim($_POST['church_address']);
    $church_phone = trim($_POST['church_phone']);
    $church_email = trim($_POST['church_email']);
    $bank_name = trim($_POST['bank_name']);
    $bank_account_name = trim($_POST['bank_account_name']);
    $bank_account_number = trim($_POST['bank_account_number']);
    
    // Validate input
    if(empty($church_name) || empty($church_address) || empty($church_phone) || empty($church_email)) {
        $error = 'Church details are required';
    } elseif(empty($bank_name) || empty($bank_account_name) || empty($bank_account_number)) {
        $error = 'Bank details are required';
    } else {
        // Update settings
        $query = "UPDATE settings SET 
                  church_name = ?, 
                  church_address = ?, 
                  church_phone = ?, 
                  church_email = ?, 
                  bank_name = ?, 
                  bank_account_name = ?, 
                  bank_account_number = ? 
                  WHERE id = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssss', $church_name, $church_address, $church_phone, $church_email, $bank_name, $bank_account_name, $bank_account_number);
        
        if($stmt->execute()) {
            $success = 'Settings updated successfully';
            
            // Refresh settings
            $query = "SELECT * FROM settings WHERE id = 1";
            $result = $conn->query($query);
            $settings = $result->fetch_assoc();
        } else {
            $error = 'Failed to update settings';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="settings-section">
                <h2>Site Settings</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="settings.php" method="post" class="settings-form">
                    <div class="settings-card">
                        <h3>Church Information</h3>
                        
                        <div class="form-group">
                            <label for="church_name">Church Name</label>
                            <input type="text" id="church_name" name="church_name" value="<?php echo htmlspecialchars($settings['church_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="church_address">Church Address</label>
                            <textarea id="church_address" name="church_address" rows="3" required><?php echo htmlspecialchars($settings['church_address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="church_phone">Church Phone</label>
                            <input type="text" id="church_phone" name="church_phone" value="<?php echo htmlspecialchars($settings['church_phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="church_email">Church Email</label>
                            <input type="email" id="church_email" name="church_email" value="<?php echo htmlspecialchars($settings['church_email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Bank Information</h3>
                        <p class="form-note">These details will be shown to members when making donations</p>
                        
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bank_account_name">Account Name</label>
                            <input type="text" id="bank_account_name" name="bank_account_name" value="<?php echo htmlspecialchars($settings['bank_account_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bank_account_number">Account Number</label>
                            <input type="text" id="bank_account_number" name="bank_account_number" value="<?php echo htmlspecialchars($settings['bank_account_number']); ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Save Settings</button>
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
