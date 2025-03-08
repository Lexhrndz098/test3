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

// Get active teleconsultation sessions (this would need a table to track sessions)
$active_sessions = [];
$sql = "SELECT * FROM teleconsultation_sessions WHERE status = 'active' ORDER BY start_time DESC";

// This is a placeholder - you would need to create this table
// Uncomment when the table exists
/*
if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $active_sessions[] = $row;
    }
}
*/

// Get recent teleconsultation sessions
$recent_sessions = [];
$sql = "SELECT * FROM teleconsultation_sessions WHERE status = 'completed' ORDER BY end_time DESC LIMIT 10";

// This is a placeholder - you would need to create this table
// Uncomment when the table exists
/*
if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $recent_sessions[] = $row;
    }
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teleconsultation Management - Admin Panel</title>
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
        .btn-info {
            background-color: #2196F3;
        }
        .btn-info:hover {
            background-color: #0b7dda;
        }
        .btn-warning {
            background-color: #ff9800;
        }
        .btn-warning:hover {
            background-color: #e68a00;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #c3e6cb;
            color: #155724;
        }
        .status-waiting {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .video-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .video-placeholder {
            background-color: #333;
            height: 180px;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            margin-bottom: 10px;
        }
        .session-info {
            margin-top: 10px;
        }
        .session-info p {
            margin: 5px 0;
            font-size: 14px;
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
        <a href="admin_teleconsultation.php" class="active">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>Teleconsultation Management</h1>
        
        <div class="dashboard">
            <div class="card">
                <h3>Active Sessions</h3>
                <div class="number"><?php echo count($active_sessions); ?></div>
            </div>
            <div class="card">
                <h3>Total Sessions Today</h3>
                <div class="number">0</div>
            </div>
            <div class="card">
                <h3>Average Duration</h3>
                <div class="number">0 min</div>
            </div>
        </div>
        
        <div class="panel">
            <h2>Active Teleconsultation Sessions</h2>
            <?php if(count($active_sessions) > 0): ?>
                <div class="video-grid">
                    <?php foreach($active_sessions as $session): ?>
                    <div class="video-card">
                        <div class="video-placeholder">
                            <i class="fas fa-video fa-3x"></i>
                        </div>
                        <div class="session-info">
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($session['room_id']); ?></p>
                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($session['patient_name']); ?></p>
                            <p><strong>Started:</strong> <?php echo htmlspecialchars($session['start_time']); ?></p>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($session['duration']); ?> minutes</p>
                        </div>
                        <div class="action-buttons">
                            <a href="join_teleconsultation.php?room=<?php echo $session['room_id']; ?>" class="btn btn-info">Join Session</a>
                            <a href="#" class="btn btn-danger">End Session</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No active teleconsultation sessions at the moment.</p>
                <p>When users start teleconsultation sessions, they will appear here for monitoring.</p>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <h2>Recent Sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Patient</th>
                        <th>Counselor</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recent_sessions) > 0): ?>
                        <?php foreach($recent_sessions as $session): ?>
                        <tr>
                            <td><?php echo $session['id']; ?></td>
                            <td><?php echo htmlspecialchars($session['room_id']); ?></td>
                            <td><?php echo htmlspecialchars($session['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($session['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($session['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($session['end_time']); ?></td>
                            <td><?php echo htmlspecialchars($session['duration']); ?> min</td>
                            <td>
                                <span class="status-badge status-completed">Completed</span>
                            </td>
                            <td class="action-buttons">
                                <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn btn-info">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No recent sessions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>