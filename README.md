# Assignment 2 — 动态网站

**BITMZ023 网页设计导论** | 2026 春季学期

## 成员

- wangkun 24160144

## 技术栈

HTML · CSS · JavaScript · PHP · MySQL

## 项目结构

```
├── index.php          # 首页，会话启动
├── images/            # 静态图片与资源文件
├── styles/            # 外部 CSS 样式表，响应式布局
├── scripts/           # 外部 JS，DOM 事件与表单验证
├── includes/          # PHP 公共模块（数据库连接、页头页尾）
├── pages/             # 子页面（about、contact、products）
└── database/          # SQL 导出，结构+数据（≥4 张表）
```

## 说明

- **不使用任何框架** — 纯 HTML / CSS / 原生 JS
- PHP 处理放在 `<body>` 内，使用 `mysqli` + prepared statements
- 数据库通过 phpMyAdmin 导出：包含 CREATE DATABASE 与 DROP TABLE 语句
