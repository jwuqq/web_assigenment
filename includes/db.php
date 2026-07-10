<?php
/**
 * 数据库连接 — 自动适配 本地/InfinityFree/Render 环境
 */

// ── Render 环境（自动检测环境变量）──
if (getenv('RENDER')) {
    $db_host = getenv('MYSQL_HOST') ?: 'localhost';
    $db_username = getenv('MYSQL_USER') ?: 'root';
    $db_password = getenv('MYSQL_PASSWORD') ?: '';
    $db_name = getenv('MYSQL_DATABASE') ?: 'final_exam';
    $port = intval(getenv('MYSQL_PORT') ?: 3306);
}
// ── InfinityFree 环境 ──
elseif (strpos($_SERVER['HTTP_HOST'] ?? '', 'rf.gd') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'epizy.com') !== false) {
    $db_host = 'sql303.infinityfree.com';
    $db_username = 'if0_42373350';
    $db_password = 'TNwKwbD70Z9T';
    $db_name = 'if0_42373350_final_exam';
    $port = 3306;
}
// ── 本地环境 ──
else {
    // 本地连云端：改为 true → 本地也读写 InfinityFree 数据库（所有人同步）
    $local_use_cloud = false;
    if ($local_use_cloud) {
        $db_host = 'sql303.infinityfree.com';
        $db_username = 'if0_42373350';
        $db_password = 'TNwKwbD70Z9T';
        $db_name = 'if0_42373350_final_exam';
        $port = 3306;
    } else {
        $db_host = 'localhost';
        $db_username = 'root';
        $db_password = '';
        $db_name = 'final_exam';
        $port = 3307;
    }
}

$conn = new mysqli($db_host, $db_username, $db_password, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
// 云端时区修正：InfinityFree服务器在美国，手动设成北京时间
$conn->query("SET time_zone = '+08:00'");
