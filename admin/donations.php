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

// Process donation status update
if(isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id']) && isset($_GET['status'])) {
    $donation_id = intval($_GET['id']);
    $status = $_GET['status'];
    
    // Validate status
    if($status !== 'approved' && $status !== 'rejected') {
        $error = 'Invalid status';
    } else {
        // Update donation status
        $query = "UPDATE donations SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $status, $donation_id);
        
        if($stmt->execute()) {
            $success = 'Donation status updated successfully';
        } else {
            $error = 'Failed to update donation status';
        }
    }
}

// Get all donations
$query = "SELECT d.*, u.name, u.email FROM donations d 
          JOIN users u ON d.user_id = u.id 
          ORDER BY d.donation_date DESC";
$result = $conn->query($query);
$donations = $result;

// Get donation statistics
$query = "SELECT 
            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as count_pending
          FROM donations";
$result = $conn->query($query);
$stats = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donations - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="donations-section">
                <h2>Manage Donations</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="donation-stats">
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3>Approved Donations</h3>
                            <p class="stat-value">₦<?php echo number_format($stats['total_approved'] ?: 0, 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3>Pending Donations</h3>
                            <p class="stat-value">₦<?php echo number_format($stats['total_pending'] ?: 0, 2); ?></p>
                            <p class="stat-subtext"><?php echo $stats['count_pending']; ?> pending donations</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">❌</div>
                        <div class="stat-info">
                            <h3>Rejected Donations</h3>
                            <p class="stat-value">₦<?php echo number_format($stats['total_rejected'] ?: 0, 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="donation-filters">
                    <h3>Filter Donations</h3>
                    <div class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status-filter">Status</label>
                                <select id="status-filter">
                                    <option value="all">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date-from">From Date</label>
                                <input type="date" id="date-from">
                            </div>
                            
                            <div class="form-group">
                                <label for="date-to">To Date</label>
                                <input type="date" id="date-to">
                            </div>
                            
                            <button type="button" id="filter-btn" class="btn">Apply Filters</button>
                            <button type="button" id="reset-btn" class="btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table" id="donations-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Purpose</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($donations->num_rows > 0) {
                                while($donation = $donations->fetch_assoc()) {
                                    echo '<tr data-status="' . $donation['status'] . '" data-date="' . date('Y-m-d', strtotime($donation['donation_date'])) . '">';
                                    echo '<td>' . htmlspecialchars($donation['name']) . '</td>';
                                    echo '<td>₦' . number_format($donation['amount'], 2) . '</td>';
                                    echo '<td>' . htmlspecialchars($donation['purpose']) . '</td>';
                                    echo '<td>' . htmlspecialchars($donation['payment_method']) . '</td>';
                                    echo '<td>' . htmlspecialchars($donation['reference']) . '</td>';
                                    echo '<td>' . date('M j, Y', strtotime($donation['donation_date'])) . '</td>';
                                    echo '<td><span class="status-badge status-' . $donation['status'] . '">' . ucfirst($donation['status']) . '</span></td>';
                                    echo '<td class="action-buttons">';
                                    
                                    if($donation['status'] === 'pending') {
                                        echo '<a href="donations.php?action=update&id=' . $donation['id'] . '&status=approved" class="btn-small btn-success" onclick="return confirm(\'Are you sure you want to approve this donation?\')">Approve</a>';
                                        echo '<a href="donations.php?action=update&id=' . $donation['id'] . '&status=rejected" class="btn-small btn-danger" onclick="return confirm(\'Are you sure you want to reject this donation?\')">Reject</a>';
                                    }
                                    
                                    echo '<a href="view-donation.php?id=' . $donation['id'] . '" class="btn-small">View Details</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="8">No donations found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="export-buttons">
                    <button type="button" id="export-pdf" class="btn">Export to PDF</button>
                    <button type="button" id="export-excel" class="btn">Export to Excel</button>
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
            const statusFilter = document.getElementById('status-filter');
            const dateFrom = document.getElementById('date-from');
            const dateTo = document.getElementById('date-to');
            const filterBtn = document.getElementById('filter-btn');
            const resetBtn = document.getElementById('reset-btn');
            const table = document.getElementById('donations-table');
            const rows = table.getElementsByTagName('tr');
            
            // Apply filters
            filterBtn.addEventListener('click', function() {
                const status = statusFilter.value;
                const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
                const toDate = dateTo.value ? new Date(dateTo.value) : null;
                
                for(let i = 1; i < rows.length; i++) {
                    let row = rows[i];
                    let showRow = true;
                    
                    // Skip if it's a "no donations found" row
                    if(row.cells.length === 1) continue;
                    
                    // Filter by status
                    if(status !== 'all') {
                        const rowStatus = row.getAttribute('data-status');
                        if(rowStatus !== status) {
                            showRow = false;
                        }
                    }
                    
                    // Filter by date range
                    if(showRow && (fromDate || toDate)) {
                        const rowDate = new Date(row.getAttribute('data-date'));
                        
                        if(fromDate && rowDate < fromDate) {
                            showRow = false;
                        }
                        
                        if(toDate && rowDate > toDate) {
                            showRow = false;
                        }
                    }
                    
                    row.style.display = showRow ? '' : 'none';
                }
            });
            
            // Reset filters
            resetBtn.addEventListener('click', function() {
                statusFilter.value = 'all';
                dateFrom.value = '';
                dateTo.value = '';
                
                for(let i = 1; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            });
            
            // Export functions (placeholders)
            document.getElementById('export-pdf').addEventListener('click', function() {
                alert('PDF export functionality will be implemented here.');
            });
            
            document.getElementById('export-excel').addEventListener('click', function() {
                alert('Excel export functionality will be implemented here.');
            });
        });
    </script>
</body>
</html>
