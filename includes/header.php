<?php
/**
 * 共享页头 — 当前未被引用，保留备用
 */
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: index.php'); exit(); }
require_once __DIR__ . '/db.php';
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>在超市后门偷喝奶茶的二人</title>
<link rel="stylesheet" href="../styles/index.css">
</head>
<body>
<header>
<nav>
    <div class="logo">🧋 在超市后门偷喝奶茶的二人</div>
    <ul class="nav-links">
