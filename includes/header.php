<?php
// Shared header — session check + DB + HTML head + nav START
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: index.php'); exit(); }
require_once __DIR__ . '/db.php';
?><!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>奶茶坊</title><link rel="stylesheet" href="styles/style.css"></head>
<body>
<header><nav><div class="logo">🧋 奶茶坊</div>
<ul class="nav-links">
