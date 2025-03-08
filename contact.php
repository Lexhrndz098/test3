<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if user is an admin
$is_admin = false;
if(isset($_SESSION["id"])){
    require_once "db_connect.php";
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

// Initialize variables
$name = $email = $subject = $message = "";
$name_err = $email_err = $subject_err = $message_err = "";
$success_message = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate subject
    if(empty(trim($_POST["subject"]))){
        $subject_err = "Please enter a subject.";
    } else {
        $subject = trim($_POST["subject"]);
    }
    
    // Validate message
    if(empty(trim($_POST["message"]))){
        $message_err = "Please enter your message.";
    } else {
        $message = trim($_POST["message"]);
    }
    
    // Check input errors before sending email
    if(empty($name_err) && empty($email_err) && empty($subject_err) && empty($message_err)){
        // Insert the message into the database
        $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("issss", $_SESSION["id"], $name, $email, $subject, $message);
            
            if($stmt->execute()){
                $success_message = "Your message has been sent successfully!";
                $name = $email = $subject = $message = "";
            } else {
                $success_message = "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
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
            padding: 40px 20px;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .contact-info {
            margin-bottom: 30px;
        }
        .contact-info p {
            margin: 10px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .contact-info i {
            margin-right: 10px;
            color: #4CAF50;
            width: 20px;
            text-align: center;
        }
        .contact-form .form-group {
            margin-bottom: 15px;
        }
        .contact-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .contact-form textarea {
            height: 150px;
            resize: vertical;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .map-container {
            margin-top: 30px;
            height: 300px;
            width: 100%;
            background-color: #eee;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 4px;
        }
        /* Responsive navigation */
        @media screen and (max-width: 600px) {
            .navbar a:not(:first-child), .navbar .right {
                display: none;
            }
            .navbar a.icon {
                float: right;
                display: block;
            }
            .navbar.responsive {
                position: relative;
            }
            .navbar.responsive a.icon {
                position: absolute;
                right: 0;
                top: 0;
            }
            .navbar.responsive a {
                float: none;
                display: block;
                text-align: left;
            }
            .navbar.responsive .right {
                float: none;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="bg-container bg-contact"></div>
    
    <div class="navbar" id="myNavbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php">My Requests</a>
        <a href="contact.php" class="active">Contact</a>
        <a href="chat.php">Chat Support</a>
        <?php if($is_admin): ?>
        <a href="admin_panel.php" style="background-color: #ff9800;">Admin Panel</a>
        <?php endif; ?>
        <div class="right">
        <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <h1>Contact Us</h1>
            
            <div class="contact-info">
                <p><i class="fas fa-map-marker-alt"></i> P3G8+PJJ, Misa de Gallo, Novaliches, Quezon City, Metro Manila</p>
                <p><i class="fas fa-phone"></i> (02) 8939 1092</p>
                <p><i class="fas fa-envelope"></i> LagroHighschool@gmail.com</p>
                <p><i class="fas fa-clock"></i> Monday - Friday: 9:00 AM - 5:00 PM</p>
            </div>
            
            <?php if(!empty($success_message)): ?>
                <div style="color: green; margin-bottom: 20px;"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo $name; ?>">
                        <span style="color: red;"><?php echo $name_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $email; ?>">
                        <span style="color: red;"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" value="<?php echo $subject; ?>">
                        <span style="color: red;"><?php echo $subject_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message"><?php echo $message; ?></textarea>
                        <span style="color: red;"><?php echo $message_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn" value="Send Message">
                    </div>
                </form>
            </div>
            
            <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.748004087571!2d121.0665253!3d14.726834099999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b0635f5ffacf%3A0x7255ae7ce29c4de6!2sLagro%20High%20School!5e0!3m2!1sen!2sph!4v1741082781945!5m2!1sen!2sph" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>

    <script>
    function toggleNavbar() {
        var x = document.getElementById("myNavbar");
        if (x.className === "navbar") {
            x.className += " responsive";
        } else {
            x.className = "navbar";
        }
    }
    </script>
</body>
</html>