<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 40px;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .service-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .service-card h3 {
            color: #4CAF50;
            margin-top: 0;
        }
        .service-card p {
            color: #666;
            line-height: 1.6;
        }
        .service-icon {
            font-size: 40px;
            color: #4CAF50;
            margin-bottom: 15px;
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
            .services-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="bg-container bg-services"></div>
    
    <div class="navbar" id="myNavbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php" class="active">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php">My Requests</a>
        <a href="contact.php">Contact</a>
        <a href="chat.php">Chat Support</a>
        <?php if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]): ?>
        <a href="admin_panel.php" style="background-color: #ff9800;">Admin Panel</a>
        <?php endif; ?>
        <div class="right">
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
            <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <h1>Our Services</h1>
            
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-user-md service-icon"></i>
                    <h3>Individual Counseling</h3>
                    <p>One-on-one virtual counseling sessions with qualified professionals to address personal concerns, emotional challenges, and behavioral issues.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-brain service-icon"></i>
                    <h3>Behavioral Assessment</h3>
                    <p>Comprehensive evaluation of behavioral patterns to identify potential issues early and develop appropriate intervention strategies.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-graduation-cap service-icon"></i>
                    <h3>Academic Support</h3>
                    <p>Guidance for academic-related stress, study habits, and learning strategies to improve educational outcomes and reduce school-related anxiety.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-hands-helping service-icon"></i>
                    <h3>Crisis Intervention</h3>
                    <p>Immediate support for students experiencing acute emotional distress, providing stabilization and connecting them with appropriate resources.</p>
                </div>
                
                <div class="service-card">
                    <i class="fas fa-chalkboard-teacher service-icon"></i>
                    <h3>Parent-Teacher Consultation</h3>
                    <p>Collaborative sessions between parents, teachers, and counselors to develop consistent support strategies for students across school and home environments.</p>
                </div>
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