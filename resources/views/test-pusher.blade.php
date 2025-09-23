<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🧪 Pusher Broadcasting Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover { background-color: #0056b3; }
        button:disabled { background-color: #6c757d; cursor: not-allowed; }
        
        .messages {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .message {
            margin: 5px 0;
            padding: 8px;
            border-radius: 3px;
            background-color: white;
            border-left: 4px solid #007bff;
        }
        .message.error { border-left-color: #dc3545; }
        .message.success { border-left-color: #28a745; }
        
        .config-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Pusher Broadcasting Test</h1>
        
        <div id="status" class="status info">
            🔄 Connecting to Pusher...
        </div>
        
        <div class="config-info">
            <strong>Configuration:</strong><br>
            Pusher Key: 0f11222c1334ac630b9c<br>
            Cluster: mt1<br>
            Channel: call-transcription<br>
            Event: transcription.updated
        </div>
        
        <div>
            <button id="testBtn" onclick="sendTestBroadcast()" disabled>
                📡 Send Test Broadcast (POST)
            </button>
            <button id="testBtnGet" onclick="sendTestBroadcastGet()" disabled>
                🚀 Send Test Broadcast (GET)
            </button>
            <button onclick="clearMessages()">
                🗑️ Clear Messages
            </button>
            <button onclick="checkConnection()">
                🔍 Check Connection
            </button>
        </div>
        
        <div id="messages" class="messages">
            <div class="message info">Waiting for messages...</div>
        </div>
        
        <div id="lastBroadcast" class="status info" style="display: none;">
            Last broadcast sent at: <span id="lastBroadcastTime"></span>
        </div>
    </div>

    <script>
        // Pusher configuration
        const pusher = new Pusher('0f11222c1334ac630b9c', {
            cluster: 'us2',
            forceTLS: true
        });

        const channel = pusher.subscribe('call-transcription');
        const statusDiv = document.getElementById('status');
        const messagesDiv = document.getElementById('messages');
        const testBtn = document.getElementById('testBtn');
        const testBtnGet = document.getElementById('testBtnGet');

        // Connection event handlers
        pusher.connection.bind('connected', function() {
            updateStatus('success', '✅ Connected to Pusher!');
            testBtn.disabled = false;
            testBtnGet.disabled = false;
            addMessage('Connected to Pusher successfully', 'success');
        });

        pusher.connection.bind('disconnected', function() {
            updateStatus('warning', '⚠️ Disconnected from Pusher');
            testBtn.disabled = true;
            testBtnGet.disabled = true;
            addMessage('Disconnected from Pusher', 'error');
        });

        pusher.connection.bind('error', function(error) {
            updateStatus('error', '❌ Pusher Error: ' + JSON.stringify(error));
            testBtn.disabled = true;
            testBtnGet.disabled = true;
            addMessage('Pusher Error: ' + JSON.stringify(error), 'error');
        });

        // Channel subscription events
        channel.bind('pusher:subscription_succeeded', function() {
            addMessage('✅ Subscribed to call-transcription channel', 'success');
        });

        channel.bind('pusher:subscription_error', function(error) {
            updateStatus('error', '❌ Subscription Error: ' + JSON.stringify(error));
            addMessage('Subscription Error: ' + JSON.stringify(error), 'error');
        });

        // Listen for transcription updates
        channel.bind('transcription.updated', function(data) {
            addMessage('📡 Received transcription update: ' + JSON.stringify(data, null, 2), 'success');
            console.log('📡 Received transcription update:', data);
        });

        // Functions
        function updateStatus(type, message) {
            statusDiv.className = 'status ' + type;
            statusDiv.textContent = message;
        }

        function addMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type;
            messageDiv.innerHTML = '<strong>' + new Date().toLocaleTimeString() + '</strong>: ' + message;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function clearMessages() {
            messagesDiv.innerHTML = '<div class="message info">Messages cleared...</div>';
        }

        function checkConnection() {
            const state = pusher.connection.state;
            addMessage('Connection state: ' + state, 'info');
        }

        function sendTestBroadcast() {
            testBtn.disabled = true;
            testBtn.textContent = '🔄 Sending...';
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                addMessage('❌ CSRF token not found', 'error');
                testBtn.disabled = false;
                testBtn.textContent = '📡 Send Test Broadcast';
                return;
            }
            
            fetch('/test-pusher/broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addMessage('✅ Broadcast sent successfully: ' + data.message, 'success');
                    document.getElementById('lastBroadcastTime').textContent = data.timestamp;
                    document.getElementById('lastBroadcast').style.display = 'block';
                } else {
                    addMessage('❌ Broadcast failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                addMessage('❌ Request failed: ' + error.message, 'error');
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.textContent = '📡 Send Test Broadcast';
            });
        }

        function sendTestBroadcastGet() {
            testBtnGet.disabled = true;
            testBtnGet.textContent = '🔄 Sending...';
            
            fetch('/test-pusher/send')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addMessage('✅ Broadcast sent successfully via GET: ' + data.message, 'success');
                    document.getElementById('lastBroadcastTime').textContent = data.timestamp;
                    document.getElementById('lastBroadcast').style.display = 'block';
                } else {
                    addMessage('❌ Broadcast failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                addMessage('❌ Request failed: ' + error.message, 'error');
            })
            .finally(() => {
                testBtnGet.disabled = false;
                testBtnGet.textContent = '🚀 Send Test Broadcast (GET)';
            });
        }

        // Auto-scroll messages
        setInterval(() => {
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }, 1000);
    </script>
</body>
</html>
