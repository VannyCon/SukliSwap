<?php
require_once '../../../components/render.php';

renderPage(__DIR__ . '/notifications_content.php', [
	'page_js' => [ 'notification.js' ]
]);
?>

