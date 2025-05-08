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

// Process donation form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $purpose = trim($_POST['purpose']);
    $payment_method = trim($_POST['payment_method']);
    $reference = trim($_POST['reference']);
    
    // Check if file was uploaded
    $proof_file = '';
    if(isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = $_FILES['payment_proof']['type'];
        
        if(!in_array($file_type, $allowed_types)) {
            $error = 'Invalid file type. Only JPG, PNG, and PDF files are allowed.';
        } else {
            $file_name = time() . '_' . $_FILES['payment_proof']['name'];
            $upload_dir = '../uploads/payment_proofs/';
            
            // Create directory if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                $proof_file = $file_name;
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $error = 'Payment proof is required';
    }
    
    // Validate input
    if(empty($error)) {
        if($amount <= 0) {
            $error = 'Amount must be greater than zero';
        } elseif(empty($purpose)) {
            $error = 'Purpose is required';
        } elseif(empty($payment_method)) {
            $error = 'Payment method is required';
        } elseif(empty($reference)) {
            $error = 'Transaction reference is required';
        } else {
            // Insert donation record
            $query = "INSERT INTO donations (user_id, amount, purpose, payment_method, reference, payment_proof, donation_date, status) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('idssss', $user_id, $amount, $purpose, $payment_method, $reference, $proof_file);
            
            if($stmt->execute()) {
                $donation_id = $conn->insert_id;
                $success = 'Donation submitted successfully! Your donation will be verified by the admin.';
                
                // Redirect to receipt page
                header("Location: donation-receipt.php?id=$donation_id");
                exit;
            } else {
                $error = 'Failed to submit donation. Please try again.';
            }
        }
    }
}

// Get donation history
$query = "SELECT * FROM donations WHERE user_id = ? ORDER BY donation_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$donations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/member-header.php'; ?>
        
        <main>
            <section class="donations-section">
                <h2>Make a Donation</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="donation-container">
                    <form action="donations.php" method="post" enctype="multipart/form-data" class="donation-form">
                        <div class="form-group">
                            <label for="amount">Amount (₦)</label>
                            <input type="number" id="amount" name="amount" min="1" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Purpose</label>
                            <select id="purpose" name="purpose" required>
                                <option value="">Select Purpose</option>
                                <option value="Tithe">Tithe</option>
                                <option value="Offering">Offering</option>
                                <option value="Building Fund">Building Fund</option>
                                <option value="Missions">Missions</option>
                                <option value="Charity">Charity</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Bank Deposit">Bank Deposit</option>
                            </select>
                        </div>
                        
                        <div class="bank-details">
                            <h3>Bank Account Details</h3>
                            <p><strong>Bank Name:</strong> First Bank</p>
                            <p><strong>Account Name:</strong> Church Management System</p>
                            <p><strong>Account Number:</strong> 1234567890</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="reference">Transaction Reference</label>
                            <input type="text" id="reference" name="reference" required>
                            <p class="form-note">Enter the reference number from your bank transfer</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_proof">Payment Proof</label>
                            <input type="file" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                            <p class="form-note">Upload a screenshot or photo of your payment receipt</p>
                        </div>
                        
                        <button type="submit" class="btn">Submit Donation</button>
                    </form>
                </div>
            </section>
            
            <section class="donation-history">
                <h2>Donation History</h2>
                
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
                                    echo '<td><a href="donation-receipt.php?id=' . $donation['id'] . '" class="btn-small">View Receipt</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No donation history found.</td></tr>';
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
