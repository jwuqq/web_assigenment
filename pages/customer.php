<?php
session_name('CUSTOMER');
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'customer') {
    header('Location: index.php'); exit();
}
require_once '../includes/db.php';

$msg = '';
$error = '';

function money_fmt($amount) {
    return number_format((float)$amount, 2);
}

function drink_image_src($name, $image) {
    if (!empty($image)) {
        return '../' . ltrim($image, '/');
    }

    $fallbacks = [
        '招牌珍珠奶茶' => 'classic-pearl.jpg',
        '茉莉绿奶茶' => 'jasmine-green.jpg',
        '芋泥波波奶茶' => 'taro-boba.jpg',
        '黑糖珍珠鲜奶' => 'brown-sugar.jpg',
        '芝士莓莓' => 'berry-cheese.jpg',
        '柠檬红茶' => 'lemon-tea.jpg',
    ];

    return '../images/drinks/' . ($fallbacks[$name] ?? 'classic-pearl.jpg');
}

function drink_note($name) {
    $notes = [
        '招牌珍珠奶茶' => '经典红茶奶底，珍珠软糯，适合第一次点单。',
        '茉莉绿奶茶' => '茉莉茶香更清爽，甜度低一点也顺口。',
        '芋泥波波奶茶' => '厚实芋泥加波波，口感更像甜品。',
        '黑糖珍珠鲜奶' => '黑糖挂壁香气重，鲜奶口感更浓。',
        '芝士莓莓' => '莓果酸甜配芝士奶盖，适合下午茶。',
        '柠檬红茶' => '清爽解腻，冰饮风味更明显。',
    ];

    return $notes[$name] ?? '鲜奶茶底，可按口味备注给店员。';
}

// --- Checkout cart ---
if (isset($_POST['checkout_cart'])) {
    $cart_payload = $_POST['cart_payload'] ?? '[]';
    $cart_items = json_decode($cart_payload, true);

    if (!is_array($cart_items) || count($cart_items) === 0) {
        $error = "购物车还是空的，请先选择饮品。";
    } else {
        $inserted = 0;
        $total_all = 0;
        $conn->begin_transaction();

        try {
            foreach ($cart_items as $item) {
                $drink_id = (int)($item['id'] ?? 0);
                $qty = max(1, min(10, (int)($item['qty'] ?? 1)));

                $stmt = $conn->prepare("SELECT name, price FROM inventory WHERE id = ? AND available = 1");
                $stmt->bind_param("i", $drink_id);
                $stmt->execute();
                $drink = $stmt->get_result();

                if ($d = $drink->fetch_assoc()) {
                    $total = (float)$d['price'] * $qty;
                    $stmt = $conn->prepare("INSERT INTO orders (user_id,drink_id,drink_name,quantity,total_price) VALUES (?,?,?,?,?)");
                    $stmt->bind_param("iisid", $_SESSION['user_id'], $drink_id, $d['name'], $qty, $total);
                    $stmt->execute();
                    $inserted++;
                    $total_all += $total;
                }
            }

            if ($inserted === 0) {
                throw new Exception("购物车里的饮品暂不可售，请重新选择。");
            }

            $conn->commit();
            $msg = "✅ 已提交 {$inserted} 款饮品，合计 ¥" . money_fmt($total_all);
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage() ?: "提交订单失败，请稍后再试。";
        }
    }
}

// --- Place order: legacy fallback ---
if (isset($_POST['order']) && !isset($_POST['checkout_cart'])) {
    $drink_id = (int)($_POST['drink_id'] ?? 0);
    $qty = max(1, min(10, (int)($_POST['quantity'] ?? 1)));

    $stmt = $conn->prepare("SELECT name, price FROM inventory WHERE id = ? AND available = 1");
    $stmt->bind_param("i", $drink_id);
    $stmt->execute();
    $drink = $stmt->get_result();
    if ($d = $drink->fetch_assoc()) {
        $total = (float)$d['price'] * $qty;
        $stmt = $conn->prepare("INSERT INTO orders (user_id,drink_id,drink_name,quantity,total_price) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisid", $_SESSION['user_id'], $drink_id, $d['name'], $qty, $total);
        if ($stmt->execute()) {
            $msg = "✅ 已下单：{$d['name']} ×{$qty}，¥" . money_fmt($total);
        } else {
            $error = "下单失败，请稍后再试。";
        }
    } else {
        $error = "该饮品暂不可售，请重新选择。";
    }
}

