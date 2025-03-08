<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

// Include database connection and settings functions
require_once "db_connect.php";
require_once "settings_functions.php";

// Process settings update if form is submitted
$update_message = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["update_settings"])){
        // Process general settings update
        $settings = [
            'site_title' => $_POST['site_title'],
            'admin_email' => $_POST['admin_email'],
            'timezone' => $_POST['timezone'],
            'maintenance_mode' => $_POST['maintenance_mode']
        ];
        
        if(update_settings($settings)){
            $update_message = "<div class=\"panel\" style=\"background-color: #dff0d8; color: #3c763d;\">General settings have been successfully updated.</div>";
        } else {
            $update_message = "<div class=\"panel\" style=\"background-color: #f2dede; color: #a94442;\">Error updating general settings.</div>";
        }
    } elseif(isset($_POST["update_security"])){
        // Process security settings update
        $settings = [
            'login_attempts' => $_POST['login_attempts'],
            'session_timeout' => $_POST['session_timeout'],
            'password_policy' => $_POST['password_policy']
        ];
        
        if(update_settings($settings)){
            $update_message = "<div class=\"panel\" style=\"background-color: #dff0d8; color: #3c763d;\">Security settings have been successfully updated.</div>";
        } else {
            $update_message = "<div class=\"panel\" style=\"background-color: #f2dede; color: #a94442;\">Error updating security settings.</div>";
        }
    }
}

// Get navigation active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
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
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
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
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php" class="active">Settings</a>
        <div class="right">
        <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <h1>System Settings</h1>
        
        <?php echo $update_message; ?>
        
        <div class="panel">
            <h2>General Settings</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars(get_setting('site_title', 'Healthcare Portal')); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars(get_setting('admin_email', 'admin@example.com')); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select id="timezone" name="timezone">
                        <?php $current_timezone = get_setting('timezone', 'UTC'); ?>
                        <option value="UTC" <?php echo $current_timezone == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        <option value="America/New_York" <?php echo $current_timezone == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                        <option value="America/Chicago" <?php echo $current_timezone == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                        <option value="America/Denver" <?php echo $current_timezone == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                        <option value="America/Los_Angeles" <?php echo $current_timezone == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_mode">Maintenance Mode</label>
                    <select id="maintenance_mode" name="maintenance_mode">
                        <?php $maintenance_mode = get_setting('maintenance_mode', '0'); ?>
                        <option value="0" <?php echo $maintenance_mode == '0' ? 'selected' : ''; ?>>Off</option>
                        <option value="1" <?php echo $maintenance_mode == '1' ? 'selected' : ''; ?>>On</option>
                    </select>
                </div>
                
                <button type="submit" name="update_settings" class="btn">Save Settings</button>
            </form>
        </div>
        
        <div class="panel">
            <h2>Security Settings</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="login_attempts">Max Login Attempts</label>
                    <input type="number" id="login_attempts" name="login_attempts" value="<?php echo htmlspecialchars(get_setting('login_attempts', '5')); ?>" min="1" max="10" required>
                </div>
                
                <div class="form-group">
                    <label for="session_timeout">Session Timeout (minutes)</label>
                    <input type="number" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars(get_setting('session_timeout', '30')); ?>" min="5" max="120" required>
                </div>
                
                <div class="form-group">
                    <label for="password_policy">Password Policy</label>
                    <select id="password_policy" name="password_policy">
                        <?php $password_policy = get_setting('password_policy', 'medium'); ?>
                        <option value="low" <?php echo $password_policy == 'low' ? 'selected' : ''; ?>>Low - At least 6 characters</option>
                        <option value="medium" <?php echo $password_policy == 'medium' ? 'selected' : ''; ?>>Medium - At least 8 characters with numbers</option>
                        <option value="high" <?php echo $password_policy == 'high' ? 'selected' : ''; ?>>High - At least 10 characters with numbers and special characters</option>
                    </select>
                </div>
                
                <button type="submit" name="update_security" class="btn">Save Security Settings</button>
            </form>
        </div>
    </div>
</body>
</html>