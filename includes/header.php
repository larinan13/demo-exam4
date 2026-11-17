<?php if (!isset($pageTitle)) $pageTitle = 'Сладкие сны'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/index.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner container">
        <a href="/index.php" class="header-brand">
            <span class="brand-icon">🌙</span>
            <div>
                <div class="brand-name">Сладкие сны</div>
                <div class="brand-slogan">Приезжайте как гости, уезжайте как друзья!</div>
            </div>
        </a>
        
        <nav class="header-nav">
            <a href="/index.php" class="nav-link-custom">Каталог</a>
            <a href="/admin/login.php" class="nav-link-custom admin-link" title="Вход для администратора">
                <span class="admin-icon">⚙️</span>
                <span class="admin-text">Войти</span>
            </a>
        </nav>
    </div>
</header>

<div class="main-content container">
