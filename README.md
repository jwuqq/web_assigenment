# 🧋 在超市后门偷喝奶茶的二人

**BITMZ023 网页设计导论 Assignment 2** | 2026 春

---

## 成员

| 姓名 | 学号 |
|------|------|
| Wang Kun | 24160144 |
| Hu Jiaming | 24160064 |

## 测试账号

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 店员 | `milktea` | `114514` |
| 顾客 | 自行注册 | — |

## 技术栈

HTML · CSS · JavaScript · PHP · MySQL（纯原生，无框架）

## 功能概览

### 登录页
- 角色选择动画（顾客/店员分入口）
- 顾客：登录 / 注册 / 找回密码（密保验证）
- 店员：专用登录框，无注册入口
- 密码强度检测 + 实时匹配 + 显示/隐藏切换
- 店员 & 顾客 session 分离，同一浏览器可同时登录

### 顾客端
- 饮品卡片式菜单（图片 + 描述），搜索过滤
- 购物车批量下单，支持数量调整
- 候单排队（制作中 / 已完成状态）
- 留言反馈（3 字起 + 2 分钟冷却防刷屏）
- 店铺公告栏 + 每日随机推荐（24h 自动轮换）

### 店员后台
- 待处理订单列表，一键制作（5 秒进度条动画）
- 饮品管理：改价、上下架、删除、新增（支持上传图片）
- 营收统计：折叠面板，可展开查看已完成订单明细
- 顾客评价回复
- 所有操作 PRG 重定向，防 F5 重复提交

## 项目结构

```
├── index.php               # 根入口，跳转登录页
├── images/
│   ├── drinks/             # 饮品图片（6 款）
│   ├── index/              # 背景素材
│   └── zhuangb/            # 😎 彩蛋图片
├── styles/
│   ├── index.css           # 全局共享样式
│   ├── login.css           # 登录页专用
│   ├── customer.css        # 顾客页专用
│   └── staff.css           # 店员页专用
├── scripts/
│   ├── script.js           # 全局共享脚本
│   ├── login.js            # 登录页交互
│   ├── customer.js         # 购物车 + 菜单交互
│   └── staff.js            # 订单制作 + 调价交互
├── includes/
│   ├── db.php              # 数据库连接（自动适配环境）
│   ├── header.php          # 共享页头组件
│   └── footer.php          # 共享页尾组件
├── pages/
│   ├── index.php           # 登录/注册/找回密码
│   ├── customer.php        # 顾客主页
│   ├── staff.php           # 店员后台
│   ├── logout.php          # 退出登录
│   └── zhuangb.php         # 😎 彩蛋页
└── database/
    ├── final_exam.sql      # 建库建表 + 初始数据
    └── deploy.sql          # mysqldump 完整导出（云端导入用）
```

## 数据库（6 张表）

| 表 | 说明 |
|----|------|
| users | 用户（顾客注册 + 店员固定账号） |
| inventory | 饮品库存（名称/价格/图片/上架状态） |
| orders | 订单记录（pending → done） |
| feedback | 顾客留言 & 店员回复 |
| revenue | 已完成订单流水（营收统计） |
| announcements | 店员操作公告 & 每日推荐 |

## 本地运行

1. XAMPP 启动 Apache + MySQL
2. 项目放 `htdocs/assignment2/`
3. 导入 `database/final_exam.sql` 到 MySQL
4. 浏览器打开 `http://localhost/assignment2/`

---

> 2026 在超市后门偷喝奶茶的二人 — Wang Kun 24160144 & Hu Jiaming 24160064
