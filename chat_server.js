const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const os = require('os');
const mysql = require('mysql');

const app = express();
const port = 3001; // Different from videocall port

const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

// Create MySQL connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'lagrocare'
});

// Connect to MySQL
db.connect(err => {
    if (err) {
        console.error('Error connecting to MySQL database:', err);
        return;
    }
    console.log('Connected to MySQL database');
});

// Store active connections
const connections = {
    users: {}, // userId -> socket
    admins: {}  // adminId -> socket
};

// Store active conversations
const activeConversations = {}; // conversationId -> { userId, adminIds[] }

function getLocalIp() {
    const interfaces = os.networkInterfaces();
    for (const name of Object.keys(interfaces)) {
        for (const iface of interfaces[name]) {
            if (iface.family === 'IPv4' && !iface.internal) {
                return iface.address;
            }
        }
    }
    return '127.0.0.1';
}

wss.on('connection', (socket) => {
    let userId = null;
    let isAdmin = false;
    let activeConversationId = null;

    socket.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            console.log('Received message:', data.type);

            switch (data.type) {
                case 'register':
                    // Register the connection with user ID
                    userId = data.userId;
                    isAdmin = data.isAdmin;
                    
                    if (isAdmin) {
                        connections.admins[userId] = socket;
                        console.log(`Admin ${userId} connected`);
                    } else {
                        connections.users[userId] = socket;
                        console.log(`User ${userId} connected`);
                    }
                    break;

                case 'start_conversation':
                    // User starts a new conversation
                    if (!isAdmin && userId) {
                        activeConversationId = data.conversationId;
                        activeConversations[activeConversationId] = {
                            userId: userId,
                            adminIds: []
                        };
                        
                        // Notify all admins about new conversation
                        notifyAdmins({
                            type: 'new_conversation',
                            conversationId: activeConversationId,
                            userId: userId,
                            username: data.username
                        });
                    }
                    break;

                case 'join_conversation':
                    // Admin joins a conversation
                    if (isAdmin && userId) {
                        const conversationId = data.conversationId;
                        if (activeConversations[conversationId]) {
                            activeConversationId = conversationId;
                            activeConversations[conversationId].adminIds.push(userId);
                            
                            // Notify user that admin joined
                            const userSocket = connections.users[activeConversations[conversationId].userId];
                            if (userSocket) {
                                userSocket.send(JSON.stringify({
                                    type: 'admin_joined',
                                    conversationId: conversationId,
                                    adminId: userId,
                                    adminName: data.adminName
                                }));
                            }
                        }
                    }
                    break;

                case 'message':
                    // Handle chat message
                    if (userId) {
                        const conversationId = data.conversationId;
                        const message = data.message;
                        const isAdminMessage = isAdmin ? 1 : 0;
                        const timestamp = new Date().toISOString();
                        
                        // Save message to database
                        const sql = "INSERT INTO chat_messages (conversation_id, sender_id, is_admin, message, created_at) VALUES (?, ?, ?, ?, NOW())";
                        db.query(sql, [conversationId, userId, isAdminMessage, message], (err, result) => {
                            if (err) {
                                console.error('Error saving message to database:', err);
                            } else {
                                console.log('Message saved to database, ID:', result.insertId);
                                
                                // Update conversation timestamp
                                const updateSql = "UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?";
                                db.query(updateSql, [conversationId], (updateErr) => {
                                    if (updateErr) {
                                        console.error('Error updating conversation timestamp:', updateErr);
                                    }
                                });
                            }
                        });
                        
                        if (isAdmin) {
                            // Admin sending message to user
                            const userSocket = connections.users[activeConversations[conversationId]?.userId];
                            if (userSocket) {
                                userSocket.send(JSON.stringify({
                                    type: 'message',
                                    conversationId: conversationId,
                                    senderId: userId,
                                    senderName: data.senderName,
                                    isAdmin: true,
                                    message: message,
                                    timestamp: timestamp
                                }));
                            }
                        } else {
                            // User sending message to admins
                            const conversation = activeConversations[conversationId];
                            if (conversation) {
                                // Send message to all admins in the conversation
                                conversation.adminIds.forEach(adminId => {
                                    const adminSocket = connections.admins[adminId];
                                    if (adminSocket) {
                                        adminSocket.send(JSON.stringify({
                                            type: 'message',
                                            conversationId: conversationId,
                                            senderId: userId,
                                            senderName: data.senderName,
                                            isAdmin: false,
                                            message: message,
                                            timestamp: timestamp
                                        }));
                                    }
                                });
                                
                                // Also send the message back to the user who sent it
                                const userSocket = connections.users[userId];
                                if (userSocket) {
                                    userSocket.send(JSON.stringify({
                                        type: 'message',
                                        conversationId: conversationId,
                                        senderId: userId,
                                        senderName: data.senderName,
                                        isAdmin: false,
                                        message: message,
                                        timestamp: timestamp
                                    }));
                                }
                            }
                        }
                    }
                    break;

                case 'close_conversation':
                    // Close the conversation
                    if (activeConversationId) {
                        const conversation = activeConversations[activeConversationId];
                        
                        if (conversation) {
                            // Notify all participants
                            const userSocket = connections.users[conversation.userId];
                            if (userSocket) {
                                userSocket.send(JSON.stringify({
                                    type: 'conversation_closed',
                                    conversationId: activeConversationId,
                                    closedBy: userId
                                }));
                            }
                            
                            conversation.adminIds.forEach(adminId => {
                                const adminSocket = connections.admins[adminId];
                                if (adminSocket && adminId !== userId) {
                                    adminSocket.send(JSON.stringify({
                                        type: 'conversation_closed',
                                        conversationId: activeConversationId,
                                        closedBy: userId
                                    }));
                                }
                            });
                            
                            // Remove conversation
                            delete activeConversations[activeConversationId];
                        }
                    }
                    break;
            }
        } catch (error) {
            console.error('Error processing message:', error);
        }
    });

    socket.on('close', () => {
        console.log(`Connection closed for ${isAdmin ? 'admin' : 'user'} ${userId}`);
        
        // Clean up connections
        if (userId) {
            if (isAdmin) {
                delete connections.admins[userId];
                
                // Remove admin from active conversations
                Object.keys(activeConversations).forEach(conversationId => {
                    const conversation = activeConversations[conversationId];
                    const index = conversation.adminIds.indexOf(userId);
                    if (index !== -1) {
                        conversation.adminIds.splice(index, 1);
                        
                        // Notify user that admin left
                        const userSocket = connections.users[conversation.userId];
                        if (userSocket) {
                            userSocket.send(JSON.stringify({
                                type: 'admin_left',
                                conversationId: conversationId,
                                adminId: userId
                            }));
                        }
                    }
                });
            } else {
                delete connections.users[userId];
                
                // Notify admins about user disconnection
                if (activeConversationId && activeConversations[activeConversationId]) {
                    const conversation = activeConversations[activeConversationId];
                    conversation.adminIds.forEach(adminId => {
                        const adminSocket = connections.admins[adminId];
                        if (adminSocket) {
                            adminSocket.send(JSON.stringify({
                                type: 'user_disconnected',
                                conversationId: activeConversationId,
                                userId: userId
                            }));
                        }
                    });
                    
                    // Remove conversation
                    delete activeConversations[activeConversationId];
                }
            }
        }
    });
});

// Helper function to notify all admins
function notifyAdmins(data) {
    Object.values(connections.admins).forEach(socket => {
        socket.send(JSON.stringify(data));
    });
}

server.listen(port, () => {
    const localIp = getLocalIp();
    console.log(`Chat server running on:`);
    console.log(`- Local:   http://localhost:${port}`);
    console.log(`- Network: http://${localIp}:${port}`);
});