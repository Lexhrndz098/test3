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
$email = "";
$email_err = "";
$notification = "";

// Get user information
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];

// Check if user already has an approved email
$has_approved_email = false;
$approved_email = "";

$sql = "SELECT email FROM approved_emails WHERE email IN (SELECT email FROM users WHERE id = ?) AND is_used = 0";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()){
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $stmt->bind_result($approved_email);
            $stmt->fetch();
            $has_approved_email = true;
        }
    }
    $stmt->close();
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        } else {
            $email = trim($_POST["email"]);
            
            // Check if email is in the approved list
            $sql = "SELECT id, is_used FROM approved_emails WHERE email = ?";
            
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("s", $param_email);
                $param_email = $email;
                
                if($stmt->execute()){
                    $stmt->store_result();
                    
                    if($stmt->num_rows == 0){
                        $email_err = "This email is not approved for registration. Please contact your administrator.";
                    } else {
                        $stmt->bind_result($approved_id, $is_used);
                        $stmt->fetch();
                        
                        if($is_used){
                            $email_err = "This approved email has already been used for registration.";
                        } else {
                            // Update user's email
                            $stmt->close();
                            
                            $sql = "UPDATE users SET email = ? WHERE id = ?";
                            if($stmt = $conn->prepare($sql)){
                                $stmt->bind_param("si", $param_email, $param_user_id);
                                $param_email = $email;
                                $param_user_id = $user_id;
                                
                                if($stmt->execute()){
                                    // Update approved_emails table to mark email as used
                                    $stmt->close();
                                    
                                    $sql = "UPDATE approved_emails SET is_used = 1 WHERE email = ?";
                                    if($stmt = $conn->prepare($sql)){
                                        $stmt->bind_param("s", $param_email);
                                        $param_email = $email;
                                        
                                        if($stmt->execute()){
                                            // Redirect to registration page
                                            header("location: register.php");
                                            exit;
                                        }
                                    }
                                } else {
                                    $email_err = "Something went wrong. Please try again later.";
                                }
                            }
                        }
                    }
                } else {
                    $email_err = "Something went wrong. Please try again later.";
                }
                
                $stmt->close();
            }
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
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
        .notification {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .logout-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Email Verification</h2>
        
        <div class="notification">
            <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</p>
            <p>Before you can proceed to registration, you need to verify your approved email address.</p>
            <?php if($has_approved_email): ?>
                <p>Good news! You have an approved email: <strong><?php echo htmlspecialchars($approved_email); ?></strong></p>
                <p>Please enter this email below to continue with registration.</p>
            <?php else: ?>
                <p>You don't have an approved email yet. Please contact your administrator to get an approved email.</p>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($email_err)): ?>
            <div class="error"><?php echo $email_err; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Enter Your Approved Email</label>
                <input type="email" name="email" value="<?php echo $email; ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Verify Email">
            </div>
            <div class="logout-link">
                <p><a href="logout.php">Logout</a></p>
            </div>
        </form>
    </div>
</body>
</html>