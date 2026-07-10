<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>😎</title>
    <link rel="stylesheet" href="../styles/index.css">
    <style>
        .zhuangb-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: #fff;
            text-align: center;
        }
        .zhuangb-wrapper img {
            max-width: 90vw;
            max-height: 60vh;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            margin-bottom: 2rem;
        }
        .zhuangb-text {
            font-size: 1.2rem;
            color: #ffd700;
            text-shadow: 0 0 20px rgba(255,215,0,0.3);
            animation: glow 2s ease-in-out infinite alternate;
            max-width: 600px;
            line-height: 1.8;
        }
        @keyframes glow {
            0%   { text-shadow: 0 0 20px rgba(255,215,0,0.3); }
            100% { text-shadow: 0 0 40px rgba(255,215,0,0.6); }
        }
        .back-link-z {
            position: fixed;
            top: 1rem;
            left: 1rem;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link-z:hover { color: #fff; }
    </style>
</head>
<body>
<a href="index.php" class="back-link-z">← 返回</a>
<div class="zhuangb-wrapper">
    <img src="../images/zhuangb/zhuangb.png" alt="885合约">
    <p class="zhuangb-text">
        你怎么知道我在2026年七月九日18时58分04秒<br>
        用一堆拼凑的干员一不小心打了一个<br>
        <strong style="font-size:2rem;">885</strong><br>
        高分合约成绩出来
    </p>
</div>
</body>
</html>
