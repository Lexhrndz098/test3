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

// Get user information
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];

// Check if there's an active conversation for this user
$active_conversation_id = null;
$sql = "SELECT conversation_id FROM chat_conversations WHERE user_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()){
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $stmt->bind_result($active_conversation_id);
            $stmt->fetch();
        }
    }
    $stmt->close();
}

// Handle new conversation creation
if(isset($_POST["start_conversation"]) && !$active_conversation_id){
    $sql = "INSERT INTO chat_conversations (user_id) VALUES (?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $user_id);
        if($stmt->execute()){
            $active_conversation_id = $conn->insert_id;
        }
        $stmt->close();
    }
    
    // Redirect to prevent form resubmission
    header("location: chat.php");
    exit;
}

// Handle AJAX message sending
if(isset($_POST["ajax_send_message"]) && !empty(trim($_POST["message"])) && isset($_POST["conversation_id"])){
    $message = trim($_POST["message"]);
    $conv_id = $_POST["conversation_id"];
    $response = array('success' => false);
    
    $sql = "INSERT INTO chat_messages (conversation_id, sender_id, is_admin, message) VALUES (?, ?, 0, ?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("iis", $conv_id, $user_id, $message);
        if($stmt->execute()){
            // Update conversation timestamp
            $update_sql = "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?";
            if($update_stmt = $conn->prepare($update_sql)){
                $update_stmt->bind_param("i", $conv_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            $response['success'] = true;
            $response['message_id'] = $conn->insert_id;
            $response['timestamp'] = date('Y-m-d H:i:s');
        }
        $stmt->close();
    }
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle regular form message sending
if(isset($_POST["send_message"]) && !empty(trim($_POST["message"])) && $active_conversation_id){
    $message = trim($_POST["message"]);
    
    $sql = "INSERT INTO chat_messages (conversation_id, sender_id, is_admin, message) VALUES (?, ?, 0, ?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("iis", $active_conversation_id, $user_id, $message);
        if($stmt->execute()){
            // Update conversation timestamp
            $update_sql = "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?";
            if($update_stmt = $conn->prepare($update_sql)){
                $update_stmt->bind_param("i", $active_conversation_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $stmt->close();
    }
    
    // Redirect to prevent form resubmission
    header("location: chat.php");
    exit;
}

// Handle conversation closing
if(isset($_POST["close_conversation"]) && $active_conversation_id){
    $sql = "UPDATE chat_conversations SET status = 'closed' WHERE conversation_id = ? AND user_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $active_conversation_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $active_conversation_id = null;
    
    // Redirect to prevent form resubmission
    header("location: chat.php");
    exit;
}

// Get chat messages if there's an active conversation
$messages = [];
if($active_conversation_id){
    $sql = "SELECT m.message_id, m.sender_id, m.is_admin, m.message, m.created_at, u.username 
           FROM chat_messages m 
           JOIN users u ON m.sender_id = u.id 
           WHERE m.conversation_id = ? 
           ORDER BY m.created_at ASC";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $active_conversation_id);
        if($stmt->execute()){
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()){
                $messages[] = $row;
            }
        }
        $stmt->close();
    }
    
    // Mark all messages as read
    $sql = "UPDATE chat_messages SET is_read = 1 
           WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $active_conversation_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support</title>
    <link rel="stylesheet" href="background_template.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .chat-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 70vh;
        }
        .chat-header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            position: relative;
        }
        .message .time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
            display: block;
        }
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .message .sender {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .message-content {
            margin-top: 5px;
        }
        .admin-message {
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
        .start-chat-container {
            text-align: center;
            padding: 40px 20px;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
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
        .btn-danger {
            background-color: #f44336;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .no-messages {
            text-align: center;
            color: #999;
            margin: 20px 0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .back-link i {
            margin-right: 8px;
            font-size: 18px;
        }
        .back-link:hover {
            background-color: #45a049;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            text-decoration: none;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-online {
            background-color: #4CAF50;
        }
        .status-offline {
            background-color: #f44336;
        }
        .message-sending {
            opacity: 0.7;
        }
        .message-error {
            border: 1px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php">Teleconsultation</a>
        <a href="request.php">My Requests</a>
        <a href="contact.php">Contact</a>
        <a href="chat.php" class="active">Chat Support</a>
        <?php if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]): ?>
        <a href="admin_panel.php" style="background-color: #ff9800;">Admin Panel</a>
        <?php endif; ?>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <a href="welcome.php" class="back-link"><i class="fas fa-home"></i> Back to Home</a>
        
        <?php if(!$active_conversation_id): ?>
            <div class="start-chat-container">
                <h2>Start a Chat with Support</h2>
                <p>Our support team is ready to assist you with any questions or issues you may have.</p>
                <form method="post" action="">
                    <button type="submit" name="start_conversation" class="btn">Start Chat</button>
                </form>
            </div>
        <?php else: ?>
            <div class="chat-container">
                <div class="chat-header">
                    <h2><span class="status-indicator status-online"></span> Chat Support</h2>
                    <form method="post" action="">
                        <button type="submit" name="close_conversation" class="btn btn-danger" onclick="return confirm('Are you sure you want to end this chat session?');">End Chat</button>
                    </form>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if(empty($messages)): ?>
                        <div class="no-messages">No messages yet. Start the conversation!</div>
                    <?php else: ?>
                        <?php foreach($messages as $message): ?>
                            <div class="message <?php echo $message['is_admin'] ? 'admin-message' : 'user-message'; ?>">
                                <div class="sender"><?php echo htmlspecialchars($message['username']); ?></div>
                                <div class="message-content"><?php echo htmlspecialchars($message['message']); ?></div>
                                <span class="time"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="post" action="" id="chatForm" class="chat-input-container">
                    <input type="hidden" name="conversation_id" value="<?php echo $active_conversation_id; ?>">
                    <input type="text" name="message" id="messageInput" class="chat-input" placeholder="Type your message..." required>
                    <button type="submit" name="send_message" class="chat-send"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
            
            <script>
                // Scroll to bottom of chat messages
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Set up WebSocket connection for real-time updates
                const userId = <?php echo $user_id; ?>;
                const username = "<?php echo $username; ?>";
                const conversationId = <?php echo $active_conversation_id; ?>;
                const ip = window.location.hostname;
                let socket;
                
                // Handle form submission via AJAX to prevent page reload
                document.getElementById('chatForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const input = document.getElementById('messageInput');
                    const message = input.value.trim();
                    
                    if (message) {
                        // Create temporary message element (optimistic UI update)
                        const tempMessageId = 'temp-' + Date.now();
                        const messageDiv = document.createElement('div');
                        messageDiv.id = tempMessageId;
                        messageDiv.className = 'message user-message message-sending';
                        
                        const messageContentDiv = document.createElement('div');
                        messageContentDiv.className = 'message-content';
                        messageContentDiv.textContent = message;
                        messageDiv.appendChild(messageContentDiv);
                        
                        const timeSpan = document.createElement('span');
                        timeSpan.className = 'time';
                        const now = new Date();
                        timeSpan.textContent = now.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
                        messageDiv.appendChild(timeSpan);
                        
                        chatMessages.appendChild(messageDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        
                        // Save message via AJAX to ensure database persistence
                        const formData = new FormData();
                        formData.append('ajax_send_message', 'true');
                        formData.append('message', message);
                        formData.append('conversation_id', conversationId);
                        
                        fetch('chat.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Message saved successfully
                                const tempMessage = document.getElementById(tempMessageId);
                                if (tempMessage) {
                                    tempMessage.classList.remove('message-sending');
                                    tempMessage.id = 'msg-' + data.message_id;
                                }
                                
                                // Send message via WebSocket for real-time updates
                                if (socket && socket.readyState === WebSocket.OPEN) {
                                    socket.send(JSON.stringify({
                                        type: 'message',
                                        conversationId: conversationId,
                                        senderId: userId,
                                        senderName: username,
                                        message: message,
                                        messageId: data.message_id,
                                        timestamp: data.timestamp
                                    }));
                                }
                            } else {
                                // Message failed to save
                                const tempMessage = document.getElementById(tempMessageId);
                                if (tempMessage) {
                                    tempMessage.classList.add('message-error');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error saving message:', error);
                            const tempMessage = document.getElementById(tempMessageId);
                            if (tempMessage) {
                                tempMessage.classList.add('message-error');
                            }
                        });
                        
                        // Clear input
                        input.value = '';
                    }
                });
                
                function connectWebSocket() {
                    socket = new WebSocket(`ws://${ip}:3001`);
                    
                    socket.onopen = function() {
                        console.log('WebSocket connected');
                        // Register user with the WebSocket server
                        socket.send(JSON.stringify({
                            type: 'register',
                            userId: userId,
                            isAdmin: false
                        }));
                        
                        // Notify server about active conversation
                        socket.send(JSON.stringify({
                            type: 'start_conversation',
                            conversationId: conversationId,
                            userId: userId,
                            username: username
                        }));
                    };
                    
                    socket.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        console.log('Message received:', data);
                        
                        if (data.type === 'message') {
                            // Check if this is our own message that we already displayed
                            const existingMsg = document.getElementById('msg-' + data.messageId);
                            if (existingMsg) {
                                return; // Skip if we already have this message displayed
                            }
                            
                            // Add new message to chat
                            const messageDiv = document.createElement('div');
                            messageDiv.id = 'msg-' + data.messageId;
                            messageDiv.className = `message ${data.isAdmin ? 'admin-message' : 'user-message'}`;
                            
                            const senderDiv = document.createElement('div');
                            senderDiv.className = 'sender';
                            senderDiv.textContent = data.senderName;
                            messageDiv.appendChild(senderDiv);
                            
                            const messageContentDiv = document.createElement('div');
                            messageContentDiv.className = 'message-content';
                            messageContentDiv.textContent = data.message;
                            messageDiv.appendChild(messageContentDiv);
                            
                            const timeSpan = document.createElement('span');
                            timeSpan.className = 'time';
                            const date = new Date(data.timestamp);
                            timeSpan.textContent = date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
                            messageDiv.appendChild(timeSpan);
                            
                            chatMessages.appendChild(messageDiv);
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            
                            // Play notification sound
                            const audio = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU');
                            audio.play().catch(e => console.log('Audio play failed:', e));
                        } else if (data.type === 'admin_joined') {
                            // Show admin joined notification
                            const notificationDiv = document.createElement('div');
                            notificationDiv.className = 'no-messages';
                            notificationDiv.textContent = `${data.adminName} has joined the chat.`;
                            chatMessages.appendChild(notificationDiv);
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        } else if (data.type === 'admin_left') {
                            // Show admin left notification
                            const notificationDiv = document.createElement('div');
                            notificationDiv.className = 'no-messages';
                            notificationDiv.textContent = 'Support agent has left the chat.';
                            chatMessages.appendChild(notificationDiv);
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        } else if (data.type === 'conversation_closed') {
                            // Reload the page to show conversation ended
                            window.location.reload();
                        }
                    };
                    
                    socket.onclose = function() {
                        console.log('WebSocket connection closed');
                        // Try to reconnect after 5 seconds
                        setTimeout(connectWebSocket, 5000);
                    };
                    
                    socket.onerror = function(error) {
                        console.error('WebSocket error:', error);
                    };
                }
                
                // Connect to WebSocket server
                connectWebSocket();
            </script>
        <?php endif; ?>
    </div>
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>