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

// Define variables and initialize with empty values
$request_type = $subject = $description = $priority = "";
$request_type_err = $subject_err = $description_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate request type
    if(empty(trim($_POST["request_type"]))){
        $request_type_err = "Please select a request type.";
    } else{
        $request_type = trim($_POST["request_type"]);
    }
    
    // Validate subject
    if(empty(trim($_POST["subject"]))){
        $subject_err = "Please enter a subject.";
    } elseif(strlen(trim($_POST["subject"])) > 255){
        $subject_err = "Subject cannot exceed 255 characters.";
    } else{
        $subject = trim($_POST["subject"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Check for priority
    $priority = !empty($_POST["priority"]) ? trim($_POST["priority"]) : "medium";
    
    // Check input errors before inserting in database
    if(empty($request_type_err) && empty($subject_err) && empty($description_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO requests (user_id, request_type, subject, description, priority) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("issss", $param_user_id, $param_request_type, $param_subject, $param_description, $param_priority);
            
            // Set parameters
            $param_user_id = $_SESSION["id"];
            $param_request_type = $request_type;
            $param_subject = $subject;
            $param_description = $description;
            $param_priority = $priority;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Redirect to request page with success message
                header("location: request.php?success=1");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}

// Get user's requests for display
$user_requests = [];
$sql = "SELECT r.*, COUNT(rr.response_id) as response_count 
        FROM requests r 
        LEFT JOIN request_responses rr ON r.request_id = rr.request_id 
        WHERE r.user_id = ? 
        GROUP BY r.request_id 
        ORDER BY r.created_at DESC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["id"]);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()){
            $user_requests[] = $row;
        }
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests</title>
    <link rel="stylesheet" href="background_template.css">
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
            max-width: 1200px;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
            resize: vertical;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
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
        .view-btn {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .view-btn:hover {
            background-color: #138496;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: #f8f9fa;
        }
        .tab.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php" class="active">My Requests</a>
        <a href="contact.php">Contact</a>
        <a href="chat.php">Chat Support</a>
        <?php if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]): ?>
        <a href="admin_panel.php" style="background-color: #ff9800;">Admin Panel</a>
        <?php endif; ?>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>My Requests</h1>
        
        <?php if(isset($_GET["success"]) && $_GET["success"] == 1): ?>
            <div class="alert alert-success">
                Your request has been submitted successfully. We will review it shortly.
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab(event, 'new-request')">New Request</div>
            <div class="tab" onclick="openTab(event, 'my-requests')">My Request History</div>
        </div>
        
        <div id="new-request" class="tab-content active">
            <div class="panel">
                <h2>Submit a New Request</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Request Type</label>
                        <select name="request_type" class="form-control <?php echo (!empty($request_type_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Request Type</option>
                            <option value="General Inquiry" <?php echo ($request_type == "General Inquiry") ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Technical Support" <?php echo ($request_type == "Technical Support") ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="Appointment Scheduling" <?php echo ($request_type == "Appointment Scheduling") ? 'selected' : ''; ?>>Appointment Scheduling</option>
                            <option value="Billing Issue" <?php echo ($request_type == "Billing Issue") ? 'selected' : ''; ?>>Billing Issue</option>
                            <option value="Feedback" <?php echo ($request_type == "Feedback") ? 'selected' : ''; ?>>Feedback</option>
                            <option value="Other" <?php echo ($request_type == "Other") ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span class="alert-danger"><?php echo $request_type_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $subject; ?>">
                        <span class="alert-danger"><?php echo $subject_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                        <span class="alert-danger"><?php echo $description_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low" <?php echo ($priority == "low") ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($priority == "medium" || empty($priority)) ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($priority == "high") ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn" value="Submit Request">
                    </div>
                </form>
            </div>
        </div>
        
        <div id="my-requests" class="tab-content">
            <div class="panel">
                <h2>My Request History</h2>
                <?php if(empty($user_requests)): ?>
                    <p>You haven't submitted any requests yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
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
                            <?php foreach($user_requests as $request): ?>
                            <tr>
                                <td><?php echo $request["request_id"]; ?></td>
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
                                    <a href="view_request.php?id=<?php echo $request["request_id"]; ?>" class="view-btn">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        
        // Hide all tab content
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        
        // Remove active class from all tabs
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        
        // Show the current tab and add active class
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    </script>
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>