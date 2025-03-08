<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="icon" href="backgrounds/lagro_logo.png" type="image/png">
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
            max-width: 400px;
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
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="backgrounds/lagro_logo.png" alt="Lagro Logo" style="width: 80px; height: auto;">
        </div>
        <h2>Admin Login</h2>
        <?php
        // Initialize the session
        session_start();
        
        // Check if the admin is already logged in
        if(isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true){
            header("location: admin_panel.php");
            exit;
        }
        
        // Include database connection
        require_once "db_connect.php";
        
        // Define variables and initialize with empty values
        $username = $password = "";
        $username_err = $password_err = $login_err = "";
        
        // Processing form data when form is submitted
        if($_SERVER["REQUEST_METHOD"] == "POST"){
            // Validate username
            if(empty(trim($_POST["username"]))){
                $username_err = "Please enter username.";
            } else{
                $username = trim($_POST["username"]);
            }
            
            // Validate password
            if(empty(trim($_POST["password"]))){
                $password_err = "Please enter your password.";
            } else{
                $password = trim($_POST["password"]);
            }
            
            // Validate credentials
            if(empty($username_err) && empty($password_err)){
                // Check if username exists and is an admin
                $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ? AND is_admin = 1";
                
                if($stmt = $conn->prepare($sql)){
                    $stmt->bind_param("s", $param_username);
                    $param_username = $username;
                    
                    if($stmt->execute()){
                        $stmt->store_result();
                        
                        if($stmt->num_rows == 1){                    
                            $stmt->bind_result($id, $username, $hashed_password, $is_admin);
                            if($stmt->fetch()){
                                if(password_verify($password, $hashed_password)){
                                    session_start();
                                    
                                    $_SESSION["admin_loggedin"] = true;
                                    $_SESSION["admin_username"] = $username;
                                    $_SESSION["admin_id"] = $id;
                                    
                                    header("location: admin_panel.php");
                                } else{
                                    $login_err = "Invalid username or password.";
                                }
                            }
                        } else{
                            $login_err = "Invalid username or password.";
                        }
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                    }
                    $stmt->close();
                }
            }
        }
        ?>
        
        <?php 
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
        </form>
    </div>
</body>
</html>