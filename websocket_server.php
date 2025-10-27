<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/services/WebSocketService.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MessagingWebSocket()
        )
    ),
    8080
);

echo "WebSocket server starting on port 8080...\n";
echo "Press Ctrl+C to stop the server\n";

// Start the server
$server->run();
