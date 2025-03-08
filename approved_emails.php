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
$email = $message = "";
$email_err = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if we're adding a new approved email
    if(isset($_POST["add_email"])) {
        // Validate email
        if(empty(trim($_POST["email"]))) {
            $email_err = "Please enter an email address.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email already exists in the approved list
            $sql = "SELECT id FROM approved_emails WHERE email = ?";
            
            if($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = trim($_POST["email"]);
                
                if($stmt->execute()) {
                    $stmt->store_result();
                    
                    if($stmt->num_rows > 0) {
                        $email_err = "This email is already in the approved list.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    $message = "Something went wrong. Please try again later.";
                }
                
                $stmt->close();
            }
            
            // If no errors, insert the new email
            if(empty($email_err)) {
                $sql = "INSERT INTO approved_emails (email, added_by) VALUES (?, ?)";
                
                if($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("si", $param_email, $param_added_by);
                    $param_email = $email;
                    $param_added_by = $_SESSION["id"];
                    
                    if($stmt->execute()) {
                        $message = "<div class=\"success\">Email successfully added to approved list.</div>";
                        $email = ""; // Clear the form
                    } else {
                        $message = "<div class=\"error\">Something went wrong. Please try again later.</div>";
                    }
                    
                    $stmt->close();
                }
            }
        }
    }
    
    // Check if we're deleting an approved email
    if(isset($_POST["delete_email"])) {
        $email_id = trim($_POST["email_id"]);
        
        // Only delete if the email is not used
        $sql = "DELETE FROM approved_emails WHERE id = ? AND is_used = 0";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $email_id);
            
            if($stmt->execute()) {
                if($stmt->affected_rows > 0) {
                    $message = "<div class=\"success\">Email successfully removed from approved list.</div>";
                } else {
                    $message = "<div class=\"error\">Unable to delete email. It may be already in use.</div>";
                }
            } else {
                $message = "<div class=\"error\">Something went wrong. Please try again later.</div>";
            }
            
            $stmt->close();
        }
    }
}

// Get all approved emails for the table
$approved_emails = [];
$sql = "SELECT ae.id, ae.email, ae.is_used, ae.created_at, u.username as added_by_name 
        FROM approved_emails ae 
        LEFT JOIN users u ON ae.added_by = u.id 
        ORDER BY ae.created_at DESC";
if($result = $conn->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $approved_emails[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Emails - Admin Panel</title>
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
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
        <a href="approved_emails.php" class="active">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>Approved Emails Management</h1>
        
        <?php if(!empty($message)): ?>
            <div class="panel">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="panel">
            <h2>Add New Approved Email</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <?php if(!empty($email_err)): ?>
                        <span class="error"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <input type="submit" name="add_email" class="btn" value="Add Email">
                </div>
            </form>
        </div>
        
        <div class="panel">
            <h2>Approved Email List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Added By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($approved_emails)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No approved emails found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($approved_emails as $email): ?>
                    <tr>
                        <td><?php echo $email["id"]; ?></td>
                        <td><?php echo htmlspecialchars($email["email"]); ?></td>
                        <td><?php echo $email["is_used"] ? "Used" : "Available"; ?></td>
                        <td><?php echo htmlspecialchars($email["added_by_name"] ?? "System"); ?></td>
                        <td><?php echo $email["created_at"]; ?></td>
                        <td>
                            <?php if(!$email["is_used"]): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="email_id" value="<?php echo $email["id"]; ?>">
                                <button type="submit" name="delete_email" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this email?');">Delete</button>
                            </form>
                            <?php else: ?>
                            <span style="color: #999;">In Use</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>