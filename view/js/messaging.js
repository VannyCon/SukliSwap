/**
 * Messaging Manager - Handles real-time messaging functionality
 */
class MessagingManager {
    constructor() {
        // Check if AuthManager is available
        if (typeof AuthManager === 'undefined') {
            throw new Error('AuthManager is not available. Please ensure auth.js is loaded before messaging.js');
        }
        
        this.authManager = new AuthManager();
        this.messagingAPI = this.authManager.API_CONFIG.baseURL + 'messaging.php';
        this.headerAPI = this.authManager.API_CONFIG.getHeaders();
        this.formHeaderAPI = this.authManager.API_CONFIG.getFormHeaders();
        this.currentUserId = this.authManager.getUser().id;
        this.websocket = null;
        this.currentTransactionId = null;
        this.currentReceiverId = null;
        this.isTyping = false;
        this.typingTimeout = null;
        this.messageRefreshInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.connectWebSocket();
    }

    setupEventListeners() {
        // Message form submission
        document.getElementById('messageForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // Message input typing detection
        document.getElementById('messageInput')?.addEventListener('input', () => {
            this.handleTyping();
        });

        // File input change
        document.getElementById('messageAttachment')?.addEventListener('change', (e) => {
            this.handleFileSelection(e);
        });

        // Emoji picker
        document.getElementById('emojiPicker')?.addEventListener('click', () => {
            this.toggleEmojiPicker();
        });

        // Close messaging modal
        document.getElementById('messagingModal')?.addEventListener('hidden.bs.modal', () => {
            this.leaveTransaction();
        });
    }

    connectWebSocket() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.hostname}:8080`;
            
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                // this.updateConnectionStatus('connected');
                this.authenticateWebSocket();
            };
            
            this.websocket.onmessage = (event) => {
                this.handleWebSocketMessage(JSON.parse(event.data));
            };
            
            this.websocket.onclose = (event) => {
                console.log('WebSocket disconnected:', event.code, event.reason);
                // this.updateConnectionStatus('disconnected');
                // Only attempt to reconnect if it wasn't a manual close
                if (event.code !== 1000) {
                    console.log('Attempting to reconnect in 5 seconds...');
                    setTimeout(() => this.connectWebSocket(), 5000);
                }
            };
            
            this.websocket.onerror = (error) => {
                console.warn('WebSocket connection failed. Real-time features will be limited.');
                console.log('To enable real-time messaging, start the WebSocket server: php websocket_server.php');
                // this.updateConnectionStatus('error');
                // Don't show error to user as the app can still work without WebSocket
            };
        } catch (error) {
            console.warn('WebSocket not available. Real-time features will be limited.');
            console.log('To enable real-time messaging, start the WebSocket server: php websocket_server.php');
        }
    }

    authenticateWebSocket() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            const token = localStorage.getItem('auth_token');
            this.websocket.send(JSON.stringify({
                action: 'authenticate',
                user_id: this.currentUserId,
                token: token
            }));
        }
    }

    handleWebSocketMessage(data) {
        console.log('WebSocket message received:', data);
        switch (data.action) {
            case 'authenticated':
                console.log('WebSocket authenticated');
                break;
            case 'new_message':
                console.log('New message received via WebSocket:', data.message);
                this.handleNewMessage(data.message);
                break;
            case 'typing':
                this.handleTypingIndicator(data);
                break;
            case 'user_joined':
                this.handleUserJoined(data);
                break;
            case 'user_left':
                this.handleUserLeft(data);
                break;
            case 'error':
                console.error('WebSocket error:', data.message);
                break;
            default:
                console.log('Unknown WebSocket action:', data.action);
        }
    }

    openMessagingModal(transactionId, receiverId) {
        this.currentTransactionId = transactionId;
        this.currentReceiverId = receiverId;
        
        // Load messages
        this.loadMessages();
        
        // Join transaction room
        this.joinTransaction(transactionId);
        
        // Start auto-refresh for messages
        this.startMessageAutoRefresh();
        
        // Show modal
        new bootstrap.Modal(document.getElementById('messagingModal')).show();
    }


    async loadMessages() {
        try {
            console.log('Loading messages for transaction:', this.currentTransactionId);
            const response = await axios.get(`${this.messagingAPI}?action=get_messages&transaction_id=${this.currentTransactionId}`, {
                headers: this.headerAPI
            });

            console.log('Messages response:', response.data);

            if (response.data.success) {
                this.renderMessages(response.data.messages);
                this.scrollToBottom();
            } else {
                console.error('Failed to load messages:', response.data.message);
                // If conversation is closed, show appropriate message
                if (response.data.message && response.data.message.includes('closed')) {
                    this.showConversationClosedMessage();
                }
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            CustomToast.show('error', 'Failed to load messages');
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) {
            console.error('Messages container not found');
            return;
        }

        console.log('Rendering messages:', messages);
        container.innerHTML = messages.map(message => this.renderMessage(message)).join('');
        console.log('Messages rendered, container HTML length:', container.innerHTML.length);
    }

    renderMessage(message) {
        const isOwnMessage = parseInt(message.sender_id) === parseInt(this.currentUserId);
        const messageClass = isOwnMessage ? 'message-own' : 'message-other';
        const timeAgo = this.formatTimeAgo(message.created_at);

        let messageContent = '';
        if (message.message_type === 'image') {
            const imageUrl = `api/serve_message_file.php?file=${encodeURIComponent(message.attachment_path)}`;
            messageContent = `
                <div class="message-image">
                    <img src="${imageUrl}" alt="Image" class="img-fluid rounded" style="max-width: 200px; max-height: 200px;">
                </div>
            `;
        } else {
            messageContent = `
                <div class="message-text">${this.escapeHtml(message.message)}</div>
            `;
        }

        return `
            <div class="message-item ${messageClass}" data-message-id="${message.id}">
                <div class="message-content">
                    ${messageContent}
                    <div class="message-meta">
                        <small class="text-muted">${timeAgo}</small>
                        ${isOwnMessage ? `
                            <div class="message-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="messagingManager.deleteMessage(${message.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        const attachment = document.getElementById('messageAttachment').files[0];
        
        if (!message && !attachment) return;

        const messageType = attachment ? 'image' : 'text';

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('transaction_id', this.currentTransactionId);
            formData.append('receiver_id', this.currentReceiverId);
            formData.append('message', message);
            formData.append('message_type', messageType);
            
            if (attachment) {
                formData.append('attachment', attachment);
            }

            const response = await axios.post(`${this.messagingAPI}?action=send_message`, formData, {
                headers: this.formHeaderAPI
            });

            if (response.data.success) {
                this.loadMessages();
                console.log('Message sent successfully:', response.data);
                messageInput.value = '';
                document.getElementById('messageAttachment').value = '';
                this.hideAttachmentPreview();
                this.stopTyping();
                
                // If WebSocket is not available, reload messages to show the new one
                if (!this.websocket || this.websocket.readyState !== WebSocket.OPEN) {
                    console.log('WebSocket not available, reloading messages');
                    this.loadMessages();
                }
            } else {
                // Check if conversation is closed
                if (response.data.message && response.data.message.includes('closed')) {
                    this.showConversationClosedMessage();
                    this.disableMessaging();
                } else {
                    CustomToast.show('error', response.data.message);
                }
            }
        } catch (error) {
            console.error('Error sending message:', error);
            CustomToast.show('error', 'Failed to send message');
        }
    }

    handleNewMessage(message) {
        this.loadMessages();
        if (parseInt(message.transaction_id) === parseInt(this.currentTransactionId)) {
            const container = document.getElementById('messagesContainer');
            const messageElement = this.renderMessage(message);
            container.insertAdjacentHTML('beforeend', messageElement);
            this.scrollToBottom();
        }
    }

    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }

        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.stopTyping();
        }, 1000);
    }

    sendTypingStatus(isTyping) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                action: 'typing',
                transaction_id: this.currentTransactionId,
                is_typing: isTyping
            }));
        }
    }

    stopTyping() {
        if (this.isTyping) {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }
    }

    handleTypingIndicator(data) {
        this.loadMessages();
        if (parseInt(data.transaction_id) === parseInt(this.currentTransactionId) && 
            parseInt(data.user_id) !== parseInt(this.currentUserId)) {
            // Show typing indicator for other users
            this.showTypingIndicator(data.user_id, data.is_typing);
        }
    }

    showTypingIndicator(userId, isTyping) {
        let indicator = document.getElementById('typingIndicator');
        
        if (isTyping) {
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'typingIndicator';
                indicator.className = 'typing-indicator';
                indicator.innerHTML = '<small class="text-muted"><i class="fas fa-circle"></i> Someone is typing...</small>';
                document.getElementById('messagesContainer').appendChild(indicator);
            }
        } else {
            if (indicator) {
                indicator.remove();
            }
        }
    }

    handleUserJoined(data) {
        console.log('User joined:', data.user_id);
    }

    handleUserLeft(data) {
        console.log('User left:', data.user_id);
    }

    joinTransaction(transactionId) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                action: 'join_transaction',
                transaction_id: transactionId
            }));
        }
    }

    leaveTransaction() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN && this.currentTransactionId) {
            this.websocket.send(JSON.stringify({
                action: 'leave_transaction',
                transaction_id: this.currentTransactionId
            }));
        }
        
        // Stop auto-refresh for messages
        this.stopMessageAutoRefresh();
        
        this.currentTransactionId = null;
        this.currentReceiverId = null;
    }

    handleFileSelection(event) {
        const file = event.target.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                this.showImagePreview(file);
            } else {
                CustomToast.show('error', 'Please select an image file');
                event.target.value = '';
            }
        }
    }

    showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('attachmentPreview');
            preview.innerHTML = `
                <div class="attachment-preview">
                    <img src="${e.target.result}" alt="Preview" class="img-fluid rounded" style="max-width: 200px; max-height: 150px;">
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="messagingManager.hideAttachmentPreview()">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            `;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    hideAttachmentPreview() {
        const preview = document.getElementById('attachmentPreview');
        preview.classList.add('d-none');
        preview.innerHTML = '';
        document.getElementById('messageAttachment').value = '';
    }

    toggleEmojiPicker() {
        // Simple emoji picker implementation
        const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'];
        
        const picker = document.getElementById('emojiPickerDropdown');
        if (picker.classList.contains('show')) {
            picker.classList.remove('show');
        } else {
            picker.innerHTML = emojis.map(emoji => 
                `<button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="messagingManager.insertEmoji('${emoji}')">${emoji}</button>`
            ).join('');
            picker.classList.add('show');
        }
    }

    insertEmoji(emoji) {
        const messageInput = document.getElementById('messageInput');
        messageInput.value += emoji;
        messageInput.focus();
        document.getElementById('emojiPickerDropdown').classList.remove('show');
    }

    async deleteMessage(messageId) {
        if (!confirm('Are you sure you want to delete this message?')) return;

        try {
            const response = await axios.post(`${this.messagingAPI}?action=delete_message`, {
                message_id: messageId
            }, {
                headers: this.headerAPI
            });

            if (response.data.success) {
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.querySelector('.message-text').textContent = '[Message deleted]';
                    messageElement.querySelector('.message-actions').remove();
                }
            } else {
                CustomToast.show('error', response.data.message);
            }
        } catch (error) {
            console.error('Error deleting message:', error);
            CustomToast.show('error', 'Failed to delete message');
        }
    }

    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showConversationClosedMessage() {
        const container = document.getElementById('messagesContainer');
        const closedMessage = document.createElement('div');
        closedMessage.className = 'alert alert-warning text-center';
        closedMessage.innerHTML = `
            <i class="fas fa-lock"></i>
            <strong>Conversation Closed</strong><br>
            <small>This conversation has been closed because the transaction is completed. Messaging is no longer available.</small>
        `;
        container.appendChild(closedMessage);
        this.scrollToBottom();
    }

    disableMessaging() {
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.querySelector('#messageForm button[type="submit"]');
        const attachmentButton = document.querySelector('button[onclick*="messageAttachment"]');
        const emojiButton = document.getElementById('emojiPicker');

        if (messageInput) messageInput.disabled = true;
        if (sendButton) sendButton.disabled = true;
        if (attachmentButton) attachmentButton.disabled = true;
        if (emojiButton) emojiButton.disabled = true;

        // Update placeholder text
        if (messageInput) {
            messageInput.placeholder = 'Conversation is closed - messaging disabled';
        }
    }

    // updateConnectionStatus(status) {
    //     const statusElement = document.getElementById('connectionStatus');
    //     if (!statusElement) return;

    //     switch (status) {
    //         case 'connected':
    //             statusElement.className = 'badge bg-success ms-2';
    //             statusElement.innerHTML = '<i class="fas fa-circle"></i> Live';
    //             break;
    //         case 'disconnected':
    //             statusElement.className = 'badge bg-warning ms-2';
    //             statusElement.innerHTML = '<i class="fas fa-circle"></i> Reconnecting...';
    //             break;
    //         case 'error':
    //             statusElement.className = 'badge bg-danger ms-2';
    //             statusElement.innerHTML = '<i class="fas fa-circle"></i> Offline';
    //             break;
    //         default:
    //             statusElement.className = 'badge bg-secondary ms-2';
    //             statusElement.innerHTML = '<i class="fas fa-circle"></i> Connecting...';
    //     }
    // }

    startMessageAutoRefresh() {
        // Clear any existing interval
        this.stopMessageAutoRefresh();
        
        // Start new interval to load messages every 3 seconds
        this.messageRefreshInterval = setInterval(() => {
            if (this.currentTransactionId) {
                console.log('Auto-refreshing messages...');
                this.loadMessages();
            }
        }, 3000); // 3 seconds = 3000 milliseconds
        
        console.log('Message auto-refresh started (every 3 seconds)');
    }

    stopMessageAutoRefresh() {
        if (this.messageRefreshInterval) {
            clearInterval(this.messageRefreshInterval);
            this.messageRefreshInterval = null;
            console.log('Message auto-refresh stopped');
        }
    }
}

// Initialize messaging manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Wait for AuthManager to be available
    const initMessaging = () => {
        if (typeof AuthManager !== 'undefined' && !window.messagingManager) {
            try {
                window.messagingManager = new MessagingManager();
                console.log('MessagingManager initialized successfully');
            } catch (error) {
                console.error('Failed to initialize MessagingManager:', error);
            }
        } else if (typeof AuthManager === 'undefined') {
            // Retry after a short delay if AuthManager is not yet loaded
            setTimeout(initMessaging, 100);
        }
    };
    
    initMessaging();
});

// Make MessagingManager globally available
window.MessagingManager = MessagingManager;