// --- Submit feedback ---
if (isset($_POST['feedback'])) {
    $msg_text = trim($_POST['message']);
    if (!empty($msg_text)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id,username,message) VALUES (?,?,?)");
        $stmt->bind_param("iss", $_SESSION['user_id'], $_SESSION['username'], $msg_text);
        if ($stmt->execute()) {
            $msg = "✅ 留言已提交！";
        } else {
            $error = "留言提交失败，请稍后再试。";
        }
    }
}

$menu = $conn->query("SELECT * FROM inventory WHERE available=1 ORDER BY id");
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$myOrders = $stmt->get_result();
// Auto-generate today's recommendation if not exists
$today = date('Y-m-d');
$todayRec = $conn->query("SELECT id FROM announcements WHERE message LIKE '🌟 今日推荐%' AND DATE(created_at)='$today'");
if ($todayRec->num_rows === 0) {
    $rand = $conn->query("SELECT name FROM inventory WHERE available=1 ORDER BY RAND() LIMIT 1")->fetch_assoc();
    if ($rand) {
        $conn->query("INSERT INTO announcements (message) VALUES ('🌟 今日推荐：{$rand['name']} —— 试试看吧！')");
    }
}
$announcements = $conn->query("SELECT * FROM announcements ORDER BY CASE WHEN message LIKE '🌟 今日推荐%' THEN 0 ELSE 1 END, created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>在超市后门偷喝奶茶的二人 — 顾客</title><link rel="stylesheet" href="../styles/index.css">
</head>
<body>
<header>
<nav>
    <div class="logo">🧋 在超市后门偷喝奶茶的二人</div>
    <button type="button" class="nav-toggle" aria-label="打开菜单" aria-expanded="false">☰</button>
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
<?php if ($error): ?><div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<section class="customer-hero">
    <div class="hero-copy">
        <span class="eyebrow">今日手作 · 现点现做</span>
        <h1>挑一杯顺口的奶茶</h1>
        <p>登录后可直接下单、查看候单进度，也能给店员留言反馈口味建议。</p>
    </div>
    <div class="hero-panel">
        <strong>营业中</strong>
        <span>10:00 - 22:00</span>
    </div>
</section>

<!-- 公告栏 -->
<section class="card announcement-card">
    <h2>📢 店铺公告</h2>
    <?php if ($announcements && $announcements->num_rows > 0):
        while ($a = $announcements->fetch_assoc()): ?>
    <p style="margin:0.4rem 0; font-size:0.95rem;"><?php echo htmlspecialchars($a['message']); ?>
        <span style="color:#aaa; font-size:0.78rem; margin-left:0.5rem;"><?php echo $a['created_at']; ?></span></p>
    <?php endwhile; else: ?>
    <p style="color:#999;">暂无公告</p>
    <?php endif; ?>
</section>

<!-- 店铺简介 -->
<section id="about" class="card">
    <h2>🏠 店铺简介</h2>
    <p>欢迎光临 <strong>在超市后门偷喝奶茶的二人</strong>！精选鲜奶与好茶，手工现做，每一杯都是好味道。🍵</p>
    <p>营业时间：10:00 - 22:00 | 地址：学府路 88 号</p>
</section>

<!-- 饮品订购 -->
<section id="menu" class="card">
    <div class="section-head">
        <div>
            <h2>📋 饮品订购</h2>
            <p>先选择数量加入购物车，再一次性提交多款饮品。</p>
        </div>
        <label class="menu-search">
            <span>搜索</span>
            <input type="search" id="drink-search" placeholder="输入饮品名称">
        </label>
    </div>
    <div class="menu-grid">
    <?php while ($d = $menu->fetch_assoc()): ?>
        <div class="drink-card" data-name="<?php echo htmlspecialchars($d['name'], ENT_QUOTES); ?>">
            <img class="drink-photo" src="<?php echo htmlspecialchars(drink_image_src($d['name'], $d['image'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($d['name'], ENT_QUOTES); ?>">
            <div class="drink-info">
                <h3><?php echo htmlspecialchars($d['name']); ?></h3>
                <p class="drink-note"><?php echo htmlspecialchars(drink_note($d['name'])); ?></p>
            </div>
            <div class="order-form cart-picker">
                <span class="price">¥<?php echo money_fmt($d['price']); ?></span>
                <div class="qty-stepper" aria-label="选择数量">
                    <button type="button" class="qty-btn qty-minus" aria-label="减少数量">−</button>
                    <input type="number" value="1" min="1" max="10" class="qty-input" aria-label="数量">
                    <button type="button" class="qty-btn qty-plus" aria-label="增加数量">+</button>
                </div>
                <button type="button"
                        class="btn-sm btn-add-cart"
                        data-id="<?php echo $d['id']; ?>"
                        data-name="<?php echo htmlspecialchars($d['name'], ENT_QUOTES); ?>"
                        data-price="<?php echo money_fmt($d['price']); ?>">加入购物车</button>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
    <p class="empty-state" id="drink-empty" hidden>没有找到对应饮品。</p>
</section>

<section id="cart" class="card cart-card">
    <div class="section-head">
        <div>
            <h2>🛒 我的购物车</h2>
            <p>可以同时选择多款饮品，确认后统一提交给店员。</p>
        </div>
        <button type="button" class="btn-sm btn-ghost" id="cart-clear">清空</button>
    </div>
    <div class="cart-list" id="cart-list">
        <p class="empty-state">购物车为空，先去选择饮品吧。</p>
    </div>
    <div class="cart-summary">
        <span>合计</span>
        <strong id="cart-total">¥0.00</strong>
    </div>
    <form method="POST" id="cart-checkout-form">
        <input type="hidden" name="checkout_cart" value="1">
        <input type="hidden" name="cart_payload" id="cart-payload" value="[]">
        <button type="submit" class="btn-primary btn-checkout" disabled>提交订单</button>
    </form>
</section>

<!-- 候单排队 -->
<section id="queue" class="card">
    <h2>⏳ 候单排队</h2>
    <?php if ($myOrders->num_rows > 0): ?>
    <table class="data-table">
        <tr><th>饮品</th><th>数量</th><th>金额</th><th>状态</th><th>时间</th></tr>
        <?php while ($o = $myOrders->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($o['drink_name']); ?></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>¥<?php echo money_fmt($o['total_price']); ?></td>
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
    <form method="POST" action="">
        <textarea name="message" rows="3" placeholder="告诉我们你的想法…" required></textarea>
        <button type="submit" name="feedback" class="btn-primary">提交反馈</button>
    </form>
    <?php
    $stmt = $conn->prepare("SELECT f.*, COALESCE(f.reply,'') AS reply_text FROM feedback f WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $fbs = $stmt->get_result();
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

<div class="modal-backdrop" id="order-modal" hidden>
    <div class="order-modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
        <button type="button" class="modal-close" aria-label="关闭">×</button>
        <h2 id="order-modal-title">确认提交订单</h2>
        <p id="order-modal-text"></p>
        <div class="modal-actions">
            <button type="button" class="btn-sm btn-ghost modal-cancel">再想想</button>
            <button type="button" class="btn-sm modal-confirm">确认下单</button>
        </div>
    </div>
</div>

<footer>© 2026 在超市后门偷喝奶茶的二人 — wangkun 24160144</footer>
<script src="../scripts/script.js"></script>
</body></html>
