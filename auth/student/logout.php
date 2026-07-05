<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

$database = new Database();
$sessionManager = new SessionManager($database);

$sessionManager->destroySession();

header('Location: /exam-seat-allocation/index.php');
exit;
?>
