<?php
// Include the render helper
require_once '../../../components/render.php';

// Use the new clean approach
renderPage(__DIR__ . '/safeplace_content.php', [
    'page' => 'map',
    'page_js' => ['main.js']
]);
?>
