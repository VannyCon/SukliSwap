<?php
// Include the render helper
require_once '../../../components/render.php';

// Use the new clean approach
renderPage(__DIR__ . '/profile_content.php', [
    'page_js' => ['profile.js']
]);
?>
