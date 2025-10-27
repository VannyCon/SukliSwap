<?php
// Test the messaging API directly
require_once 'api/messaging.php';

// Simulate a GET request to get messages
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_messages';
$_GET['transaction_id'] = '1'; // Test with transaction ID 1

// Set a test JWT token (you'll need to replace this with a real token)
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer your_test_token_here';

echo "Testing messaging API...\n";
?>
