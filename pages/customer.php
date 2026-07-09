<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../index.php'); exit();
}
require_once '../includes/db.php';

// --- Place order ---
$msg = '';
if (isset($_POST['order'])) {
    $drink_id = intval($_POST['drink_id']);
    $qty = intval($_POST['quantity']);
    $r = $conn->query("SELECT name,price FROM inventory WHERE id=$drink_id AND available=1");
    if ($d = $r->fetch_assoc()) {
        $total = $d['price'] * $qty;
        $stmt = $conn->prepare("INSERT INTO orders (user_id,drink_id,drink_name,quantity,total_price) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisid", $_SESSION['user_id'], $drink_id, $d['name'], $qty, $total);
        $stmt->execute();
        $msg = "✅ 已下单：{$d['name']} ×{$qty}，¥{$total}";
    }
}

// --- Submit feedback ---
if (isset($_POST['feedback'])) {
    $msg_text = trim($_POST['message']);
    if (!empty($msg_text)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id,username,message) VALUES (?,?,?)");
        $stmt->bind_param("iss", $_SESSION['user_id'], $_SESSION['username'], $msg_text);
        $stmt->execute();
        $msg = "✅ 留言已提交！";
    }
}

$menu = $conn->query("SELECT * FROM inventory WHERE available=1 ORDER BY id");
$myOrders = $conn->query("SELECT * FROM orders WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>奶茶坊 — 顾客</title><link rel="stylesheet" href="../styles/style.css">
</head>
<body>
<header>
<nav>
    <div class="logo">🧋 奶茶坊</div>
    <ul class="nav-links">
        <li><a href="logout.php">🔙 返回登录</a></li>
        <li><a href="#about">店铺简介</a></li>
        <li><a href="#menu">饮品订购</a></li>
        <li><a href="#queue">候单排队</a></li>
        <li><a href="#fb">留言反馈</a></li>
    </ul>
</nav>
</header>

<main>
<?php if ($msg): ?><div class="msg msg-success"><?php echo $msg; ?></div><?php endif; ?>

<!-- 店铺简介 -->
<section id="about" class="card">
    <h2>🏠 店铺简介</h2>
    <p>欢迎光临 <strong>奶茶坊</strong>！精选鲜奶与好茶，手工现做，每一杯都是好味道。🍵</p>
    <p>营业时间：10:00 - 22:00 | 地址：学府路 88 号</p>
</section>

<!-- 饮品订购 -->
<section id="menu" class="card">
    <h2>📋 饮品订购</h2>
    <div class="menu-grid">
    <?php while ($d = $menu->fetch_assoc()): ?>
        <div class="drink-card">
            <h3><?php echo $d['name']; ?></h3>
            <p class="price">¥<?php echo $d['price']; ?></p>
            <form method="POST" class="order-form">
                <input type="hidden" name="drink_id" value="<?php echo $d['id']; ?>">
                <input type="number" name="quantity" value="1" min="1" max="10" class="qty-input">
                <button type="submit" name="order" class="btn-sm">下单</button>
            </form>
        </div>
    <?php endwhile; ?>
    </div>
</section>

<!-- 候单排队 -->
<section id="queue" class="card">
    <h2>⏳ 候单排队</h2>
    <?php if ($myOrders->num_rows > 0): ?>
    <table class="data-table">
        <tr><th>饮品</th><th>数量</th><th>金额</th><th>状态</th><th>时间</th></tr>
        <?php while ($o = $myOrders->fetch_assoc()): ?>
        <tr>
            <td><?php echo $o['drink_name']; ?></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>¥<?php echo $o['total_price']; ?></td>
            <td><?php echo $o['status']==='done' ? '✅ 已完成' : '⏳ 制作中'; ?></td>
            <td><?php echo $o['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>暂无订单，快去下单吧！</p>
    <?php endif; ?>
</section>

<!-- 留言反馈 -->
<section id="fb" class="card">
    <h2>💬 留言反馈</h2>
    <form method="POST">
        <textarea name="message" rows="3" placeholder="告诉我们你的想法…" required></textarea>
        <button type="submit" name="feedback" class="btn-primary">提交反馈</button>
    </form>
    <?php
    $fbs = $conn->query("SELECT f.*, COALESCE(f.reply,'') AS reply_text FROM feedback f WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 5");
    while ($f = $fbs->fetch_assoc()):
    ?>
    <div class="fb-item">
        <p><strong><?php echo $f['username']; ?></strong> · <?php echo $f['created_at']; ?></p>
        <p><?php echo nl2br(htmlspecialchars($f['message'])); ?></p>
        <?php if ($f['reply']): ?>
        <p class="fb-reply">🧋 店员回复：<?php echo nl2br(htmlspecialchars($f['reply'])); ?></p>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</section>
</main>

<footer>© 2026 奶茶坊 — wangkun 24160144</footer>
<script src="../scripts/script.js"></script>
</body></html>
