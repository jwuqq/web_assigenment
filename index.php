<?php
session_start();
// If already logged in, redirect
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'staff') {
        header('Location: staff.php');
    } else {
        header('Location: customer.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>奶茶坊 — 登录</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php
require_once 'includes/db.php';

$error = '';
$success = '';
$activeTab = 'login';

// --- REGISTER ---
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm = $_POST['reg_confirm'];
    $question = trim($_POST['reg_question']);
    $answer = trim($_POST['reg_answer']);

    if (empty($username) || empty($email) || empty($password) || empty($question) || empty($answer)) {
        $error = '所有字段都必须填写。';
    } elseif ($password !== $confirm) {
        $error = '两次密码输入不一致。';
    } elseif (strlen($password) < 6) {
        $error = '密码至少 6 位。';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = '用户名或邮箱已被注册。';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO users (username,password,email,security_question,security_answer) VALUES (?,?,?,?,?)");
            $stmt2->bind_param("sssss", $username, $hash, $email, $question, $answer);
            if ($stmt2->execute()) {
                $success = '注册成功！请登录。';
                $activeTab = 'login';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

// --- LOGIN ---
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['login_username']);
    $password = $_POST['login_password'];

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码。';
    } else {
        $stmt = $conn->prepare("SELECT id,username,password,role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['logged_in'] = true;
                if ($row['role'] === 'staff') {
                    header('Location: staff.php');
                } else {
                    header('Location: customer.php');
                }
                exit();
            } else {
                $error = '密码错误。';
            }
        } else {
            $error = '用户名不存在。';
        }
        $stmt->close();
    }
}

// --- FORGOT PASSWORD ---
if (isset($_POST['action']) && $_POST['action'] === 'forgot') {
    if (isset($_POST['step']) && $_POST['step'] === 'verify') {
        // Step 1: verify username
        $username = trim($_POST['fg_username']);
        $stmt = $conn->prepare("SELECT id,security_question FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $found_user_id = $row['id'];
            $found_question = $row['security_question'];
            $activeTab = 'forgot';
            $forgotStep = 'reset';
        } else {
            $error = '用户名不存在。';
            $activeTab = 'forgot';
            $forgotStep = 'verify';
        }
        $stmt->close();
    } elseif (isset($_POST['step']) && $_POST['step'] === 'reset') {
        // Step 2: verify answer + reset password
        $username = trim($_POST['fg_username']);
        $answer = trim($_POST['fg_answer']);
        $newpass = $_POST['fg_newpass'];
        $confirm = $_POST['fg_confirm'];

        $stmt = $conn->prepare("SELECT id,security_answer FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($answer === $row['security_answer']) {
                if ($newpass === $confirm && strlen($newpass) >= 6) {
                    $hash = password_hash($newpass, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt2->bind_param("si", $hash, $row['id']);
                    $stmt2->execute();
                    $stmt2->close();
                    $success = '密码重置成功！请登录。';
                    $activeTab = 'login';
                } else {
                    $error = '密码至少 6 位且两次输入一致。';
                    $activeTab = 'forgot';
                    $forgotStep = 'reset';
                }
            } else {
                $error = '密保答案错误。';
                $activeTab = 'forgot';
                $forgotStep = 'reset';
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<div class="login-container">
    <div class="login-card">
        <h1>🧋 奶茶坊</h1>

        <!-- Tab buttons -->
        <div class="tab-buttons">
            <button class="tab-btn <?php echo $activeTab==='login'?'active':''; ?>" data-tab="login">登录</button>
            <button class="tab-btn <?php echo $activeTab==='register'?'active':''; ?>" data-tab="register">注册</button>
            <button class="tab-btn <?php echo $activeTab==='forgot'?'active':''; ?>" data-tab="forgot">找回密码</button>
        </div>

        <?php if ($error): ?>
            <div class="msg msg-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="msg msg-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="tab-login" class="tab-panel <?php echo $activeTab==='login'?'active':''; ?>" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="login-username">用户名</label>
                <input type="text" id="login-username" name="login_username" placeholder="顾客或店员用户名" required>
            </div>
            <div class="form-group">
                <label for="login-password">密码</label>
                <input type="password" id="login-password" name="login_password" placeholder="输入密码" required>
            </div>
            <button type="submit" class="btn-primary">登 录</button>
        </form>

        <!-- Register Form -->
        <form id="tab-register" class="tab-panel <?php echo $activeTab==='register'?'active':''; ?>" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="reg-username">用户名</label>
                <input type="text" id="reg-username" name="reg_username" placeholder="创建用户名" required>
            </div>
            <div class="form-group">
                <label for="reg-email">邮箱</label>
                <input type="email" id="reg-email" name="reg_email" placeholder="用于接收通知" required>
            </div>
            <div class="form-group">
                <label for="reg-password">密码</label>
                <input type="password" id="reg-password" name="reg_password" placeholder="至少 6 位" required minlength="6">
            </div>
            <div class="form-group">
                <label for="reg-confirm">确认密码</label>
                <input type="password" id="reg-confirm" name="reg_confirm" placeholder="再次输入密码" required>
            </div>
            <div class="form-group">
                <label for="reg-question">密保问题</label>
                <input type="text" id="reg-question" name="reg_question" placeholder="例如：我的宠物叫什么？" required>
            </div>
            <div class="form-group">
                <label for="reg-answer">密保答案</label>
                <input type="text" id="reg-answer" name="reg_answer" placeholder="密保答案" required>
            </div>
            <button type="submit" class="btn-primary">注 册</button>
        </form>

        <!-- Forgot Password -->
        <form id="tab-forgot" class="tab-panel <?php echo $activeTab==='forgot'?'active':''; ?>" method="POST">
            <input type="hidden" name="action" value="forgot">
            <?php if (!isset($forgotStep) || $forgotStep === 'verify'): ?>
                <input type="hidden" name="step" value="verify">
                <div class="form-group">
                    <label for="fg-username">输入用户名</label>
                    <input type="text" id="fg-username" name="fg_username" placeholder="请输入你的用户名" required>
                </div>
                <button type="submit" class="btn-primary">验证身份</button>
            <?php else: ?>
                <input type="hidden" name="step" value="reset">
                <input type="hidden" name="fg_username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                <p class="sec-q">🔐 密保问题：<strong><?php echo htmlspecialchars($found_question ?? ''); ?></strong></p>
                <div class="form-group">
                    <label for="fg-answer">密保答案</label>
                    <input type="text" id="fg-answer" name="fg_answer" placeholder="输入密保答案" required>
                </div>
                <div class="form-group">
                    <label for="fg-newpass">新密码</label>
                    <input type="password" id="fg-newpass" name="fg_newpass" placeholder="至少 6 位" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="fg-confirm">确认新密码</label>
                    <input type="password" id="fg-confirm" name="fg_confirm" placeholder="再次输入新密码" required>
                </div>
                <button type="submit" class="btn-primary">重置密码</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script src="scripts/script.js"></script>
</body>
</html>
