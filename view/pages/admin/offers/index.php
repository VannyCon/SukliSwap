<?php
// Include the render helper
require_once '../../../components/render.php';

// Use the new clean approach
renderPage(__DIR__ . '/offers_content.php', [
    'page_js' => ['offers.js']
]);
?>
