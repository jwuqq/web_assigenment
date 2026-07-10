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
    <title>在超市后门偷喝奶茶的二人 — 登录</title>
    <link rel="stylesheet" href="../styles/index.css">
</head>
<body class="login-page">
<?php
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';
$activeTab = 'login';   // customer tab default
$staffError = '';       // staff login error
$view = 'role';         // 'role' | 'customer' | 'staff' — tracks which panel to show after POST

// --- CUSTOMER: REGISTER ---
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $view = 'customer';
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

// --- CUSTOMER: LOGIN ---
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $view = 'customer';
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
                session_write_close();
                $sessName = $row['role'] === 'staff' ? 'STAFF' : 'CUSTOMER';
                session_name($sessName);
                session_start();
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

// --- CUSTOMER: FORGOT PASSWORD ---
if (isset($_POST['action']) && $_POST['action'] === 'forgot') {
    $view = 'customer';
    if (isset($_POST['step']) && $_POST['step'] === 'verify') {
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

// --- STAFF LOGIN ---
if (isset($_POST['action']) && $_POST['action'] === 'staff_login') {
    $view = 'staff';
    $s_username = trim($_POST['staff_username']);
    $s_password = $_POST['staff_password'];

    if (empty($s_username) || empty($s_password)) {
        $staffError = '请输入用户名和密码。';
    } else {
        $stmt = $conn->prepare("SELECT id,username,password,role FROM users WHERE username = ? AND role = 'staff'");
        $stmt->bind_param("s", $s_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($s_password, $row['password'])) {
                session_write_close();
                session_name('STAFF');
                session_start();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['logged_in'] = true;
                header('Location: staff.php');
                exit();
            } else {
                $staffError = '密码错误。';
            }
        } else {
            $staffError = '店员账号不存在。';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<div class="login-container">
    <div class="login-card">

        <!-- ========== VIEW 1: 角色选择 ========== -->
        <div id="role-select" class="role-select"<?php echo $view !== 'role' ? ' style="display:none"' : ''; ?>>
            <h1><span class="logo-icon swinging">🧋</span> 在超市后门偷喝奶茶的二人</h1>
            <p class="login-subtitle">来杯好茶爻一爻，吉星高照爻一爻</p>

            <div class="role-cards">
                <div class="role-card" data-role="customer" tabindex="0">
                    <span class="role-icon">👤</span>
                    <span class="role-label">我是顾客</span>
                </div>
                <div class="role-card" data-role="staff" tabindex="0">
                    <span class="role-icon">👔</span>
                    <span class="role-label">我是店员</span>
                </div>
            </div>
        </div>

        <!-- ========== VIEW 2: 顾客面板 ========== -->
        <div id="customer-panel" class="customer-panel<?php echo $view === 'customer' ? ' active' : ''; ?>">
            <button type="button" class="back-link">← 返回选择</button>

            <h1><span class="logo-icon swinging">🧋</span> 在超市后门偷喝奶茶的二人</h1>
            <p class="login-subtitle">来杯好茶爻一爻，吉星高照爻一爻</p>

            <!-- Tab buttons -->
            <div class="tab-buttons">
                <button class="tab-btn <?php echo $activeTab==='login'?'active':''; ?>" data-tab="login">🔑 登录</button>
                <button class="tab-btn <?php echo $activeTab==='register'?'active':''; ?>" data-tab="register">📝 注册</button>
                <button class="tab-btn <?php echo $activeTab==='forgot'?'active':''; ?>" data-tab="forgot">🔐 找回密码</button>
            </div>

            <?php if ($error): ?>
                <div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg msg-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="tab-login" class="tab-panel <?php echo $activeTab==='login'?'active':''; ?>" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-username">用户名</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="login-username" name="login_username" class="no-toggle" placeholder="用户名" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="login-password">密码</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="login-password" name="login_password" placeholder="输入密码" required>
                        <button type="button" class="pw-toggle" data-target="login-password" tabindex="-1">👁️</button>
                    </div>
                </div>
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">记住我</label>
                </div>
                <button type="submit" class="btn-primary">登 录</button>
            </form>

            <!-- Register Form -->
            <form id="tab-register" class="tab-panel <?php echo $activeTab==='register'?'active':''; ?>" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="reg-username">用户名</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="reg-username" name="reg_username" class="no-toggle" placeholder="创建用户名" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-email">邮箱</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" id="reg-email" name="reg_email" class="no-toggle" placeholder="用于接收通知" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-password">密码</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="reg-password" name="reg_password" placeholder="至少 6 位" required minlength="6">
                        <button type="button" class="pw-toggle" data-target="reg-password" tabindex="-1">👁️</button>
                    </div>
                    <div class="pw-strength" id="pw-strength-bar"></div>
                    <span class="pw-strength-text" id="pw-strength-text"></span>
                </div>
                <div class="form-group">
                    <label for="reg-confirm">确认密码</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">✅</span>
                        <input type="password" id="reg-confirm" name="reg_confirm" placeholder="再次输入密码" required>
                        <button type="button" class="pw-toggle" data-target="reg-confirm" tabindex="-1">👁️</button>
                    </div>
                    <span class="field-hint" id="confirm-hint"></span>
                </div>
                <div class="form-group">
                    <label for="reg-question">密保问题</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">❓</span>
                        <input type="text" id="reg-question" name="reg_question" class="no-toggle" placeholder="例如：我的宠物叫什么？" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-answer">密保答案</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">✏️</span>
                        <input type="text" id="reg-answer" name="reg_answer" class="no-toggle" placeholder="密保答案" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary">注 册</button>
            </form>

            <!-- Forgot Password -->
            <form id="tab-forgot" class="tab-panel <?php echo $activeTab==='forgot'?'active':''; ?>" method="POST">
                <input type="hidden" name="action" value="forgot">
                <?php if (!isset($forgotStep) || $forgotStep === 'verify'): ?>
                    <div class="step-indicator">
                        <span class="step-dot active">1</span>
                        <span class="step-line"></span>
                        <span class="step-dot">2</span>
                    </div>
                    <input type="hidden" name="step" value="verify">
                    <div class="form-group">
                        <label for="fg-username">输入用户名</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="fg-username" name="fg_username" class="no-toggle" placeholder="请输入你的用户名" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">验证身份</button>
                <?php else: ?>
                    <div class="step-indicator">
                        <span class="step-dot done">✓</span>
                        <span class="step-line done"></span>
                        <span class="step-dot active">2</span>
                    </div>
                    <input type="hidden" name="step" value="reset">
                    <input type="hidden" name="fg_username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    <p class="sec-q">🔐 密保问题：<strong><?php echo htmlspecialchars($found_question ?? ''); ?></strong></p>
                    <div class="form-group">
                        <label for="fg-answer">密保答案</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">✏️</span>
                            <input type="text" id="fg-answer" name="fg_answer" class="no-toggle" placeholder="输入密保答案" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fg-newpass">新密码</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="fg-newpass" name="fg_newpass" placeholder="至少 6 位" required minlength="6">
                            <button type="button" class="pw-toggle" data-target="fg-newpass" tabindex="-1">👁️</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fg-confirm">确认新密码</label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon">✅</span>
                            <input type="password" id="fg-confirm" name="fg_confirm" placeholder="再次输入新密码" required>
                            <button type="button" class="pw-toggle" data-target="fg-confirm" tabindex="-1">👁️</button>
                        </div>
                        <span class="field-hint" id="fg-confirm-hint"></span>
                    </div>
                    <button type="submit" class="btn-primary">重置密码</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========== VIEW 3: 店员面板 ========== -->
        <div id="staff-panel" class="staff-panel<?php echo $view === 'staff' ? ' active' : ''; ?>">
            <button type="button" class="back-link">← 返回选择</button>

            <div class="staff-header">
                <span class="staff-icon">👔</span>
                <h2>店员登录</h2>
            </div>

            <?php if ($staffError): ?>
                <div class="msg msg-error"><?php echo htmlspecialchars($staffError); ?></div>
            <?php endif; ?>

            <form id="staff-login-form" method="POST">
                <input type="hidden" name="action" value="staff_login">
                <div class="form-group">
                    <label for="staff-username">用户名</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="staff-username" name="staff_username" class="no-toggle" placeholder="店员账号" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="staff-password">密码</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="staff-password" name="staff_password" placeholder="输入密码" required>
                        <button type="button" class="pw-toggle" data-target="staff-password" tabindex="-1">👁️</button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">登 录</button>
            </form>
        </div>

    </div>
</div>
<a href="zhuangb.php" class="easter-egg" title="你真的要看吗">😎😎</a>
<footer>在超市后门偷喝奶茶的二人 — Wang Kun 24160144 & Hu Jiaming 24160064</footer>
<script src="../scripts/script.js"></script>
</body>
</html>
