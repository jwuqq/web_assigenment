# 🚀 云端部署指南 — 扫码即用

## 第一步：注册 InfinityFree

1. 打开 https://infinityfree.com
2. 点右上角 **Sign Up**，用 GitHub 或邮箱注册
3. 登录后点 **Create Account** → 选免费方案
4. 你会得到一个免费域名，如 `milktea.rf.gd`（记下来！）

## 第二步：获取 MySQL 凭证

1. InfinityFree 控制面板 → **MySQL Databases**
2. 创建一个新数据库 → 记下四个关键信息：
   - **MySQL Host**: `sql123.epizy.com`（示例）
   - **Username**: `epiz_12345678`（示例）
   - **Password**: 你设的密码
   - **DB Name**: `epiz_12345678_final_exam`

## 第三步：改配置 + 上传

1. 用 VS Code 打开 `includes/db.php`
2. 把文件顶部 `$cloud_*` 四个变量改成上一步拿到的信息
3. 把整个项目文件夹里的内容上传到 InfinityFree 的 `htdocs/`
   - 控制面板 → **Online File Manager**（或 FTP 工具）
   - 拖拽全部文件进去

## 第四步：导入数据库

1. InfinityFree 控制面板 → **phpMyAdmin**
2. 选择你创建的数据库
3. 点 **Import** → 选择 `database/deploy.sql` → 执行

## 第五步：生成二维码

1. 打开 https://cli.im
2. 输入你的域名（如 `http://milktea.rf.gd`）
3. 生成二维码 → 下载图片 → 分享！

---

> 💡 店员账号: `milktea` / `114514`（云端同样有效）
