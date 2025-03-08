<?php
// Initialize the session
session_start();

// Include maintenance mode check
require_once "maintenance_check.php";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
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
            align-items: center;
            padding: 40px 20px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        h2 {
            color: #4CAF50;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        p {
            margin: 15px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .section-title {
            font-weight: bold;
            font-size: 20px;
            color: #4CAF50;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .about-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #4CAF50;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f5f9f5;
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .feature-icon {
            color: #4CAF50;
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }
        .feature-text {
            flex: 1;
        }
        .feature-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .feature-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        .team-section {
            margin-top: 40px;
        }
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        /* Flip Card Styles */
        .team-member {
            perspective: 1000px;
            height: 280px;
            cursor: pointer;
        }
        .team-member-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        .team-member:hover .team-member-inner {
            transform: rotateY(180deg);
        }
        .team-member-front, .team-member-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .team-member-front {
            background-color: white;
        }
        .team-member-back {
            background-color: #4CAF50;
            color: white;
            transform: rotateY(180deg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .team-member img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #4CAF50;
        }
        .team-member h3 {
            margin: 10px 0 5px;
            color: #333;
        }
        .team-member-front p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .team-member-back h3 {
            color: white;
            margin-bottom: 15px;
        }
        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        .social-links a {
            color: white;
            font-size: 24px;
            transition: transform 0.3s ease;
        }
        .social-links a:hover {
            transform: scale(1.2);
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
            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="bg-container bg-about"></div>
    
    <div class="navbar" id="myNavbar">
        <a href="welcome.php">Home</a>
        <a href="about.php" class="active">About Us</a>
        <a href="services.php">Services</a>
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
            <h1>About LagroCare</h1>
            <p>LagroCare is a dedicated teleconsultation system designed to provide early detection of behavioral issues among students at Lagro High School. 
                Our mission is to create a safe, supportive, and accessible platform where students can receive professional guidance 
                on their mental and emotional well-being.</p>
            
            <div class="about-section">
                <div class="section-title">Our Purpose</div>
                <p>We recognize the importance of mental health in academic success. LagroCare serves as a bridge between students and qualified professionals, 
                ensuring that behavioral concerns are identified early and addressed effectively. Through secure virtual consultations, 
                we aim to provide timely intervention and personalized support.</p>
            </div>
            
            <div class="section-title">What We Offer</div>
            <ul class="features-list">
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <div class="feature-text">
                        <div class="feature-title">Confidential Teleconsultations</div>
                        <div class="feature-description">Speak with trusted professionals privately in a secure environment.</div>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-search"></i></div>
                    <div class="feature-text">
                        <div class="feature-title">Early Detection & Intervention</div>
                        <div class="feature-description">Identify behavioral issues before they escalate to more serious problems.</div>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="feature-text">
                        <div class="feature-title">Student-Centered Approach</div>
                        <div class="feature-description">Focused on well-being, academic performance, and personal growth.</div>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon"><i class="fas fa-laptop-medical"></i></div>
                    <div class="feature-text">
                        <div class="feature-title">Convenient & Secure Platform</div>
                        <div class="feature-description">Access support anytime, anywhere within the school community.</div>
                    </div>
                </li>
            </ul>
            
            <div class="team-section">
                <h2>Our Team</h2>
                <p>Meet the talented individuals who make our system successful:</p>
                
                <div class="team-grid">
                    <div class="team-member">
                        <div class="team-member-inner">
                            <div class="team-member-front">
                                <img src="ProjectMngr.jpg" alt="Team Member">
                                <h3>Pamela Kate Cacayan</h3>
                                <p>Project Manager</p>
                            </div>
                            <div class="team-member-back">
                                <h3>Connect with Pamela</h3>
                                <div class="social-links">
                                    <a href="https://www.facebook.com/kate.cacayan.3" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                    <a href="https://www.instagram.com/kate.es_cala/" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="mailto:#"><i class="fas fa-envelope"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="team-member-inner">
                            <div class="team-member-front">
                                <img src="LeadDev.jpg" alt="Team Member">
                                <h3>Alexander Jethro Hernandez</h3>
                                <p>Lead Developer</p>
                            </div>
                            <div class="team-member-back">
                                <h3>Connect with Alexander</h3>
                                <div class="social-links">
                                    <a href="https://www.facebook.com/hernandez.alexjethro" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                    <a href="https://www.instagram.com/ludexlex__/" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="mailto:hernandezalexanderjethro@gmail.com"><i class="fas fa-envelope"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="team-member-inner">
                            <div class="team-member-front">
                                <img src="Debugger.jpg" alt="Team Member">
                                <h3>Ferraren JP Bea</h3>
                                <p>Debugger</p>
                            </div>
                            <div class="team-member-back">
                                <h3>Connect with JP</h3>
                                <div class="social-links">
                                    <a href="https://www.facebook.com/ferrarenjp.bea" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="mailto:#"><i class="fas fa-envelope"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="team-member-inner">
                            <div class="team-member-front">
                                <img src="systemanalyst.png" alt="Team Member">
                                <h3>John Dave Rustia</h3>
                                <p>System Analyst</p>
                            </div>
                            <div class="team-member-back">
                                <h3>Connect with John</h3>
                                <div class="social-links">
                                    <a href="https://www.facebook.com/john.dave.rustia.2024" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="mailto:#"><i class="fas fa-envelope"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="team-member-inner">
                            <div class="team-member-front">
                                <img src="Adviser.jpg" alt="Team Member">
                                <h3>Richard Zabala</h3>
                                <p>Adviser</p>
                            </div>
                            <div class="team-member-back">
                                <h3>Connect with Richard</h3>
                                <div class="social-links">
                                    <a href="https://www.facebook.com/zabala.chard" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="mailto:#"><i class="fas fa-envelope"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>