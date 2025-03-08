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
$response = $status = "";
$response_err = "";

// Process response submission
if(isset($_POST["request_id"]) && !empty($_POST["request_id"]) && isset($_POST["response"])){
    // Validate response
    if(empty(trim($_POST["response"]))){
        $response_err = "Please enter a response.";
    } else{
        $response = trim($_POST["response"]);
    }
    
    // Check if status update is provided
    if(isset($_POST["status"]) && !empty($_POST["status"])){
        $status = trim($_POST["status"]);
    }
    
    // Check input errors before inserting in database
    if(empty($response_err)){
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First, insert the response
            $sql = "INSERT INTO request_responses (request_id, admin_id, response) VALUES (?, ?, ?)";
            
            if($stmt = $conn->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("iis", $param_request_id, $param_admin_id, $param_response);
                
                // Set parameters
                $param_request_id = trim($_POST["request_id"]);
                $param_admin_id = $_SESSION["id"];
                $param_response = $response;
                
                // Execute the prepared statement
                $stmt->execute();
                
                // Close statement
                $stmt->close();
            }
            
            // Then, update the request status if provided
            if(!empty($status)){
                $sql = "UPDATE requests SET status = ? WHERE request_id = ?";
                
                if($stmt = $conn->prepare($sql)){
                    // Bind variables to the prepared statement as parameters
                    $stmt->bind_param("si", $param_status, $param_request_id);
                    
                    // Set parameters
                    $param_status = $status;
                    $param_request_id = trim($_POST["request_id"]);
                    
                    // Execute the prepared statement
                    $stmt->execute();
                    
                    // Close statement
                    $stmt->close();
                }
            }
            
            // Commit the transaction
            $conn->commit();
            
            // Redirect to avoid resubmission
            header("location: admin_requests.php?success=1");
            exit();
            
        } catch (Exception $e) {
            // Roll back the transaction if something failed
            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET["status"]) ? $_GET["status"] : "";
$priority_filter = isset($_GET["priority"]) ? $_GET["priority"] : "";

// Prepare the base query
$sql = "SELECT r.*, u.username, COUNT(rr.response_id) as response_count 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN request_responses rr ON r.request_id = rr.request_id";

// Add filters if provided
$where_clauses = [];
if(!empty($status_filter)){
    $where_clauses[] = "r.status = '$status_filter'";
}
if(!empty($priority_filter)){
    $where_clauses[] = "r.priority = '$priority_filter'";
}

// Combine where clauses if any
if(!empty($where_clauses)){
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Complete the query with grouping and ordering
$sql .= " GROUP BY r.request_id ORDER BY 
         CASE r.status 
             WHEN 'pending' THEN 1 
             WHEN 'in_progress' THEN 2 
             WHEN 'completed' THEN 3 
             WHEN 'cancelled' THEN 4 
         END, 
         CASE r.priority 
             WHEN 'high' THEN 1 
             WHEN 'medium' THEN 2 
             WHEN 'low' THEN 3 
         END, 
         r.created_at DESC";

// Execute the query
$requests = [];
if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $requests[] = $row;
    }
}

// Get request details if viewing a specific request
$current_request = null;
$responses = [];

if(isset($_GET["id"]) && !empty($_GET["id"])){
    $request_id = trim($_GET["id"]);
    
    // Get request details
    $sql = "SELECT r.*, u.username FROM requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.request_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $request_id);
        
        if($stmt->execute()){
            $result = $stmt->get_result();
            
            if($result->num_rows == 1){
                $current_request = $result->fetch_assoc();
                
                // Get responses for this request
                $sql = "SELECT rr.*, u.username FROM request_responses rr 
                        JOIN users u ON rr.admin_id = u.id 
                        WHERE rr.request_id = ? 
                        ORDER BY rr.created_at ASC";
                
                if($resp_stmt = $conn->prepare($sql)){
                    $resp_stmt->bind_param("i", $request_id);
                    
                    if($resp_stmt->execute()){
                        $resp_result = $resp_stmt->get_result();
                        
                        while($row = $resp_result->fetch_assoc()){
                            $responses[] = $row;
                        }
                    }
                    
                    $resp_stmt->close();
                }
            }
        }
        
        $stmt->close();
    }
}

