<?php
// Initialize the session
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teleconsultation</title>
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
            max-width: 1200px;
            margin: 0 auto;
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
        }
        .side-panel {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
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
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
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
        .patient-info {
            margin-bottom: 20px;
        }
        .patient-info h3 {
            margin-top: 0;
            color: #333;
        }
        .chat-container {
            height: 300px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            overflow-y: auto;
        }
        @media screen and (max-width: 768px) {
            .video-container {
                grid-template-columns: 1fr;
            }
            .main-video {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="welcome.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="services.php">Services</a>
        <a href="teleconsultation.php" class="active">Teleconsultation</a>
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
            <a href="login.php">Sign In</a>
            <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <h1>Teleconsultation Session</h1>
            <div class="video-container">
                <div class="main-video">
                    <div id="room-selection">
                        <input type="text" id="roomInput" placeholder="Enter Room Name" aria-label="Room Name" />
                        <button id="joinRoomBtn" class="btn">Join Room</button>
                    </div>

                    <div id="waitingRoomMessage" style="display: none;">Waiting for another user to join...</div>

                    <div id="videos" style="display: none;">
                        <video id="localVideo" autoplay playsinline muted></video>
                        <video id="remoteVideo" autoplay playsinline></video>
                    </div>
                </div>
                <div class="side-panel">
                    <div class="patient-info">
                        <h3>Session Information</h3>
                        <p><strong>Patient:</strong> <span id="patient-name">Loading...</span></p>
                        <p><strong>Time:</strong> <span id="session-time">Loading...</span></p>
                        <p><strong>Status:</strong> <span id="connection-status">Waiting to connect...</span></p>
                    </div>
                    <div class="chat-container" id="chat-box">
                        <!-- Chat messages will appear here -->
                    </div>
                </div>
            </div>
            <div class="controls">
                <button class="btn" id="toggle-audio">Mute Audio</button>
                <button class="btn" id="toggle-video">Turn Off Video</button>
                <button class="btn" id="share-screen">Share Screen</button>
                <button class="btn btn-danger" id="end-call">End Call</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
    let localStream;
    let peerConnection;
    const ip = window.location.hostname;
    const socket = new WebSocket(`ws://${ip}:3000`);

    const joinRoomBtn = document.getElementById('joinRoomBtn');
    const roomInput = document.getElementById('roomInput');
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const waitingRoomMessage = document.getElementById('waitingRoomMessage');
    const videosDiv = document.getElementById('videos');
    const toggleAudioBtn = document.getElementById('toggle-audio');
    const toggleVideoBtn = document.getElementById('toggle-video');
    const endCallBtn = document.getElementById('end-call');

    let currentRoom = '';
    let isInitiator = false;
    let isRoomReady = false;

    joinRoomBtn.addEventListener('click', () => {
        currentRoom = roomInput.value.trim();
        if (currentRoom) {
            socket.send(JSON.stringify({ type: 'join', room: currentRoom }));
            waitingRoomMessage.style.display = 'block';
        } else {
            alert('Please enter a room name.');
        }
    });

    socket.onmessage = async (event) => {
        const data = JSON.parse(event.data);

        switch (data.type) {
            case 'joined':
                console.log(`User joined room: ${currentRoom}`);
                isInitiator = data.initiator;
                checkRoomReady();
                break;

            case 'user-joined':
                console.log('Another user joined the room.');
                isRoomReady = true;
                checkRoomReady();
                break;

            case 'offer':
                await handleOffer(data.sdp);
                break;

            case 'answer':
                await handleAnswer(data.sdp);
                break;

            case 'candidate':
                await handleCandidate(data.candidate);
                break;

            default:
                console.error('Unknown message type:', data.type);
        }
    };

    function checkRoomReady() {
        if (isRoomReady) {
            startCall();
            if (isInitiator) createOffer();
        }
    }

    function startCall() {
        waitingRoomMessage.style.display = 'none';
        videosDiv.style.display = 'flex';
        initializeMediaStreams();
    }

    function initializeMediaStreams() {
        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = stream;
                setupPeerConnection();
            })
            .catch(error => {
                console.error('Error accessing media devices:', error);
                alert('Please allow camera and microphone access.');
            });
    }

    function setupPeerConnection() {
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

        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                socket.send(JSON.stringify({
                    type: 'candidate',
                    candidate: event.candidate,
                    room: currentRoom
                }));
            }
        };

        peerConnection.ontrack = (event) => {
            remoteVideo.srcObject = event.streams[0];
        };

        socket.send(JSON.stringify({ type: 'user-joined', room: currentRoom }));
    }

    async function createOffer() {
        try {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            socket.send(JSON.stringify({ type: 'offer', sdp: offer, room: currentRoom }));
        } catch (error) {
            console.error('Error creating offer:', error);
        }
    }

    async function handleOffer(sdp) {
        try {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(sdp));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            socket.send(JSON.stringify({ type: 'answer', sdp: answer, room: currentRoom }));
        } catch (error) {
            console.error('Error handling offer:', error);
        }
    }

    async function handleAnswer(sdp) {
        try {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(sdp));
        } catch (error) {
            console.error('Error handling answer:', error);
        }
    }

    async function handleCandidate(candidate) {
        try {
            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (error) {
            console.error('Error adding ICE candidate:', error);
        }
    }

    toggleAudioBtn.addEventListener('click', () => {
        if (localStream) {
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                toggleAudioBtn.textContent = audioTrack.enabled ? 'Mute Audio' : 'Unmute Audio';
            }
        }
    });

    toggleVideoBtn.addEventListener('click', () => {
        if (localStream) {
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                toggleVideoBtn.textContent = videoTrack.enabled ? 'Turn Off Video' : 'Turn On Video';
            }
        }
    });

    endCallBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to end the call?')) {
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }

            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }

            socket.send(JSON.stringify({ type: 'leave', room: currentRoom }));
            resetUI();
        }
    });

    function resetUI() {
        localVideo.srcObject = null;
        remoteVideo.srcObject = null;
        videosDiv.style.display = 'none';
        waitingRoomMessage.style.display = 'none';
        roomInput.value = '';
        currentRoom = '';
    }
    </script>
    <!-- Include Hamburger Menu JavaScript -->
    <script src="hamburger_menu.js"></script>
</body>
</html>