<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

// Include database connection
require_once "db_connect.php";

// Get admin information
$admin_id = $_SESSION["id"];
$admin_username = $_SESSION["username"];

// Get active conversation if specified
$active_conversation_id = null;
if(isset($_GET["conversation"]) && !empty($_GET["conversation"])){
    $active_conversation_id = intval($_GET["conversation"]);
}

// Handle message sending
if(isset($_POST["send_message"]) && !empty(trim($_POST["message"])) && $active_conversation_id){
    $message = trim($_POST["message"]);
    
    $sql = "INSERT INTO chat_messages (conversation_id, sender_id, is_admin, message) VALUES (?, ?, 1, ?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("iis", $active_conversation_id, $admin_id, $message);
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
    header("location: admin_chat.php?conversation=" . $active_conversation_id);
    exit;
}

// Handle conversation closing
if(isset($_POST["close_conversation"]) && $active_conversation_id){
    $sql = "UPDATE chat_conversations SET status = 'closed' WHERE conversation_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $active_conversation_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirect to chat list
    header("location: admin_chat.php");
    exit;
}

// Get navigation active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get active conversations
$conversations = [];
$sql = "SELECT c.conversation_id, c.user_id, c.created_at, c.updated_at, u.username, 
        (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.conversation_id AND is_read = 0 AND is_admin = 0) as unread_count
        FROM chat_conversations c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY c.updated_at DESC";
$result = $conn->query($sql);
if($result){
    while($row = $result->fetch_assoc()){
        $conversations[] = $row;
    }
}

// Get chat messages if there's an active conversation
$messages = [];
$user_info = null;
if($active_conversation_id){
    // Get user info
    $user_sql = "SELECT u.id, u.username, u.email, c.created_at
                FROM chat_conversations c
                JOIN users u ON c.user_id = u.id
                WHERE c.conversation_id = ?";
    if($user_stmt = $conn->prepare($user_sql)){
        $user_stmt->bind_param("i", $active_conversation_id);
        if($user_stmt->execute()){
            $user_result = $user_stmt->get_result();
            if($user_result->num_rows == 1){
                $user_info = $user_result->fetch_assoc();
            }
        }
        $user_stmt->close();
    }
    
    // Get messages
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
           WHERE conversation_id = ? AND sender_id != ? AND is_admin = 0 AND is_read = 0";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("ii", $active_conversation_id, $admin_id);
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
    <title>Admin Chat Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            display: flex;
            gap: 20px;
        }
        .sidebar {
            width: 300px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .conversation-list {
            flex: 1;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        .conversation-item:hover {
            background-color: #f9f9f9;
        }
        .conversation-item.active {
            background-color: #e9f7ef;
            border-left: 4px solid #4CAF50;
        }
        .conversation-item h3 {
            margin: 0 0 5px;
            font-size: 16px;
            color: #333;
        }
        .conversation-item p {
            margin: 0;
            font-size: 13px;
            color: #777;
        }
        .conversation-item .time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .unread-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
        }
        .chat-container {
            flex: 1;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 80vh;
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
        .user-info {
            margin-top: 5px;
            font-size: 14px;
            opacity: 0.9;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
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
            background-color: #e3f2fd;
            color: #333;
            border-top-right-radius: 5px;
            align-self: flex-end;
        }
        .user-message {
            background-color: white;
            color: #333;
            border-top-left-radius: 5px;
            align-self: flex-start;
            border-left: 3px solid #4CAF50;
        }
        .chat-input-container {
            display: flex;
            padding: 10px;
            border-top: 1px solid #eee;
            background-color: white;
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
        .no-conversation {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #777;
            text-align: center;
            padding: 20px;
        }
        .no-conversation i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #ddd;
        }
        .no-messages {
            text-align: center;
            color: #999;
            margin: 20px 0;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
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
    </style>
</head>
<body>
    
    <div class="navbar" id="myNavbar">
        <a href="admin_panel.php">Dashboard</a>
        <a href="admin_requests.php">Requests</a>
        <a href="users.php">Users</a>
        <a href="admin_chat.php" class="active">Chat Support</a>
        <a href="admin_messages.php">User Messages</a>
        <a href="admin_teleconsultation.php">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="#" class="username">Hello, <?php echo htmlspecialchars($admin_username); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-comments"></i> Active Conversations</h2>
            </div>
            <div class="conversation-list">
                <?php if(empty($conversations)): ?>
                    <div style="padding: 20px; text-align: center; color: #777;">
                        No active conversations
                    </div>
                <?php else: ?>
                    <?php foreach($conversations as $conversation): ?>
                        <div class="conversation-item <?php echo ($active_conversation_id == $conversation['conversation_id']) ? 'active' : ''; ?>" 
                             onclick="window.location='admin_chat.php?conversation=<?php echo $conversation['conversation_id']; ?>'">
                            <h3><?php echo htmlspecialchars($conversation['username']); ?></h3>
                            <p>Started: <?php echo date('M j, Y g:i a', strtotime($conversation['created_at'])); ?></p>
                            <p class="time">Last activity: <?php echo date('M j, g:i a', strtotime($conversation['updated_at'])); ?></p>
                            <?php if($conversation['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $conversation['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($active_conversation_id && $user_info): ?>
            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        <h2><?php echo htmlspecialchars($user_info['username']); ?></h2>
                        <div class="user-info">
                            <span><?php echo htmlspecialchars($user_info['email']); ?></span> | 
                            <span>Started: <?php echo date('M j, Y g:i a', strtotime($user_info['created_at'])); ?></span>
                        </div>
                    </div>
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
                
                <form method="post" action="" class="chat-input-container">
                    <input type="text" name="message" class="chat-input" placeholder="Type your message..." required autofocus>
                    <button type="submit" name="send_message" class="chat-send"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
            
            <script>
                // Scroll to bottom of chat messages
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Set up WebSocket connection for real-time updates
                const adminId = <?php echo $admin_id; ?>;
                const adminName = "<?php echo $admin_username; ?>";
                const conversationId = <?php echo $active_conversation_id; ?>;
                const ip = window.location.hostname;
                let socket;
                // No profile picture needed in this version
                
                function connectWebSocket() {
                    socket = new WebSocket(`ws://${ip}:3001`);
                    
                    socket.onopen = function() {
                        console.log('WebSocket connected');
                        // Register admin with the WebSocket server
                        socket.send(JSON.stringify({
                            type: 'register',
                            userId: adminId,
                            isAdmin: true
                        }));
                        
                        // Join the conversation
                        socket.send(JSON.stringify({
                            type: 'join_conversation',
                            conversationId: conversationId,
                            adminId: adminId,
                            adminName: adminName
                        }));
                    };
                    
                    socket.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        console.log('Message received:', data);
                        
                        if (data.type === 'message') {
                            // Add new message to chat
                            const messageDiv = document.createElement('div');
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
                        } else if (data.type === 'user_disconnected') {
                            // Show user disconnected notification
                            const notificationDiv = document.createElement('div');
                            notificationDiv.className = 'no-messages';
                            notificationDiv.textContent = 'User has disconnected from the chat.';
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
        <?php else: ?>
            <div class="chat-container">
                <div class="no-conversation">
                    <i class="fas fa-comments"></i>
                    <h2>Select a conversation</h2>
                    <p>Choose an active conversation from the sidebar to start chatting.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>