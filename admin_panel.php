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

// Get statistics for dashboard
$total_users = $admin_users = $regular_users = 0;
$sql = "SELECT COUNT(*) as total, SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_count FROM users";
if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $total_users = $row["total"];
        $admin_users = $row["admin_count"];
        $regular_users = $total_users - $admin_users;
    }
}

// Get all users for the table
$users = [];
$sql = "SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 10";
if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
}

// Process user deletion
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $user_id = trim($_GET["delete"]);
    
    // Make sure we're not deleting an admin
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $user_id);
        if($stmt->execute()){
            $stmt->store_result();
            if($stmt->num_rows == 1){
                $stmt->bind_result($is_admin);
                $stmt->fetch();
                
                if(!$is_admin){
                    // Delete the user
                    $delete_sql = "DELETE FROM users WHERE id = ?";
                    if($delete_stmt = $conn->prepare($delete_sql)){
                        $delete_stmt->bind_param("i", $user_id);
                        if($delete_stmt->execute()){
                            // Redirect to avoid resubmission
                            header("location: admin_panel.php?deleted=success");
                            exit;
                        }
                        $delete_stmt->close();
                    }
                }
            }
        }
        $stmt->close();
    }
}

// Process admin status toggle
if(isset($_GET["toggle_admin"]) && !empty($_GET["toggle_admin"])){
    $user_id = trim($_GET["toggle_admin"]);
    
    // Get current admin status
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $user_id);
        if($stmt->execute()){
            $stmt->store_result();
            if($stmt->num_rows == 1){
                $stmt->bind_result($is_admin);
                $stmt->fetch();
                
                // Toggle admin status
                $new_status = $is_admin ? 0 : 1;
                $update_sql = "UPDATE users SET is_admin = ? WHERE id = ?";
                if($update_stmt = $conn->prepare($update_sql)){
                    $update_stmt->bind_param("ii", $new_status, $user_id);
                    if($update_stmt->execute()){
                        // Redirect to avoid resubmission
                        header("location: admin_panel.php?updated=success");
                        exit;
                    }
                    $update_stmt->close();
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="hamburger_menu.css">
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
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        .card h3 {
            margin-top: 0;
            color: #333;
        }
        .card .number {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
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
        .btn-warning {
            background-color: #ff9800;
        }
        .btn-warning:hover {
            background-color: #e68a00;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_panel.php" class="active">Dashboard</a>
        <a href="admin_requests.php">Requests</a>
        <a href="users.php">Users</a>
        <a href="admin_chat.php">Chat Support</a>
        <a href="admin_messages.php">User Messages</a>
        <a href="admin_teleconsultation.php">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
        <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>Admin Dashboard</h1>
        
        <?php if(isset($_GET["deleted"]) && $_GET["deleted"] == "success"): ?>
            <div class="panel" style="background-color: #dff0d8; color: #3c763d;">
                User has been successfully deleted.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET["updated"]) && $_GET["updated"] == "success"): ?>
            <div class="panel" style="background-color: #dff0d8; color: #3c763d;">
                User admin status has been successfully updated.
            </div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $total_users; ?></div>
            </div>
            <div class="card">
                <h3>Admin Users</h3>
                <div class="number"><?php echo $admin_users; ?></div>
            </div>
            <div class="card">
                <h3>Regular Users</h3>
                <div class="number"><?php echo $regular_users; ?></div>
            </div>
        </div>
        
        <div class="panel">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user["id"]; ?></td>
                        <td><?php echo htmlspecialchars($user["username"]); ?></td>
                        <td><?php echo htmlspecialchars($user["email"]); ?></td>
                        <td><?php echo $user["is_admin"] ? "Admin" : "User"; ?></td>
                        <td><?php echo $user["created_at"]; ?></td>
                        <td class="action-buttons">
                            <a href="admin_panel.php?toggle_admin=<?php echo $user["id"]; ?>" class="btn btn-warning">
                                <?php echo $user["is_admin"] ? "Remove Admin" : "Make Admin"; ?>
                            </a>
                            <?php if(!$user["is_admin"]): ?>
                            <a href="admin_panel.php?delete=<?php echo $user["id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>