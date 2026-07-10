<?php
session_name('STAFF');
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    header('Location: index.php'); exit();
}
require_once '../includes/db.php';

$msg = '';

// Flash message helper
function flash($key, $val) { $_SESSION[$key] = $val; }
function do_redirect($anchor = '') {
    header('Location: staff.php' . ($anchor ? '#' . $anchor : ''));
    exit();
}

// --- Complete order (AJAX sendBeacon, no redirect needed) ---
if (isset($_POST['complete']) && empty($_POST['_ajax'])) {
    // fallback for non-AJAX
    $oid = intval($_POST['order_id']);
    $o = $conn->query("SELECT * FROM orders WHERE id=$oid")->fetch_assoc();
    if ($o && $o['status']==='pending') {
        $conn->query("UPDATE orders SET status='done' WHERE id=$oid");
        $conn->query("INSERT INTO revenue (order_id,drink_name,quantity,amount) VALUES ($oid,'{$o['drink_name']}',{$o['quantity']},{$o['total_price']})");
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
        flash('staff_msg', "✅ 已更新：" . $drink['name']);
    }
    do_redirect('stock');
}

// --- Add new drink ---
if (isset($_POST['add_drink'])) {
    $name = trim($_POST['new_name']);
    $price = floatval($_POST['new_price']);
    if (!empty($name) && $price > 0) {
        // Handle image upload
        $imagePath = '';
        if (!empty($_FILES['drink_image']['tmp_name'])) {
            $ext = pathinfo($_FILES['drink_image']['name'], PATHINFO_EXTENSION);
            $safeName = 'drink_' . time() . '_' . rand(100, 999) . '.' . strtolower($ext);
            $dest = __DIR__ . '/../images/drinks/' . $safeName;
            if (move_uploaded_file($_FILES['drink_image']['tmp_name'], $dest)) {
                $imagePath = 'images/drinks/' . $safeName;
            }
        }
        $imgVal = $imagePath ? "'" . $conn->real_escape_string($imagePath) . "'" : "NULL";
        $conn->query("INSERT INTO inventory (name,price,image) VALUES ('$name',$price,$imgVal)");
        $conn->query("INSERT INTO announcements (message) VALUES ('🆕 新品上线：" . $conn->real_escape_string($name) . "，¥" . number_format($price, 2) . "')");
        flash('staff_msg', "✅ 已添加：$name");
    }
    do_redirect('stock');
}

// --- Reply feedback ---
if (isset($_POST['reply'])) {
    $fid = intval($_POST['fb_id']);
    $reply = trim($_POST['reply_text']);
    if (!empty($reply)) {
        $conn->query("UPDATE feedback SET reply='$reply' WHERE id=$fid");
        flash('staff_msg', "✅ 已回复！");
    }
    do_redirect('reviews');
}

// --- Update price (AJAX +/- via sendBeacon, only "set" redirects) ---
if (isset($_POST['update_price'])) {
    $did = intval($_POST['drink_id']);
    $drink = $conn->query("SELECT name, price FROM inventory WHERE id=$did")->fetch_assoc();
    if ($drink) {
        $oldPrice = $drink['price'];
        if ($_POST['price_action'] === 'inc') {
            $newPrice = $oldPrice + 1;
            $conn->query("UPDATE inventory SET price=$newPrice WHERE id=$did");
            exit(); // AJAX, no output
        } elseif ($_POST['price_action'] === 'dec') {
            $newPrice = max(0, $oldPrice - 1);
            $conn->query("UPDATE inventory SET price=$newPrice WHERE id=$did");
            exit(); // AJAX, no output
        } elseif ($_POST['price_action'] === 'set') {
            $newPrice = max(0, floatval($_POST['new_price']));
            $conn->query("UPDATE inventory SET price=$newPrice WHERE id=$did");
            if ($newPrice != $oldPrice) {
                $conn->query("INSERT INTO announcements (message) VALUES ('💰 {$drink['name']} 价格调整：¥" . number_format($oldPrice, 2) . " → ¥" . number_format($newPrice, 2) . "')");
            }
            flash('staff_msg', "✅ 价格已更新：" . $drink['name']);
            do_redirect('stock');
        }
    }
}

// --- Delete drink ---
if (isset($_POST['delete_drink'])) {
    $did = intval($_POST['drink_id']);
    $conn->query("DELETE FROM inventory WHERE id=$did");
    flash('staff_msg', "✅ 已删除饮品 #$did");
    do_redirect('stock');
}

// Restore flash message
if (isset($_SESSION['staff_msg'])) { $msg = $_SESSION['staff_msg']; unset($_SESSION['staff_msg']); }

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
            <td>
                <span class="price">¥<?php echo number_format($d['price'], 2); ?></span>
                <form method="POST" style="display:flex; gap:4px; align-items:center; margin-top:4px;">
                    <input type="hidden" name="drink_id" value="<?php echo $d['id']; ?>">
                    <input type="hidden" name="update_price" value="1">
                    <input type="hidden" name="price_action" value="set">
                    <input type="number" name="new_price" step="0.01" min="0" value="<?php echo number_format($d['price'], 2); ?>" style="width:70px; padding:0.3rem; text-align:center; border:1px solid #ddd; border-radius:6px; font-size:0.85rem;">
                    <button type="submit" class="btn-sm">确定</button>
                </form>
            </td>
            <td>
                <span class="status-badge <?php echo $d['available'] ? 'status-on' : 'status-off'; ?>">
                    <?php echo $d['available'] ? '✔ 在售' : '✖ 售罄'; ?>
                </span>
            </td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="drink_id" value="<?php echo $d['id']; ?>">
                    <button type="submit" name="toggle" class="btn-sm <?php echo $d['available'] ? 'btn-off' : 'btn-on'; ?>"><?php echo $d['available'] ? '设为售罄' : '设为上架'; ?></button>
                    <button type="submit" name="delete_drink" class="btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3 style="margin-top:1rem">➕ 新增饮品</h3>
    <form method="POST" class="inline-form" enctype="multipart/form-data">
        <input type="text" name="new_name" placeholder="名称" required>
        <input type="number" name="new_price" placeholder="价格" step="0.01" min="0.01" required>
        <input type="file" name="drink_image" accept="image/*" style="font-size:0.85rem;">
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
