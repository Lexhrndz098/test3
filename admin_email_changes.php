<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

// Include database connection
require_once "db_connect.php";

// Define variables and initialize with empty values
$message = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if we're approving an email change request
    if(isset($_POST["approve_request"])) {
        $request_id = trim($_POST["request_id"]);
        
        // Get the request details
        $sql = "SELECT user_id, new_email FROM pending_email_changes WHERE id = ? AND status = 'pending'";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $request_id);
            if($stmt->execute()) {
                $stmt->store_result();
                if($stmt->num_rows == 1) {
                    $stmt->bind_result($user_id, $new_email);
                    $stmt->fetch();
                    
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Update the user's email
                        $update_sql = "UPDATE users SET email = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $new_email, $user_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Update the request status
                        $status_sql = "UPDATE pending_email_changes SET status = 'approved', admin_id = ?, processed_date = NOW() WHERE id = ?";
                        $status_stmt = $conn->prepare($status_sql);
                        $status_stmt->bind_param("ii", $_SESSION["id"], $request_id);
                        $status_stmt->execute();
                        $status_stmt->close();
                        
                        // Commit transaction
                        $conn->commit();
                        $message = "<div class=\"success\">Email change request approved successfully.</div>";
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        $message = "<div class=\"error\">Error processing request: " . $e->getMessage() . "</div>";
                    }
                } else {
                    $message = "<div class=\"error\">Request not found or already processed.</div>";
                }
            } else {
                $message = "<div class=\"error\">Something went wrong. Please try again later.</div>";
            }
            $stmt->close();
        }
    }
    
    // Check if we're rejecting an email change request
    if(isset($_POST["reject_request"])) {
        $request_id = trim($_POST["request_id"]);
        $notes = trim($_POST["rejection_notes"]);
        
        // Update the request status
        $sql = "UPDATE pending_email_changes SET status = 'rejected', admin_id = ?, processed_date = NOW(), notes = ? WHERE id = ? AND status = 'pending'";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isi", $_SESSION["id"], $notes, $request_id);
            if($stmt->execute()) {
                if($stmt->affected_rows > 0) {
                    $message = "<div class=\"success\">Email change request rejected successfully.</div>";
                } else {
                    $message = "<div class=\"error\">Request not found or already processed.</div>";
                }
            } else {
                $message = "<div class=\"error\">Something went wrong. Please try again later.</div>";
            }
            $stmt->close();
        }
    }
}

// Get all pending email change requests
$pending_requests = [];
$sql = "SELECT pec.id, pec.user_id, u.username, pec.current_email, pec.new_email, pec.request_date 
        FROM pending_email_changes pec 
        JOIN users u ON pec.user_id = u.id 
        WHERE pec.status = 'pending' 
        ORDER BY pec.request_date ASC";
if($result = $conn->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

// Get all processed email change requests (last 50)
$processed_requests = [];
$sql = "SELECT pec.id, pec.user_id, u.username, pec.current_email, pec.new_email, 
        pec.request_date, pec.status, pec.processed_date, pec.notes, a.username as admin_name 
        FROM pending_email_changes pec 
        JOIN users u ON pec.user_id = u.id 
        LEFT JOIN users a ON pec.admin_id = a.id 
        WHERE pec.status IN ('approved', 'rejected') 
        ORDER BY pec.processed_date DESC 
        LIMIT 50";
if($result = $conn->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $processed_requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change Requests - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .navbar {
            background-color: #333;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .navbar a {
            float: left;
            display: block;
            color: white;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .navbar a:hover {
            background-color: #555;
        }
        .navbar a.active {
            background-color: #4CAF50;
        }
        .navbar .right {
            float: right;
        }
        .navbar .username {
            color: white;
            padding: 14px 16px;
            float: right;
        }
        .content-wrapper {
            padding: 20px;
        }
        .panel {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .panel h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            height: 100px;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
        .success {
            color: green;
            font-size: 14px;
            margin-top: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10px;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_panel.php">Dashboard</a>
        <a href="admin_requests.php">Requests</a>
        <a href="users.php">Users</a>
        <a href="admin_chat.php">Chat Support</a>
        <a href="admin_messages.php">User Messages</a>
        <a href="admin_teleconsultation.php">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="admin_email_changes.php" class="active">Email Changes</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>Email Change Request Management</h1>
        
        <?php if(!empty($message)): ?>
            <div class="panel">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="panel">
            <h2>Pending Email Change Requests</h2>
            <?php if(empty($pending_requests)): ?>
                <p>No pending email change requests found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Current Email</th>
                            <th>New Email</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_requests as $request): ?>
                        <tr>
                            <td><?php echo $request["id"]; ?></td>
                            <td><?php echo htmlspecialchars($request["username"]); ?></td>
                            <td><?php echo htmlspecialchars($request["current_email"]); ?></td>
                            <td><?php echo htmlspecialchars($request["new_email"]); ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($request["request_date"])); ?></td>
                            <td>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request["id"]; ?>">
                                    <button type="submit" name="approve_request" class="btn" onclick="return confirm('Are you sure you want to approve this email change?');">Approve</button>
                                </form>
                                <button class="btn btn-danger" onclick="openRejectModal(<?php echo $request["id"]; ?>)">Reject</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <h2>Recent Processed Requests</h2>
            <?php if(empty($processed_requests)): ?>
                <p>No processed email change requests found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Old Email</th>
                            <th>New Email</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Processed Date</th>
                            <th>Processed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($processed_requests as $request): ?>
                        <tr>
                            <td><?php echo $request["id"]; ?></td>
                            <td><?php echo htmlspecialchars($request["username"]); ?></td>
                            <td><?php echo htmlspecialchars($request["current_email"]); ?></td>
                            <td><?php echo htmlspecialchars($request["new_email"]); ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($request["request_date"])); ?></td>
                            <td>
                                <?php if($request["status"] == "approved"): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($request["processed_date"])); ?></td>
                            <td><?php echo htmlspecialchars($request["admin_name"]); ?></td>
                            <td><?php echo htmlspecialchars($request["notes"] ?? ""); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Reject Email Change Request</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="form-group">
                    <label for="rejection_notes">Reason for Rejection:</label>
                    <textarea name="rejection_notes" id="rejection_notes" required></textarea>
                </div>
                <button type="submit" name="reject_request" class="btn btn-danger">Confirm Rejection</button>
            </form>
        </div>
    </div>
    
    <script>
        // Get the modal
        var modal = document.getElementById("rejectModal");
        
        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];
        
        // Function to open the modal and set the request ID
        function openRejectModal(requestId) {
            document.getElementById("reject_request_id").value = requestId;
            modal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>Processed By</th>
                            <th>Notes