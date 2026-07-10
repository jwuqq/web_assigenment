<?php
// Destroy both staff and customer sessions
foreach (['STAFF', 'CUSTOMER'] as $name) {
    session_name($name);
    session_start();
    $_SESSION = array();
    session_destroy();
}
header('Location: index.php');
exit();
