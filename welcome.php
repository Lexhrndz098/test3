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
    <title>Welcome</title>
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
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 650px;
            text-align: center;
            border-top: 4px solid #4CAF50;
        }
        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        p {
            margin: 20px 0;
            font-size: 18px;
            line-height: 1.6;
            color: #444;
        }
        
        .welcome-intro {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        
        .key-points {
            background-color: #f5f9f5;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
            text-align: left;
            margin-top: 25px;
        }
        
        .key-point-item {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
        }
        
        .key-point-icon {
            color: #4CAF50;
            margin-right: 10px;
            font-size: 20px;
        }
        
        .call-to-action {
            font-weight: bold;
            margin-top: 25px;
            font-size: 20px;
            color: #333;
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
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
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
        /* Chatbot Styles */
        .chat-bot-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .chat-bot-button:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }
        .chat-bot-icon {
            font-size: 24px;
        }
        .chat-container {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 450px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
        }
        .chat-header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .chat-close {
            cursor: pointer;
            font-size: 18px;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            font-size: 14px;
        }
        .bot-message {
            background-color: #f1f1f1;
            color: #333;
            border-top-left-radius: 5px;
            align-self: flex-start;
        }
        .user-message {
            background-color: #4CAF50;
            color: white;
            border-top-right-radius: 5px;
            align-self: flex-end;
            margin-left: auto;
        }
        .chat-input-container {
            display: flex;
            padding: 10px;
            border-top: 1px solid #eee;
        }
        .chat-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }
        .chat-send {
            background-color: #4CAF50;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .chat-send:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="bg-container bg-welcome"></div>
    
    <div class="navbar" id="myNavbar">
        <a href="welcome.php" class="active">Home</a>
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
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <h1>Welcome to LagroCare</h1>
            
            <div class="welcome-intro">
                <p>At <b>LagroCare</b>, we are dedicated to ensuring the well-being of our students and faculty by <b>providing accessible, confidential, and reliable</b>
                teleconsultation services. Whether you need <b>guidance on mental health, medical advice, or emotional support</b>, our professional team is here to help.</p>
            </div>
            
            <div class="key-points">
                <div class="key-point-item">
                    <span class="key-point-icon">🔒</span>
                    <span><b>Secure & Private</b> – Your health matters, and so does your privacy.</span>
                </div>
                
                <div class="key-point-item">
                    <span class="key-point-icon">🏠</span>
                    <span><b>Easy & Convenient</b> – Get expert consultations from the comfort of your home or school.</span>
                </div>
                
                <div class="key-point-item">
                    <span class="key-point-icon">👨‍👩‍👧‍👦</span>
                    <span><b>Support for Students & Staff</b> – Because health and learning go hand in hand.</span>
                </div>
            </div>
            
            <p class="call-to-action">Start your consultation today and take a step toward a healthier school community!</p>
        </div>
    </div>

    <!-- Chatbot UI -->
    <div class="chat-bot-button" id="chatBotButton">
        <i class="fas fa-comment chat-bot-icon"></i>
    </div>
    
    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <h3>Chat Support</h3>
            <span class="chat-close" id="chatClose">&times;</span>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be added here dynamically -->
        </div>
        <div class="chat-input-container">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
            <button class="chat-send" id="chatSend">
                <i class="fas fa-paper-plane"></i>
            </button>
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

    // Chatbot functionality
    document.addEventListener('DOMContentLoaded', function() {
        const chatBotButton = document.getElementById('chatBotButton');
        const chatContainer = document.getElementById('chatContainer');
        const chatClose = document.getElementById('chatClose');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const chatSend = document.getElementById('chatSend');
        
        // Open chat when button is clicked
        chatBotButton.addEventListener('click', function() {
            chatContainer.style.display = 'flex';
            chatBotButton.style.display = 'none';
            // Add welcome message if chat is empty
            if (chatMessages.children.length === 0) {
                addBotMessage('Hello! How can I help you today?');
            }
        });
        
        // Close chat when X is clicked
        chatClose.addEventListener('click', function() {
            chatContainer.style.display = 'none';
            chatBotButton.style.display = 'flex';
        });
        
        // Send message when send button is clicked
        chatSend.addEventListener('click', sendMessage);
        
        // Send message when Enter key is pressed
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message !== '') {
                addUserMessage(message);
                chatInput.value = '';
                // Process the message and get a response
                processMessage(message);
            }
        }
        
        function addUserMessage(message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', 'user-message');
            messageElement.textContent = message;
            chatMessages.appendChild(messageElement);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function addBotMessage(message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', 'bot-message');
            messageElement.textContent = message;
            chatMessages.appendChild(messageElement);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function processMessage(message) {
            // Simple response logic - can be expanded or connected to a backend
            message = message.toLowerCase();
            
            setTimeout(() => {
                // Basic conversation responses
                if (message.includes('hello') || message.includes('hi') || message.includes('hey')) {
                    addBotMessage('Hello! How can I assist you today?');
                } else if (message.includes('help')) {
                    addBotMessage('I can help you with information about our services, teleconsultation, or contact details. I can also provide guidance on common mental health concerns. What would you like to know?');
                } else if (message.includes('service')) {
                    addBotMessage('We offer various healthcare services including mental health support, medical consultations, and wellness programs. You can check our Services page for more details.');
                } else if (message.includes('teleconsultation') || message.includes('appointment') || message.includes('consult')) {
                    addBotMessage('You can schedule a teleconsultation through our Teleconsultation page. Would you like me to guide you there?');
                } else if (message.includes('contact') || message.includes('phone') || message.includes('email')) {
                    addBotMessage('You can find our contact information on the Contact page. We are available Monday to Friday, 9AM to 5PM.');
                } 
                // Mental health related responses
                else if (message.includes('anxiety') || message.includes('anxious') || message.includes('worried') || message.includes('panic')) {
                    addBotMessage('It sounds like you might be experiencing anxiety. Some helpful strategies include: deep breathing exercises, progressive muscle relaxation, mindfulness meditation, and regular physical activity. If your anxiety persists, consider scheduling a teleconsultation with one of our mental health professionals.');
                } else if (message.includes('depress') || message.includes('sad') || message.includes('hopeless') || message.includes('unmotivated')) {
                    addBotMessage('I understand you might be feeling down. Some self-care strategies that may help include: maintaining a regular daily routine, physical exercise, connecting with supportive friends or family, and engaging in activities you usually enjoy. Remember that depression is treatable, and our mental health professionals are here to help. Would you like information about scheduling a consultation?');
                } else if (message.includes('stress') || message.includes('overwhelm') || message.includes('pressure')) {
                    addBotMessage('Managing stress is important for your wellbeing. Try these techniques: prioritize tasks and break them into smaller steps, practice time management, engage in regular physical activity, and make time for relaxation. Our counselors can also provide personalized stress management strategies through a teleconsultation.');
                } else if (message.includes('sleep') || message.includes('insomnia') || message.includes('can\'t sleep') || message.includes('trouble sleeping')) {
                    addBotMessage('Good sleep is essential for mental health. Try establishing a regular sleep schedule, creating a relaxing bedtime routine, limiting screen time before bed, and avoiding caffeine in the afternoon and evening. If sleep problems persist, our healthcare providers can help identify underlying causes and suggest appropriate treatments.');
                } else if (message.includes('lonely') || message.includes('alone') || message.includes('isolated') || message.includes('no friends')) {
                    addBotMessage('Feeling lonely or isolated can be difficult. Consider joining student groups or clubs, volunteering, or participating in campus activities to connect with others. Our counselors can also provide support and strategies for building meaningful relationships. Would you like information about our support groups?');
                } else if (message.includes('study') || message.includes('exam') || message.includes('academic') || message.includes('school stress')) {
                    addBotMessage('Academic pressure can be challenging. Try breaking your work into manageable chunks, creating a study schedule, taking regular breaks, and using active learning techniques. Our academic counselors can provide additional strategies tailored to your specific needs.');
                } else if (message.includes('grief') || message.includes('loss') || message.includes('died') || message.includes('death')) {
                    addBotMessage('I\'m sorry to hear about your loss. Grief is a natural response, and everyone experiences it differently. Be patient with yourself, seek support from friends and family, and consider joining a grief support group. Our counselors are also available to provide compassionate support during this difficult time.');
                } else if (message.includes('trauma') || message.includes('abuse') || message.includes('assault')) {
                    addBotMessage('I\'m very sorry you\'re going through this. Your safety and wellbeing are important. Please consider speaking with one of our counselors who specializes in trauma support. They can provide confidential assistance and resources. Would you like information about scheduling an urgent consultation?');
                } else if (message.includes('suicide') || message.includes('kill myself') || message.includes('want to die') || message.includes('end my life')) {
                    addBotMessage('I\'m concerned about what you\'re sharing. Please know that you\'re not alone, and help is available. Please contact our Crisis support line immediately at [0966-351-4518] or text HOME to 1800-1888-1553 to reach the Crisis Text Line. Our counselors are also available for urgent consultations. Your life matters.');
                } 
                // Conversation continuity
                else if (message.includes('thank')) {
                    addBotMessage('You\'re welcome! Is there anything else I can help you with?');
                } else if (message.includes('bye') || message.includes('goodbye')) {
                    addBotMessage('Goodbye! Feel free to chat again if you need assistance. Take care of yourself!');
                } else {
                    addBotMessage('I\'m not sure I understand. Could you please rephrase or ask about our services, mental health support, teleconsultation, or contact information?');
                }
            }, 500); // Slight delay to make it feel more natural
        }
    });
    </script>
</body>
</html>