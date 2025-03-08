<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin, if not then redirect to admin login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: admin_login.php");
    exit;
}

// Check if room parameter is provided
if(!isset($_GET["room"]) || empty(trim($_GET["room"]))){
    header("location: admin_teleconsultation.php");
    exit;
}

$room_id = trim($_GET["room"]);

// Include database connection
require_once "db_connect.php";

// Get session details if needed
$session_details = null;
$sql = "SELECT * FROM teleconsultation_sessions WHERE room_id = ? AND status = 'active'";

// This is a placeholder - you would need to create this table
// Uncomment when the table exists
/*
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("s", $room_id);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        if($result->num_rows == 1){
            $session_details = $result->fetch_assoc();
        } else {
            // Session not found or not active
            header("location: admin_teleconsultation.php?error=invalid_session");
            exit;
        }
    }
    
    $stmt->close();
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Teleconsultation - Admin</title>
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
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .session-header h1 {
            margin: 0;
            color: #333;
        }
        .session-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .session-info p {
            margin: 5px 0;
        }
        .video-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .main-video {
            background-color: #333;
            height: 480px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
        }
        .side-panel {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        .chat-container {
            flex: 1;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            overflow-y: auto;
            margin-bottom: 10px;
            height: 300px;
        }
        .chat-input {
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
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
        .btn-warning {
            background-color: #ff9800;
        }
        .btn-warning:hover {
            background-color: #e68a00;
        }
        .btn-info {
            background-color: #2196F3;
        }
        .btn-info:hover {
            background-color: #0b7dda;
        }
        .admin-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(255, 152, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 18px;
            max-width: 80%;
            word-wrap: break-word;
        }
        .user-message {
            background-color: #e3f2fd;
            color: #0d47a1;
            align-self: flex-start;
            margin-right: auto;
        }
        .admin-message {
            background-color: #ffecb3;
            color: #ff6f00;
            align-self: flex-end;
            margin-left: auto;
            text-align: right;
        }
        .message-container {
            display: flex;
            flex-direction: column;
        }
        .message-header {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        #videos {
            width: 100%;
            height: 100%;
            position: relative;
        }
        #localVideo, #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        #localVideo {
            position: absolute;
            width: 150px;
            height: 100px;
            top: 10px;
            right: 10px;
            border: 2px solid white;
            border-radius: 5px;
            z-index: 10;
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
        <a href="admin_teleconsultation.php" class="active">Teleconsultation</a>
        <a href="approved_emails.php">Approved Emails</a>
        <a href="settings.php">Settings</a>
        <div class="right">
            <a href="profile.php" class="username">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="session-header">
                <h1>Monitoring Teleconsultation</h1>
                <a href="admin_teleconsultation.php" class="btn btn-info">
                    <i class="fas fa-arrow-left"></i> Back to Sessions
                </a>
            </div>
            
            <div class="session-info">
                <p><strong>Room ID:</strong> <?php echo htmlspecialchars($room_id); ?></p>
                <?php if($session_details): ?>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($session_details['patient_name']); ?></p>
                <p><strong>Started:</strong> <?php echo htmlspecialchars($session_details['start_time']); ?></p>
                <p><strong>Duration:</strong> <span id="session-duration">Calculating...</span></p>
                <?php else: ?>
                <p><strong>Status:</strong> Joining as observer</p>
                <?php endif; ?>
            </div>
            
            <div class="video-container">
                <div class="main-video">
                    <div class="admin-badge">Admin Observer</div>
                    <div id="videos">
                        <video id="remoteVideo" autoplay playsinline></video>
                        <video id="localVideo" autoplay playsinline muted></video>
                    </div>
                </div>
                <div class="side-panel">
                    <h3>Session Chat</h3>
                    <div class="chat-container" id="chat-box">
                        <div class="message-container">
                            <div class="message user-message">
                                <div class="message-header">Patient - 10:30 AM</div>
                                Hello doctor, I'm having some issues with my medication.
                            </div>
                        </div>
                        <div class="message-container">
                            <div class="message user-message">
                                <div class="message-header">Doctor - 10:31 AM</div>
                                I understand. Can you tell me what symptoms you're experiencing?
                            </div>
                        </div>
                        <div class="message-container">
                            <div class="message admin-message">
                                <div class="message-header">Admin (You) - 10:32 AM</div>
                                This is an admin message that only staff can see.
                            </div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" id="chat-message" placeholder="Type admin message...">
                        <button class="btn" id="send-message">Send</button>
                    </div>
                </div>
            </div>
            
            <div class="controls">
                <button class="btn" id="toggle-audio">
                    <i class="fas fa-microphone"></i> Mute Audio
                </button>
                <button class="btn" id="toggle-video">
                    <i class="fas fa-video"></i> Turn Off Video
                </button>
                <button class="btn btn-warning" id="send-alert">
                    <i class="fas fa-exclamation-triangle"></i> Send Alert
                </button>
                <button class="btn btn-danger" id="end-session">
                    <i class="fas fa-phone-slash"></i> End Session
                </button>
            </div>
        </div>
    </div>

    <script>
    // Room ID from PHP
    const roomId = '<?php echo $room_id; ?>';
    const adminName = '<?php echo htmlspecialchars($_SESSION["username"]); ?>';
    
    let localStream;
    let peerConnection;
    const ip = window.location.hostname;
    const socket = new WebSocket(`ws://${ip}:3000`);
    
    // DOM elements
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const toggleAudioBtn = document.getElementById('toggle-audio');
    const toggleVideoBtn = document.getElementById('toggle-video');
    const sendAlertBtn = document.getElementById('send-alert');
    const endSessionBtn = document.getElementById('end-session');
    const chatBox = document.getElementById('chat-box');
    const chatMessage = document.getElementById('chat-message');
    const sendMessageBtn = document.getElementById('send-message');
    
    // Audio/video state
    let isAudioMuted = false;
    let isVideoOff = false;
    
    // Initialize as soon as the page loads
    window.addEventListener('load', () => {
        initializeSession();
    });
    
    function initializeSession() {
        // Join the room as an admin observer
        socket.onopen = () => {
            socket.send(JSON.stringify({ 
                type: 'join', 
                room: roomId,
                isAdmin: true,
                username: adminName
            }));
        };
        
        // Setup WebRTC
        setupMediaAndConnection();
        
        // Setup socket message handling
        socket.onmessage = handleSocketMessage;
        
        // Setup UI controls
        setupControls();
    }
    
    function setupMediaAndConnection() {
        // Get local media stream
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = stream;
                
                // Setup peer connection
                setupPeerConnection();
            })
            .catch(error => {
                console.error('Error accessing media devices:', error);
                alert('Please allow camera and microphone access to join as an observer.');
            });
    }
    
    function setupPeerConnection() {
        // Create RTCPeerConnection
        peerConnection = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                {
                    urls: 'turn:turn.anyfirewall.com:443?transport=tcp',
                    username: 'webrtc',
                    credential: 'webrtc'
                }
            ]
        });
        
        // Add local stream tracks to peer connection
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
        // Handle ICE candidates
        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                socket.send(JSON.stringify({
                    type: 'candidate',
                    candidate: event.candidate,
                    room: roomId
                }));
            }
        };
        
        // Handle remote stream
        peerConnection.ontrack = event => {
            remoteVideo.srcObject = event.streams[0];
        };
    }
    
    function handleSocketMessage(event) {
        const data = JSON.parse(event.data);
        
        switch (data.type) {
            case 'joined':
                console.log(`Admin joined room: ${roomId}`);
                break;
                
            case 'user-joined':
                console.log('User joined the room');
                break;
                
            case 'offer':
                handleOffer(data.sdp);
                break;
                
            case 'answer':
                handleAnswer(data.sdp);
                break;
                
            case 'candidate':
                handleCandidate(data.candidate);
                break;
                
            case 'chat':
                addChatMessage(data.username, data.message, false);
                break;
                
            default:
                console.error('Unknown message type:', data.type);
        }
    }
    
    async function handleOffer(sdp) {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(sdp));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        
        socket.send(JSON.stringify({
            type: 'answer',
            sdp: peerConnection.localDescription,
            room: roomId
        }));
    }
    
    async function handleAnswer(sdp) {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(sdp));
    }
    
    async function handleCandidate(candidate) {
        try {
            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (error) {
            console.error('Error adding ICE candidate:', error);
        }
    }
    
    function setupControls() {
        // Toggle audio
        toggleAudioBtn.addEventListener('click', () => {
            isAudioMuted = !isAudioMuted;
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !isAudioMuted;
            });
            toggleAudioBtn.innerHTML = isAudioMuted ? 
                '<i class="fas fa-microphone-slash"></i> Unmute Audio' : 
                '<i class="fas fa-microphone"></i> Mute Audio';
        });
        
        // Toggle video
        toggleVideoBtn.addEventListener('click', () => {
            isVideoOff = !isVideoOff;
            localStream.getVideoTracks().forEach(track => {
                track.enabled = !isVideoOff;
            });
            toggleVideoBtn.innerHTML = isVideoOff ? 
                '<i class="fas fa-video-slash"></i> Turn On Video' : 
                '<i class="fas fa-video"></i> Turn Off Video';
        });
        
        // Send alert
        sendAlertBtn.addEventListener('click', () => {
            const alertMessage = prompt('Enter alert message for participants:');
            if (alertMessage) {
                socket.send(JSON.stringify({
                    type: 'admin-alert',
                    message: alertMessage,
                    room: roomId
                }));
                addChatMessage('System', `Alert sent: ${alertMessage}`, true);
            }
        });
        
        // End session
        endSessionBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to end this session for all participants?')) {
                socket.send(JSON.stringify({
                    type: 'end-session',
                    room: roomId
                }));
                window.location.href = 'admin_teleconsultation.php?ended=success';
            }
        });
        
        // Send chat message
        sendMessageBtn.addEventListener('click', sendChatMessage);
        chatMessage.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
    }
    
    function sendChatMessage() {
        const message = chatMessage.value.trim();
        if (message) {
            socket.send(JSON.stringify({
                type: 'admin-chat',
                message: message,
                room: roomId,
                username: adminName
            }));
            addChatMessage(adminName, message, true);
            chatMessage.value = '';
        }
    }
    
    function addChatMessage(username, message, isAdmin) {
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        const messageContainer = document.createElement('div');
        messageContainer.className = 'message-container';
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isAdmin ? 'admin-message' : 'user-message'}`;
        
        const headerElement = document.createElement('div');
        headerElement.className = 'message-header';
        headerElement.textContent = `${username} - ${timeString}`;
        
        messageElement.appendChild(headerElement);
        messageElement.appendChild(document.createTextNode(message));
        
        messageContainer.appendChild(messageElement);
        chatBox.appendChild(messageContainer);
        
        // Scroll to bottom
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    </script>