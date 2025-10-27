# Messaging Feature Setup Guide

This guide explains how to set up and use the messaging feature for the SukliSwap coin exchange system.

## Features

- **Real-time messaging** between transaction participants (offeror and requestor)
- **Image sharing** with automatic preview
- **Emoji support** with emoji picker
- **Typing indicators** to show when someone is typing
- **Message reactions** (planned for future)
- **Auto-close messaging** when transaction is completed
- **WebSocket support** for real-time communication

## Database Setup

1. Run the SQL script to create messaging tables:
```sql
-- Run this in your MySQL database
source database/messaging_tables.sql;
```

This will create the following tables:
- `tbl_messages` (enhanced with attachment support)
- `tbl_message_read_status`
- `tbl_conversation_participants`
- `tbl_message_reactions`
- `tbl_message_deletions`
- `tbl_websocket_connections`

## WebSocket Server Setup

1. **Start the WebSocket server:**
   ```bash
   # Windows
   websocket_start.bat
   
   # Or manually
   php websocket_server.php
   ```

2. **The WebSocket server runs on port 8080 by default**

3. **For production, consider using a process manager like PM2:**
   ```bash
   pm2 start websocket_server.php --name "sukliswap-websocket"
   ```

## File Structure

```
sukliswap/
├── api/
│   ├── messaging.php              # Messaging API endpoints
│   └── serve_message_file.php     # File serving for message attachments
├── services/
│   ├── MessagingService.php       # Core messaging logic
│   ├── WebSocketService.php       # WebSocket server implementation
│   └── FileUploadService.php      # File upload handling (enhanced)
├── view/js/
│   └── messaging.js               # Frontend messaging functionality
├── database/
│   └── messaging_tables.sql       # Database schema for messaging
├── websocket_server.php           # WebSocket server startup script
└── websocket_start.bat           # Windows batch file to start server
```

## API Endpoints

### Messaging API (`api/messaging.php`)

- `GET ?action=get_messages&transaction_id={id}` - Get messages for a transaction
- `POST ?action=send_message` - Send a new message
- `GET ?action=get_conversation_participants&transaction_id={id}` - Get participants
- `POST ?action=mark_messages_read` - Mark messages as read
- `GET ?action=get_unread_count` - Get unread message count
- `POST ?action=delete_message` - Delete a message
- `POST ?action=add_reaction` - Add reaction to message
- `GET ?action=get_message_reactions&message_id={id}` - Get message reactions
- `GET ?action=get_user_conversations` - Get user's conversations

### File Serving API (`api/serve_message_file.php`)

- `GET ?file={path}` - Serve message attachment files securely

## Usage

### For Users

1. **Access messaging:** Click the "Message" button on any active transaction (scheduled or in-progress)

2. **Send messages:** Type in the message box and press Enter or click Send

3. **Share images:** Click the paperclip icon to attach and send images

4. **Use emojis:** Click the smiley face icon to insert emojis

5. **View participants:** See who's in the conversation in the right sidebar

### For Developers

1. **Initialize messaging manager:**
   ```javascript
   const messagingManager = new MessagingManager();
   ```

2. **Open messaging for a transaction:**
   ```javascript
   messagingManager.openMessagingModal(transactionId, receiverId);
   ```

3. **Send a message programmatically:**
   ```javascript
   // This is handled automatically by the form submission
   ```

## WebSocket Events

### Client to Server

- `authenticate` - Authenticate user with JWT token
- `join_transaction` - Join a transaction conversation room
- `leave_transaction` - Leave a transaction conversation room
- `send_message` - Send a message (also handled via API)
- `typing` - Send typing indicator
- `ping` - Keep connection alive

### Server to Client

- `authenticated` - Authentication successful
- `new_message` - New message received
- `typing` - Someone is typing
- `user_joined` - User joined conversation
- `user_left` - User left conversation
- `error` - Error occurred

## Security Features

1. **JWT Authentication** - All API calls require valid JWT tokens
2. **File Upload Validation** - Only images (JPEG, PNG, GIF) up to 5MB
3. **Path Traversal Protection** - File serving prevents directory traversal
4. **Transaction Validation** - Users can only message within their transactions
5. **Auto-close** - Messaging automatically closes when transaction completes

## Configuration

### WebSocket Server Port
Edit `websocket_server.php` to change the port:
```php
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MessagingWebSocket()
        )
    ),
    8080  // Change this port number
);
```

### File Upload Limits
Edit `services/FileUploadService.php`:
```php
private $maxFileSize = 5 * 1024 * 1024; // 5MB
private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
```

## Troubleshooting

### WebSocket Connection Issues

1. **Check if port 8080 is available:**
   ```bash
   netstat -an | findstr :8080
   ```

2. **Check firewall settings** - Ensure port 8080 is open

3. **Check browser console** for WebSocket connection errors

### Database Issues

1. **Verify all tables were created:**
   ```sql
   SHOW TABLES LIKE '%message%';
   ```

2. **Check foreign key constraints:**
   ```sql
   SHOW CREATE TABLE tbl_messages;
   ```

### File Upload Issues

1. **Check directory permissions:**
   ```bash
   chmod 755 data/documents/messages/
   ```

2. **Verify PHP upload settings:**
   ```ini
   upload_max_filesize = 5M
   post_max_size = 5M
   ```

## Future Enhancements

- [ ] Message reactions with emoji picker
- [ ] Message search functionality
- [ ] Message forwarding
- [ ] Voice messages
- [ ] Message encryption
- [ ] Push notifications
- [ ] Message scheduling
- [ ] Group conversations for multiple transactions

## Support

For issues or questions about the messaging feature, please check:
1. Browser console for JavaScript errors
2. PHP error logs for server-side issues
3. WebSocket server console output
4. Database connection and table structure
