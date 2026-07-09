<?php
$db_host = 'localhost';
$port = 3307;
$db_username = 'root';
$db_password = '';
$db_name = 'final_exam';

$conn = new mysqli($db_host, $db_username, $db_password, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