// Get statistics for dashboard
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'high_priority' => 0
];

$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as `high_priority`
        FROM requests";

if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $stats = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management - Admin Panel</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            margin: 10px 0;
        }
        .card.total .number {
            color: #4CAF50;
        }
        .card.pending .number {
            color: #ffc107;
        }
        .card.in-progress .number {
            color: #17a2b8;
        }
        .card.completed .number {
            color: #28a745;
        }
        .card.cancelled .number {
            color: #dc3545;
        }
        .card.high-priority .number {
            color: #dc3545;
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
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-group {
            display: flex;
            align-items: center;
        }
        .filter-group label {
            margin-right: 10px;
            font-weight: bold;
        }
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-in_progress {
            background-color: #b8daff;
            color: #004085;
        }
        .status-completed {
            background-color: #c3e6cb;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f5c6cb;
            color: #721c24;
        }
        .priority-high {
            color: #dc3545;
        }
        .priority-medium {
            color: #fd7e14;
        }
        .priority-low {
            color: #28a745;
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
            background-color: #17a2b8;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .btn-warning {
            background-color: #ffc107;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
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
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .request-details {
            margin-bottom: 30px;
        }
        .request-info {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .info-item {
            flex: 1 0 200px;
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
        }
        .description-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 20px;
            white-space: pre-line;
        }
        .responses-section h3 {
            margin-top: 0;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .response {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        .response-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .response-content {
            white-space: pre-line;
        }
        .no-responses {
            color: #666;
            font-style: italic;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            height: 150px;
            resize: vertical;
        }
    </style>
</head>
<body>

    
    <div class="navbar" id="myNavbar">
        <a href="admin_panel.php">Dashboard</a>
        <a href="admin_requests.php" class="active">Requests</a>
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
        <h1>Request Management</h1>
        
        <?php if(isset($_GET["success"]) && $_GET["success"] == 1): ?>
            <div class="alert alert-success">
                Your response has been submitted successfully.
            </div>
        <?php endif; ?>
        
        <?php if(!$current_request): ?>
            <!-- Dashboard and Request List -->
            <div class="dashboard">
                <div class="card total">
                    <h3>Total Requests</h3>
                    <div class="number"><?php echo $stats["total"]; ?></div>
                </div>
                <div class="card pending">
                    <h3>Pending</h3>
                    <div class="number"><?php echo $stats["pending"]; ?></div>
                </div>
                <div class="card in-progress">
                    <h3>In Progress</h3>
                    <div class="number"><?php echo $stats["in_progress"]; ?></div>
                </div>
                <div class="card completed">
                    <h3>Completed</h3>
                    <div class="number"><?php echo $stats["completed"]; ?></div>
                </div>
                <div class="card cancelled">
                    <h3>Cancelled</h3>
                    <div class="number"><?php echo $stats["cancelled"]; ?></div>
                </div>
                <div class="card high-priority">
                    <h3>High Priority</h3>
                    <div class="number"><?php echo $stats["high_priority"]; ?></div>
                </div>
            </div>
            
            <div class="panel">
                <h2>All Requests</h2>
                
                <div class="filter-bar">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="status-filter" onchange="applyFilters()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == "pending" ? "selected" : ""; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter == "in_progress" ? "selected" : ""; ?>>In Progress</option>
                            <option value="completed" <?php echo $status_filter == "completed" ? "selected" : ""; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == "cancelled" ? "selected" : ""; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Priority:</label>
                        <select id="priority-filter" onchange="applyFilters()">
                            <option value="">All Priorities</option>
                            <option value="high" <?php echo $priority_filter == "high" ? "selected" : ""; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter == "medium" ? "selected" : ""; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter == "low" ? "selected" : ""; ?>>Low</option>
                        </select>
                    </div>
                </div>
                
                <?php if(empty($requests)): ?>
                    <p>No requests found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Responses</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $request): ?>
                            <tr>
                                <td><?php echo $request["request_id"]; ?></td>
                                <td><?php echo htmlspecialchars($request["username"]); ?></td>
                                <td><?php echo htmlspecialchars($request["request_type"]); ?></td>
                                <td><?php echo htmlspecialchars($request["subject"]); ?></td>
                                <td>
                                    <span class="priority-<?php echo $request["priority"]; ?>">
                                        <?php echo ucfirst($request["priority"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $request["status"]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request["status"])); ?>
                                    </span>
                                </td>
                                <td><?php echo date("M d, Y", strtotime($request["created_at"])); ?></td>
                                <td><?php echo $request["response_count"]; ?></td>
                                <td>
                                    <a href="admin_requests.php?id=<?php echo $request["request_id"]; ?>" class="btn btn-info">View & Respond</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Single Request View -->
            <a href="admin_requests.php" class="btn" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back to All Requests</a>
            
            <div class="panel request-details">
                <h2><?php echo htmlspecialchars($current_request["subject"]); ?></h2>
                
                <div class="request-info">
                    <div class="info-item">
                        <div class="info-label">Request ID</div>
                        <div class="info-value"><?php echo $current_request["request_id"]; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($current_request["request_type"]); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $current_request["status"]; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $current_request["status"])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Priority</div>
                        <div class="info-value">
                            <span class="priority-<?php echo $current_request["priority"]; ?>">
                                <?php echo ucfirst($current_request["priority"]); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Submitted By</div>
                        <div class="info-value"><?php echo htmlspecialchars($current_request["username"]); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date Submitted</div>
                        <div class="info-value"><?php echo date("F d, Y h:i A", strtotime($current_request["created_at"])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value"><?php echo date("F d, Y h:i A", strtotime($current_request["updated_at"])); ?></div>
                    </div>
                </div>
                
                <div class="info-label">Description</div>
                <div class="description-box"><?php echo htmlspecialchars($current_request["description"]); ?></div>
            </div>
            
            <div class="panel responses-section">
                <h3>Previous Responses</h3>
                
                <?php if(empty($responses)): ?>
                    <p class="no-responses">No responses yet.</p>
                <?php else: ?>
                    <?php foreach($responses as $response): ?>
                    <div class="response">
                        <div class="response-header">
                            <div><strong><?php echo htmlspecialchars($response["username"]); ?></strong> (Admin)</div>
                            <div><?php echo date("F d, Y h:i A", strtotime($response["created_at"])); ?></div>
                        </div>
                        <div class="response-content"><?php echo htmlspecialchars($response["response"]); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="panel">
                <h3>Add Response</h3>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $current_request["request_id"]); ?>" method="post">
                    <input type="hidden" name="request_id" value="<?php echo $current_request["request_id"]; ?>">
                    
                    <div class="form-group">
                        <label>Update Status (Optional)</label>
                        <select name="status" class="form-control">
                            <option value="">Keep Current Status</option>
                            <option value="pending" <?php echo $current_request["status"] == "pending" ? "selected" : ""; ?>>Pending</option>
                            <option value="in_progress" <?php echo $current_request["status"] == "in_progress" ? "selected" : ""; ?>>In Progress</option>
                            <option value="completed" <?php echo $current_request["status"] == "completed" ? "selected" : ""; ?>>Completed</option>
                            <option value="cancelled" <?php echo $current_request["status"] == "cancelled" ? "selected" : ""; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Response</label>
                        <textarea name="response" class="form-control <?php echo (!empty($response_err)) ? 'is-invalid' : ''; ?>" required></textarea>
                        <span class="alert-danger"><?php echo $response_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Submit Response</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function applyFilters() {
        const statusFilter = document.getElementById('status-filter').value;
        const priorityFilter = document.getElementById('priority-filter').value;
        
        let url = 'admin_requests.php?';
        
        if (statusFilter) {
            url += 'status=' + statusFilter + '&';
        }
        
        if (priorityFilter) {
            url += 'priority=' + priorityFilter + '&';
        }
        
        // Remove trailing & if exists
        if (url.endsWith('&')) {
            url = url.slice(0, -1);
        }
        
        window.location.href = url;
    }
    </script>
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>