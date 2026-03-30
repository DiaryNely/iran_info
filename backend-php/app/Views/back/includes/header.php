<?php
  $pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Dashboard';
  $activePage = isset($activePage) ? (string) $activePage : '';
  $adminName = 'admin';
  if (isset($adminUser) && is_array($adminUser) && !empty($adminUser['username'])) {
      $adminName = (string) $adminUser['username'];
  }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Iran Info</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <h1>Iran Info</h1>
    <p class="sidebar-sub">BackOffice Admin</p>

    <nav>
      <a href="/backoffice/dashboard" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="/backoffice/articles" class="<?= $activePage === 'articles' ? 'active' : '' ?>">Articles</a>
      <a href="/backoffice/categories" class="<?= $activePage === 'categories' ? 'active' : '' ?>">Categories</a>
    </nav>

    <div class="sidebar-footer">
      <p><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></p>
      <form method="post" action="/backoffice/logout">
        <button class="btn btn-outline" type="submit">Logout</button>
      </form>
    </div>
  </aside>

  <main class="content">
