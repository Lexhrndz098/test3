<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}

// Include database connection
require_once "db_connect.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
$notification = "";
$has_approved_email = false;
$approved_email = "";

// Check if there's a username in the URL (for returning users)
if(isset($_GET["username"]) && !empty($_GET["username"])){
    $username = trim($_GET["username"]);
    
    // Check if this username has any approved emails
    $sql = "SELECT ae.email FROM approved_emails ae 
            JOIN users u ON u.username = ? 
            WHERE ae.email = u.email AND ae.is_used = 0";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("s", $username);
        
        if($stmt->execute()){
            $stmt->store_result();
            
            if($stmt->num_rows > 0){
                $stmt->bind_result($approved_email);
                $stmt->fetch();
                $has_approved_email = true;
                $notification = "<div class='notification-bubble'>You have an approved email: <strong>" . htmlspecialchars($approved_email) . "</strong>. <a href='check_approved_email.php'>Click here</a> to proceed with registration.</div>";
            }
        }
        $stmt->close();
    }
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Store result
                $stmt->store_result();
                
                // Check if username exists, if yes then verify password
                if($stmt->num_rows == 1){                    
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password, $is_admin);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["is_admin"] = $is_admin;
                            
                            // Check if user is an admin
                            if($is_admin){
                                // Set admin session variables
                                $_SESSION["admin_loggedin"] = true;
                                $_SESSION["admin_username"] = $username;
                                $_SESSION["admin_id"] = $id;
                                
                                // Redirect admin to admin panel
                                header("location: admin_panel.php");
                            } else {
                                // Check if user already has an approved email
                                $has_approved_email = false;
                                $sql_check = "SELECT email FROM users WHERE id = ? AND email IN (SELECT email FROM approved_emails)"; 
                                if($stmt_check = $conn->prepare($sql_check)){
                                    $stmt_check->bind_param("i", $id);
                                    if($stmt_check->execute()){
                                        $stmt_check->store_result();
                                        if($stmt_check->num_rows > 0){
                                            $has_approved_email = true;
                                        }
                                    }
                                    $stmt_check->close();
                                }
                                
                                // If user has an approved email, redirect to welcome page
                                // Otherwise, redirect to email verification page
                                if($has_approved_email){
                                    header("location: welcome.php");
                                } else {
                                    // Redirect regular user to check_approved_email page
                                    header("location: check_approved_email.php");
                                }
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="background_template.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="backgrounds/lagro_logo.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
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
        input[type="text"],
        input[type="password"] {
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
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
        .notification-bubble {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            border-radius: 4px;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        /* Custom login background styling */
        .bg-login {
            background-image: url('backgrounds/login_bg.jpg');
            opacity: 0.2; /* Slightly more visible than default */
        }
    </style>
</head>
<body>
    <!-- Background container -->
    <div class="bg-container bg-login"></div>
    
    <div class="container">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="backgrounds/lagro_logo.png" alt="Lagro Logo" style="width: 80px; height: auto;">
        </div>
        <h2>Login</h2>
        
        <?php 
        if(!empty($notification)){
            echo $notification;
        }
        
        if(!empty($login_err)){
            echo '<div class="error">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo $username; ?>">
                <span class="error"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p class="register-link">Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>
</html>