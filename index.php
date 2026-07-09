<?php
/**
 * 入口转发 — 重定向到登录/注册页
 * ── 店员后台账号（特殊固定账号，仅此一个）──
 *   用户名: milktea
 *   密码:   114514
 */
header('Location: pages/index.php');
exit();
