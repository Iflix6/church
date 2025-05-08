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

// Process member deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $member_id = intval($_GET['id']);
    
    // Check if member exists
    $query = "SELECT id FROM users WHERE id = ? AND role = 'member'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        // Delete member
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $member_id);
        
        if($stmt->execute()) {
            $success = 'Member deleted successfully';
        } else {
            $error = 'Failed to delete member';
        }
    } else {
        $error = 'Member not found';
    }
}

// Get all members
$query = "SELECT * FROM users WHERE role = 'member' ORDER BY name ASC";
$result = $conn->query($query);
$members = $result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Church Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin-header.php'; ?>
        
        <main>
            <section class="members-section">
                <h2>Manage Members</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="table-actions">
                    <a href="add-member.php" class="btn">Add New Member</a>
                    <div class="search-box">
                        <input type="text" id="memberSearch" placeholder="Search members...">
                        <button type="button" id="searchBtn">Search</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table" id="membersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($members->num_rows > 0) {
                                while($member = $members->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($member['name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($member['email']) . '</td>';
                                    echo '<td>' . htmlspecialchars($member['phone']) . '</td>';
                                    echo '<td>' . htmlspecialchars($member['address'] ?: 'N/A') . '</td>';
                                    echo '<td>' . date('M j, Y', strtotime($member['created_at'])) . '</td>';
                                    echo '<td class="action-buttons">';
                                    echo '<a href="edit-member.php?id=' . $member['id'] . '" class="btn-small">Edit</a>';
                                    echo '<a href="view-member.php?id=' . $member['id'] . '" class="btn-small">View</a>';
                                    echo '<a href="members.php?action=delete&id=' . $member['id'] . '" class="btn-small btn-danger" onclick="return confirm(\'Are you sure you want to delete this member?\')">Delete</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No members found.</td></tr>';
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
    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('memberSearch');
            const searchBtn = document.getElementById('searchBtn');
            const table = document.getElementById('membersTable');
            const rows = table.getElementsByTagName('tr');
            
            function searchMembers() {
                const searchText = searchInput.value.toLowerCase();
                
                for(let i = 1; i < rows.length; i++) {
                    let found = false;
                    const cells = rows[i].getElementsByTagName('td');
                    
                    for(let j = 0; j < cells.length - 1; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        
                        if(cellText.indexOf(searchText) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = found ? '' : 'none';
                }
            }
            
            searchBtn.addEventListener('click', searchMembers);
            
            searchInput.addEventListener('keyup', function(e) {
                if(e.key === 'Enter') {
                    searchMembers();
                }
            });
        });
    </script>
</body>
</html>
