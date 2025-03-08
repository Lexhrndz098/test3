<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .maintenance-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
        }
        h1 {
            color: #e74c3c;
            margin-top: 0;
        }
        p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #e74c3c;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">🛠️</div>
        <h1>Site Under Maintenance</h1>
        <p>We're currently performing scheduled maintenance on our website to improve your experience.</p>
        <p>We apologize for any inconvenience this may cause. Please check back soon!</p>
        <p><strong>Expected completion:</strong> Shortly</p>
        
        <?php
        // Include database connection and settings functions if not already included
        if (!function_exists('get_setting')) {
            require_once "db_connect.php";
            require_once "settings_functions.php";
        }
        
        // Get admin email from settings
        $admin_email = get_setting('admin_email', 'admin@example.com');
        ?>
        
        <p>If you need immediate assistance, please contact us at: <a href="mailto:<?php echo htmlspecialchars($admin_email); ?>"><?php echo htmlspecialchars($admin_email); ?></a></p>
        
        <a href="login.php" class="btn">Try Login</a>
    </div>
</body>
</html>