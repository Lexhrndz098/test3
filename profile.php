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

// Check if user is an admin
$is_admin = false;
if(isset($_SESSION["id"])){
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_SESSION["id"]);
        if($stmt->execute()){
            $stmt->store_result();
            if($stmt->num_rows == 1){
                $stmt->bind_result($admin_status);
                $stmt->fetch();
                $is_admin = $admin_status == 1;
            }
        }
        $stmt->close();
    }
}

// Get user information
$user_info = array();
if(isset($_SESSION["id"])){
    // First, check if profile_picture column exists in users table
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if($check_column->num_rows == 0) {
        // Add profile_picture column if it doesn't exist
        $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER email");
    }
    
    $sql = "SELECT username, email, profile_picture, created_at FROM users WHERE id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_SESSION["id"]);
        if($stmt->execute()){
            $result = $stmt->get_result();
            $user_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Process profile update
$update_success = $update_error = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Handle profile picture upload
    if(isset($_POST["upload_picture"]) && isset($_FILES["profile_picture"])){
        $target_dir = "uploads/profile_pictures/";
        
        // Create directory if it doesn't exist
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $_SESSION["id"] . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        $upload_ok = 1;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if($check === false) {
            $update_error = "File is not an image.";
            $upload_ok = 0;
        }
        
        // Check file size (limit to 5MB)
        if($_FILES["profile_picture"]["size"] > 5000000) {
            $update_error = "Sorry, your file is too large. Maximum size is 5MB.";
            $upload_ok = 0;
        }
        
        // Allow only certain file formats
        if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
            $update_error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $upload_ok = 0;
        }
        
        // If everything is ok, try to upload file
        if($upload_ok == 1) {
            if(move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Update database with new profile picture path
                $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                if($stmt = $conn->prepare($sql)){
                    $stmt->bind_param("si", $target_file, $_SESSION["id"]);
                    if($stmt->execute()){
                        $user_info["profile_picture"] = $target_file;
                        $update_success = "Profile picture updated successfully.";
                    } else {
                        $update_error = "Error updating profile picture in database.";
                    }
                    $stmt->close();
                }
            } else {
                $update_error = "Sorry, there was an error uploading your file.";
            }
        }
    }
    
    // Handle profile information update
    if(isset($_POST["update_profile"])){
        $new_email = trim($_POST["email"]);
        $current_password = trim($_POST["current_password"]);
        $new_password = trim($_POST["new_password"]);
        $confirm_password = trim($_POST["confirm_password"]);
    }
    
    // Validate current password
    $valid_password = false;
    if(!empty($current_password)){
        $sql = "SELECT password FROM users WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $_SESSION["id"]);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $stmt->bind_result($hashed_password);
                    $stmt->fetch();
                    if(password_verify($current_password, $hashed_password)){
                        $valid_password = true;
                    }
                }
            }
            $stmt->close();
        }
    }
    
    if(!$valid_password){
        $update_error = "Current password is incorrect.";
    } else {
        // Handle email change requests
        if(!empty($new_email) && $new_email !== $user_info["email"]){
            // Check if there's already a pending request for this user
            $check_sql = "SELECT id FROM pending_email_changes WHERE user_id = ? AND status = 'pending'";
            if($check_stmt = $conn->prepare($check_sql)){
                $check_stmt->bind_param("i", $_SESSION["id"]);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if($check_stmt->num_rows > 0){
                    $update_error = "You already have a pending email change request. Please wait for admin approval.";
                } else {
                    // Insert new pending email change request
                    $sql = "INSERT INTO pending_email_changes (user_id, current_email, new_email) VALUES (?, ?, ?)";
                    if($stmt = $conn->prepare($sql)){
                        $stmt->bind_param("iss", $_SESSION["id"], $user_info["email"], $new_email);
                        if($stmt->execute()){
                            $update_success = "Your email change request has been submitted and is pending admin approval.";
                        } else {
                            $update_error = "Error submitting email change request: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
                $check_stmt->close();
            }
        }
        
        // Update password if provided
        if(!empty($new_password)){
            if($new_password === $confirm_password){
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                if($stmt = $conn->prepare($sql)){
                    $stmt->bind_param("si", $hashed_password, $_SESSION["id"]);
                    if($stmt->execute()){
                        $update_success = "Profile updated successfully.";
                    }
                    $stmt->close();
                }
            } else {
                $update_error = "New passwords do not match.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
        }
        .profile-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4CAF50;
            margin-bottom: 15px;
        }
        .profile-picture-upload {
            margin-top: 10px;
            text-align: center;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-bottom: 10px;
        }
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-button {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: inline-block;
            cursor: pointer;
        }
        h1, h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #666;
        }
        .info-value {
            flex: 1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-control {
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
    </style>
</head>
<body>
    <div class="bg-container"></div>
    
    <?php if($is_admin && isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true): ?>
    <!-- Admin Navigation Bar -->
    <div class="navbar" id="myNavbar">
        <a href="admin_panel.php">Dashboard</a>
        <a href="admin_requests.php">Requests</a>
        <a href="users.php">Users</a>
        <a href="admin_messages.php">Chat Support</a>
        <a href="admin_services.php">User Messages</a>
        <a href="admin_teleconsultation.php">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
        <a href="logout.php">Sign Out</a>
            <a href="profile.php" class="active username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
        </div>
    </div>
    <?php else: ?>
    <!-- Regular User Navigation Bar -->
    <div class="navbar" id="myNavbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php">My Requests</a>
        <a href="contact.php">Contact</a>
        <a href="chat.php">Chat Support</a>
        <?php if($is_admin): ?>
        <a href="admin_panel.php" style="background-color: #ff9800;">Admin Panel</a>
        <?php endif; ?>
        <div class="right">
        <a href="logout.php">Sign Out</a>
            <a href="profile.php" class="active">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
        </div>
    </div>
    <?php endif; ?>
    </div>

    <div class="content-wrapper">
        <div class="container">
            <?php if(!empty($update_success)): ?>
            <div class="alert alert-success"><?php echo $update_success; ?></div>
            <?php endif; ?>
            <?php if(!empty($update_error)): ?>
            <div class="alert alert-danger"><?php echo $update_error; ?></div>
            <?php endif; ?>

            <div class="profile-section">
                <h2>Profile Information</h2>
                <div class="profile-picture-container">
                    <?php if(!empty($user_info["profile_picture"])): ?>
                        <img src="<?php echo htmlspecialchars($user_info["profile_picture"]); ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150?text=<?php echo substr(htmlspecialchars($user_info["username"]), 0, 1); ?>" alt="Default Profile" class="profile-picture">
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="profile-picture-upload">
                        <div class="file-input-wrapper">
                            <span class="file-input-button">Choose File</span>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                        </div>
                        <div id="file-name-display">No file chosen</div>
                        <button type="submit" name="upload_picture" class="btn" style="margin-top: 10px;">Upload Picture</button>
                    </form>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info["username"]); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info["email"]); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Type:</span>
                    <span class="info-value"><?php echo $is_admin ? "Administrator" : "User"; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value"><?php echo date("F j, Y", strtotime($user_info["created_at"])); ?></span>
                </div>
            </div>

            <div class="profile-section">
                <h2>Update Profile</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_info["email"]); ?>">
                    </div>
                    <div class="form-group">
                        <label>Current Password (required for any changes)</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current password)</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>

            <?php if($is_admin): ?>
            <div class="profile-section">
                <h2>Administrative Options</h2>
                <p>As an administrator, you have access to the following functions:</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <a href="admin_panel.php" class="btn" style="text-align: center;">
                        <i class="fas fa-tachometer-alt" style="margin-right: 5px;"></i> Dashboard
                    </a>
                    <a href="users.php" class="btn" style="text-align: center;">
                        <i class="fas fa-users" style="margin-right: 5px;"></i> Manage Users
                    </a>
                    <a href="admin_requests.php" class="btn" style="text-align: center;">
                        <i class="fas fa-clipboard-list" style="margin-right: 5px;"></i> User Requests
                    </a>
                    <a href="admin_messages.php" class="btn" style="text-align: center;">
                        <i class="fas fa-envelope" style="margin-right: 5px;"></i> Messages
                    </a>
                    <a href="admin_chat.php" class="btn" style="text-align: center;">
                        <i class="fas fa-comments" style="margin-right: 5px;"></i> Chat Support
                    </a>
                    <a href="admin_teleconsultation.php" class="btn" style="text-align: center;">
                        <i class="fas fa-video" style="margin-right: 5px;"></i> Teleconsultation
                    </a>
                    <a href="approved_emails.php" class="btn" style="text-align: center;">
                        <i class="fas fa-envelope-open-text" style="margin-right: 5px;"></i> Approved Emails
                    </a>
                    <a href="settings.php" class="btn" style="text-align: center;">
                        <i class="fas fa-cog" style="margin-right: 5px;"></i> System Settings
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Display selected filename when user chooses a file
        document.getElementById('profile_picture').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name-display').textContent = fileName;
        });
    </script>
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>