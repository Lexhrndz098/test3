<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "db_connect.php";

// Check if request ID is provided
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: request.php");
    exit;
}

$request_id = trim($_GET["id"]);
$request = null;
$responses = [];

// Get request details
$sql = "SELECT r.*, u.username FROM requests r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.request_id = ? AND r.user_id = ?";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("ii", $request_id, $_SESSION["id"]);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        if($result->num_rows == 1){
            $request = $result->fetch_assoc();
            
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
        } else {
            // Request not found or doesn't belong to current user
            header("location: request.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="background_template.css">
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
            max-width: 1000px;
            margin: 0 auto;
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
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="bg-container bg-request"></div>
    
    <div class="navbar" id="myNavbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php" class="active">My Requests</a>
        <a href="contact.php">Contact</a>
        <a href="chat.php">Chat Support</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <a href="request.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Requests</a>
        
        <h1>Request Details</h1>
        
        <?php if($request): ?>
        <div class="panel request-details">
            <h2><?php echo htmlspecialchars($request["subject"]); ?></h2>
            
            <div class="request-info">
                <div class="info-item">
                    <div class="info-label">Request ID</div>
                    <div class="info-value"><?php echo $request["request_id"]; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($request["request_type"]); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $request["status"]; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $request["status"])); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Priority</div>
                    <div class="info-value">
                        <span class="priority-<?php echo $request["priority"]; ?>">
                            <?php echo ucfirst($request["priority"]); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Submitted By</div>
                    <div class="info-value"><?php echo htmlspecialchars($request["username"]); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Submitted</div>
                    <div class="info-value"><?php echo date("F d, Y h:i A", strtotime($request["created_at"])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date("F d, Y h:i A", strtotime($request["updated_at"])); ?></div>
                </div>
            </div>
            
            <div class="info-label">Description</div>
            <div class="description-box"><?php echo htmlspecialchars($request["description"]); ?></div>
        </div>
        
        <div class="panel responses-section">
            <h3>Responses</h3>
            
            <?php if(empty($responses)): ?>
                <p class="no-responses">No responses yet. Our team will respond to your request soon.</p>
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
        <?php else: ?>
        <div class="panel">
            <p>Request not found or you don't have permission to view it.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>