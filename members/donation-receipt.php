<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if donation ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: donations.php');
    exit;
}

$donation_id = intval($_GET['id']);

// Get donation details
$query = "SELECT d.*, u.name, u.email, u.phone FROM donations d 
          JOIN users u ON d.user_id = u.id 
          WHERE d.id = ? AND d.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $donation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if donation exists
if($result->num_rows === 0) {
    header('Location: donations.php');
    exit;
}

$donation = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Receipt - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="receipt-section">
                <div class="receipt-actions">
                    <button onclick="window.print()" class="btn">Print Receipt</button>
                    <a href="donations.php" class="btn">Back to Donations</a>
                </div>
                
                <div class="receipt-container">
                    <div class="receipt-header">
                        <h2>Donation Receipt</h2>
                        <p class="receipt-id">Receipt #: <?php echo str_pad($donation['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    
                    <div class="receipt-body">
                        <div class="receipt-info">
                            <div class="receipt-row">
                                <span class="receipt-label">Donor Name:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['name']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Email:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['email']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Phone:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['phone']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Date:</span>
                                <span class="receipt-value"><?php echo date('F j, Y', strtotime($donation['donation_date'])); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Amount:</span>
                                <span class="receipt-value">₦<?php echo number_format($donation['amount'], 2); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Purpose:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['purpose']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Payment Method:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['payment_method']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Reference:</span>
                                <span class="receipt-value"><?php echo htmlspecialchars($donation['reference']); ?></span>
                            </div>
                            
                            <div class="receipt-row">
                                <span class="receipt-label">Status:</span>
                                <span class="receipt-value status-badge status-<?php echo $donation['status']; ?>"><?php echo ucfirst($donation['status']); ?></span>
                            </div>
                        </div>
                        
                        <?php if($donation['status'] === 'approved'): ?>
                            <div class="receipt-thank-you">
                                <p>Thank you for your generous donation!</p>
                                <p>Your contribution helps us continue our mission and serve our community.</p>
                            </div>
                        <?php elseif($donation['status'] === 'pending'): ?>
                            <div class="receipt-pending">
                                <p>Your donation is currently pending verification.</p>
                                <p>Once approved, this receipt will be updated.</p>
                            </div>
                        <?php else: ?>
                            <div class="receipt-rejected">
                                <p>Your donation was not approved.</p>
                                <p>Please contact the church office for more information.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="receipt-footer">
                        <p>Church Management System</p>
                        <p>123 Church Street, City, Country</p>
                        <p>Phone: (123) 456-7890 | Email: info@churchsystem.com</p>
                    </div>
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
