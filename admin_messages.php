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

// Process message actions
if(isset($_GET["action"]) && isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $message_id = trim($_GET["id"]);
    
    // Mark as read
    if($_GET["action"] === "read"){
        $sql = "UPDATE contact_messages SET is_read = 1 WHERE message_id = ?";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $message_id);
            
            if($stmt->execute()){
                header("location: admin_messages.php?status=read_success");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    // Delete message
    if($_GET["action"] === "delete"){
        $sql = "DELETE FROM contact_messages WHERE message_id = ?";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $message_id);
            
            if($stmt->execute()){
                header("location: admin_messages.php?status=delete_success");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
}

// Get all messages
$messages = [];
$sql = "SELECT m.*, u.username FROM contact_messages m 
        LEFT JOIN users u ON m.user_id = u.id 
        ORDER BY m.created_at DESC";

if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $messages[] = $row;
    }
}

// Get unread message count
$unread_count = 0;
$sql = "SELECT COUNT(*) FROM contact_messages WHERE is_read = 0";
if($result = $conn->query($sql)){
    $row = $result->fetch_row();
    $unread_count = $row[0];
}

// Get total message count
$total_count = count($messages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Messages - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .btn-info {
            background-color: #2196F3;
        }
        .btn-info:hover {
            background-color: #0b7dda;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .message-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .unread {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-unread {
            background-color: #ffecb3;
            color: #856404;
        }
        .status-read {
            background-color: #c3e6cb;
            color: #155724;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .message-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            white-space: pre-line;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_panel.php">Dashboard</a>
        <a href="admin_requests.php">Requests</a>
        <a href="users.php">Users</a>
        <a href="admin_chat.php">Chat Support</a>
        <a href="admin_messages.php" class="active">User Messages</a>
        <a href="admin_teleconsultation.php">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>User Messages</h1>
        
        <?php if(isset($_GET["status"]) && $_GET["status"] == "read_success"): ?>
            <div class="alert alert-success">
                Message has been marked as read.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET["status"]) && $_GET["status"] == "delete_success"): ?>
            <div class="alert alert-success">
                Message has been successfully deleted.
            </div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="card">
                <h3>Total Messages</h3>
                <div class="number"><?php echo $total_count; ?></div>
            </div>
            <div class="card">
                <h3>Unread Messages</h3>
                <div class="number"><?php echo $unread_count; ?></div>
            </div>
        </div>
        
        <div class="panel">
            <h2>User Messages</h2>
            <?php if(count($messages) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($messages as $msg): ?>
                        <tr class="<?php echo $msg["is_read"] == 0 ? 'unread' : ''; ?>">
                            <td><?php echo $msg["message_id"]; ?></td>
                            <td><?php echo htmlspecialchars($msg["username"]); ?></td>
                            <td><?php echo htmlspecialchars($msg["name"]); ?></td>
                            <td><?php echo htmlspecialchars($msg["email"]); ?></td>
                            <td><?php echo htmlspecialchars($msg["subject"]); ?></td>
                            <td class="message-content"><?php echo htmlspecialchars(substr($msg["message"], 0, 50)) . (strlen($msg["message"]) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($msg["created_at"])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $msg["is_read"] == 0 ? 'status-unread' : 'status-read'; ?>">
                                    <?php echo $msg["is_read"] == 0 ? 'Unread' : 'Read'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="btn btn-info" onclick="toggleDetails(<?php echo $msg['message_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if($msg["is_read"] == 0): ?>
                                <a href="admin_messages.php?action=read&id=<?php echo $msg["message_id"]; ?>" class="btn">
                                    <i class="fas fa-check"></i> Mark Read
                                </a>
                                <?php endif; ?>
                                <a href="admin_messages.php?action=delete&id=<?php echo $msg["message_id"]; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $msg['message_id']; ?>" style="display: none;">
                            <td colspan="9">
                                <div class="message-details">
                                    <strong>Full Message:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($msg["message"])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No messages found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleDetails(id) {
        var detailsRow = document.getElementById('details-' + id);
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
        } else {
            detailsRow.style.display = 'none';
        }
    }
    </script>
</body>
</html>