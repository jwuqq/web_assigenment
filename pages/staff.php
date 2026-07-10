<?php
session_name('STAFF');
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: index.php'); exit();
}
require_once '../includes/db.php';

$msg = '';

// --- Complete order → revenue ---
if (isset($_POST['complete'])) {
    $oid = intval($_POST['order_id']);
    $o = $conn->query("SELECT * FROM orders WHERE id=$oid")->fetch_assoc();
    if ($o && $o['status']==='pending') {
        $conn->query("UPDATE orders SET status='done' WHERE id=$oid");
        $conn->query("INSERT INTO revenue (order_id,drink_name,quantity,amount) VALUES ($oid,'{$o['drink_name']}',{$o['quantity']},{$o['total_price']})");
        $msg = "✅ 订单 #$oid 已完成！";
    }
}

// --- Toggle availability ---
if (isset($_POST['toggle'])) {
    $did = intval($_POST['drink_id']);
    $drink = $conn->query("SELECT name, available FROM inventory WHERE id=$did")->fetch_assoc();
    $conn->query("UPDATE inventory SET available=1-available WHERE id=$did");
    if ($drink) {
        $status = $drink['available'] ? '🔴 ' . $drink['name'] . ' 暂时售罄' : '🧋 ' . $drink['name'] . ' 已上架，欢迎点单！';
        $conn->query("INSERT INTO announcements (message) VALUES ('$status')");
    }
    $msg = $drink ? "✅ 已更新：" . $drink['name'] : '';
}

// --- Add new drink ---
if (isset($_POST['add_drink'])) {
    $name = trim($_POST['new_name']);
    $price = floatval($_POST['new_price']);
    if (!empty($name) && $price > 0) {
        $conn->query("INSERT INTO inventory (name,price) VALUES ('$name',$price)");
        $conn->query("INSERT INTO announcements (message) VALUES ('🆕 新品上线：" . $conn->real_escape_string($name) . "，¥" . number_format($price, 2) . "')");
        $msg = "✅ 已添加：$name";
    }
}

// --- Reply feedback ---
if (isset($_POST['reply'])) {
    $fid = intval($_POST['fb_id']);
    $reply = trim($_POST['reply_text']);
    if (!empty($reply)) {
        $conn->query("UPDATE feedback SET reply='$reply' WHERE id=$fid");
        $msg = "✅ 已回复！";
    }
}

// --- Update price ---
if (isset($_POST['update_price'])) {
    $did = intval($_POST['drink_id']);
    $drink = $conn->query("SELECT name, price FROM inventory WHERE id=$did")->fetch_assoc();
    if ($drink) {
        $oldPrice = $drink['price'];
        if ($_POST['price_action'] === 'inc') {
            $newPrice = $oldPrice + 1;
        } elseif ($_POST['price_action'] === 'dec') {
            $newPrice = max(0, $oldPrice - 1);
        } elseif ($_POST['price_action'] === 'set') {
            $newPrice = max(0, floatval($_POST['new_price']));
            if ($newPrice != $oldPrice) {
                $conn->query("INSERT INTO announcements (message) VALUES ('💰 {$drink['name']} 价格调整：¥" . number_format($oldPrice, 2) . " → ¥" . number_format($newPrice, 2) . "')");
            }
            $msg = "✅ 价格已更新：" . $drink['name'];
        }
        $conn->query("UPDATE inventory SET price=$newPrice WHERE id=$did");
    }
}

// --- Delete drink ---
if (isset($_POST['delete_drink'])) {
    $did = intval($_POST['drink_id']);
    $conn->query("DELETE FROM inventory WHERE id=$did");
    $msg = "✅ 已删除饮品 #$did";
}

$pendingOrders = $conn->query("SELECT o.*,u.username FROM orders o JOIN users u ON o.user_id=u.id WHERE o.status='pending' ORDER BY o.created_at");
$inventory = $conn->query("SELECT * FROM inventory ORDER BY id");
$totalRevenue = $conn->query("SELECT SUM(amount) AS total FROM revenue")->fetch_assoc()['total'] ?? 0;
$doneCount = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='done'")->fetch_assoc()['c'];
$doneOrders = $conn->query("SELECT r.*, u.username FROM revenue r LEFT JOIN orders o ON r.order_id = o.id LEFT JOIN users u ON o.user_id = u.id ORDER BY r.created_at DESC");
$feedback = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>在超市后门偷喝奶茶的二人 — 店员后台</title><link rel="stylesheet" href="../styles/index.css">
</head>
<body>
<header>
<nav>
    <div class="logo">🧋 在超市后门偷喝奶茶的二人 · 店员后台</div>
    <ul class="nav-links">
        <li><a href="logout.php">🔙 退出</a></li>
        <li><a href="#orders">订单管理</a></li>
        <li><a href="#stock">饮品管理</a></li>
        <li><a href="#revenue">营收统计</a></li>
        <li><a href="#reviews">顾客评价</a></li>
    </ul>
</nav>
</header>

<main>
<?php if ($msg): ?><div class="msg msg-success"><?php echo $msg; ?></div><?php endif; ?>

