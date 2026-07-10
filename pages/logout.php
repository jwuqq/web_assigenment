<?php
// 两个session都清掉，不管当前是店员还是顾客
foreach (['STAFF', 'CUSTOMER'] as $name) {
    session_name($name);
    session_start();
    $_SESSION = array();
    session_destroy();
}
header('Location: index.php');
exit();
