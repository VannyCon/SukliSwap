<?php
// Include the render helper
require_once '../../../components/render.php';

// Use the new clean approach
renderPage(__DIR__ . '/transactions_content.php', [
    'page' => 'map',
    'page_js' => ['transactions.js']
]);
?>