<!-- 订单管理 -->
<section id="orders" class="card">
    <h2>📦 待处理订单</h2>
    <?php if ($pendingOrders->num_rows > 0): ?>
    <table class="data-table">
        <tr><th>#</th><th>顾客</th><th>饮品</th><th>数量</th><th>金额</th><th>操作</th></tr>
        <?php while ($o = $pendingOrders->fetch_assoc()): ?>
        <tr>
            <td><?php echo $o['id']; ?></td>
            <td><?php echo $o['username']; ?></td>
            <td><?php echo $o['drink_name']; ?></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>¥<?php echo $o['total_price']; ?></td>
            <td class="order-action-cell" data-oid="<?php echo $o['id']; ?>">
                <form method="POST" class="make-form" style="display:inline">
                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                    <input type="hidden" name="complete" value="1">
                    <button type="button" class="btn-sm btn-make" onclick="startMaking(this, <?php echo $o['id']; ?>)">🍵 制作</button>
                </form>
                <div class="progress-wrap" style="display:none; margin-top:4px;">
                    <div class="progress-bar"><div class="progress-fill"></div></div>
                    <span class="progress-label">制作中…</span>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?><p>暂无待处理订单 🎉</p><?php endif; ?>
</section>

<!-- 饮品管理 -->
<section id="stock" class="card">
    <h2>📋 饮品管理</h2>
    <table class="data-table">
        <tr><th>ID</th><th>名称</th><th>价格</th><th>状态</th><th>操作</th></tr>
        <?php while ($d = $inventory->fetch_assoc()): ?>
        <tr>
            <td><?php echo $d['id']; ?></td>
            <td><?php echo $d['name']; ?></td>
            <td style="white-space:nowrap;">
                <button type="button" class="btn-sm ajax-price-btn" data-id="<?php echo $d['id']; ?>" data-action="dec" style="padding:0.2rem 0.5rem; font-size:0.8rem;">−</button>
                <span class="price-display" id="price-<?php echo $d['id']; ?>">¥<?php echo number_format($d['price'], 2); ?></span>
                <button type="button" class="btn-sm ajax-price-btn" data-id="<?php echo $d['id']; ?>" data-action="inc" style="padding:0.2rem 0.5rem; font-size:0.8rem;">+</button>
                <form method="POST" style="display:inline-flex; align-items:center; gap:2px; margin-left:6px;">
                    <input type="hidden" name="drink_id" value="<?php echo $d['id']; ?>">
                    <input type="hidden" name="update_price" value="1">
                    <input type="hidden" name="price_action" value="set">
                    <input type="number" name="new_price" step="0.01" min="0" value="<?php echo number_format($d['price'], 2); ?>" style="width:65px; padding:0.2rem 0.3rem; font-size:0.8rem; border:1px solid #ddd; border-radius:4px; text-align:center;">
                    <button type="submit" class="btn-sm" style="padding:0.2rem 0.4rem; font-size:0.75rem;">确定</button>
                </form>
            </td>
            <td><?php echo $d['available'] ? '🟢 上架' : '🔴 售罄'; ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="drink_id" value="<?php echo $d['id']; ?>">
                    <button type="submit" name="toggle" class="btn-sm"><?php echo $d['available'] ? '售罄' : '上架'; ?></button>
                    <button type="submit" name="delete_drink" class="btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3 style="margin-top:1rem">➕ 新增饮品</h3>
    <form method="POST" class="inline-form">
        <input type="text" name="new_name" placeholder="名称" required>
        <input type="number" name="new_price" placeholder="价格" step="0.01" min="0.01" required>
        <button type="submit" name="add_drink" class="btn-sm">添加</button>
    </form>
</section>

<!-- 营收统计 -->
<section id="revenue" class="card">
    <h2>💰 营收统计</h2>
    <div class="stats">
        <div class="stat-box"><h3>¥<?php echo number_format($totalRevenue, 2); ?></h3><p>总营收</p></div>
        <div class="stat-box"><h3><?php echo $doneCount; ?></h3><p>已完成订单</p></div>
    </div>
    <details style="margin-top:1rem;">
        <summary style="cursor:pointer; color:#6a1b9a; font-weight:600; font-size:0.95rem;">
            📋 查看所有已完成订单 (<?php echo $doneCount; ?>单)
        </summary>
        <div style="margin-top:0.8rem; max-height:400px; overflow-y:auto;">
        <?php if ($doneOrders && $doneOrders->num_rows > 0): ?>
        <table class="data-table">
            <tr><th>顾客</th><th>饮品</th><th>数量</th><th>金额</th><th>完成时间</th></tr>
            <?php while ($ro = $doneOrders->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($ro['username'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($ro['drink_name']); ?></td>
                <td><?php echo $ro['quantity']; ?></td>
                <td>¥<?php echo number_format($ro['amount'], 2); ?></td>
                <td><?php echo $ro['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p style="color:#999;">暂无已完成订单</p>
        <?php endif; ?>
        </div>
    </details>
</section>

<!-- 顾客评价 -->
<section id="reviews" class="card">
    <h2>💬 顾客评价</h2>
    <?php while ($f = $feedback->fetch_assoc()): ?>
    <div class="fb-item">
        <p><strong><?php echo $f['username']; ?></strong> · <?php echo $f['created_at']; ?></p>
        <p><?php echo nl2br(htmlspecialchars($f['message'])); ?></p>
        <?php if ($f['reply']): ?>
        <p class="fb-reply">🧋 已回复：<?php echo nl2br(htmlspecialchars($f['reply'])); ?></p>
        <?php else: ?>
        <form method="POST" class="reply-form">
            <input type="hidden" name="fb_id" value="<?php echo $f['id']; ?>">
            <input type="text" name="reply_text" placeholder="回复…" required>
            <button type="submit" name="reply" class="btn-sm">回复</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</section>
</main>

<footer>© 2026 在超市后门偷喝奶茶的二人 — wangkun 24160144</footer>
<script src="../scripts/script.js"></script>
</body></html>
