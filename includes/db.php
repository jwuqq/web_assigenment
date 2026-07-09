<?php
/**
 * 数据库连接 — 自动适配本地/云端环境
 *
 * 云端部署时修改下方 $cloud_* 变量即可，本地无需改动
 */

// ── 云端配置 ──
$cloud_host = 'sql303.infinityfree.com';
$cloud_user = 'if0_42373350';
$cloud_pass = 'TNwKwbD70Z9T';
$cloud_name = 'if0_42373350_final_exam';
$cloud_port = 3306;

// ── 本地配置 ──
$local_host = 'localhost';
$local_user = 'root';
$local_pass = '';
$local_name = 'final_exam';
$local_port = 3307;

// ── 自动判断环境 ──
if (getenv('INFINITYFREE') || strpos($_SERVER['HTTP_HOST'] ?? '', 'rf.gd') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'epizy.com') !== false) {
    // 云端
    $db_host = $cloud_host;
    $db_username = $cloud_user;
    $db_password = $cloud_pass;
    $db_name = $cloud_name;
    $port = $cloud_port;
} else {
    // 本地
    $db_host = $local_host;
    $db_username = $local_user;
    $db_password = $local_pass;
    $db_name = $local_name;
    $port = $local_port;
}

$conn = new mysqli($db_host, $db_username, $db_password, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
