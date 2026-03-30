<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Article', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars((string) (($article['metaDescription'] ?? '') ?: ''), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="canonical" href="/article/<?= rawurlencode((string) ($article['slug'] ?? '')) ?>">
  <link rel="stylesheet" href="/assets/frontoffice.css">
</head>
<body>
  <?php
    $headerCategories = is_array($categories ?? null) ? $categories : [];
    $articleTitle = (string) ($article['title'] ?? 'Article');
    $articleSummary = (string) ($article['metaDescription'] ?? '');
    $articleContent = trim((string) ($article['content'] ?? ''));
    $paragraphs = preg_split('/\R{2,}/', $articleContent) ?: [];
    if (count($paragraphs) === 0 || (count($paragraphs) === 1 && trim((string) $paragraphs[0]) === '')) {
        $paragraphs = [$articleContent !== '' ? $articleContent : 'Contenu indisponible.'];
    }
    $coverPath = (string) ($article['coverImagePath'] ?? '');
    $authorName = (string) (($article['author']['username'] ?? '') ?: 'Redaction');
    $createdAt = (string) ($article['createdAt'] ?? '');
    $dateFr = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : 'Date inconnue';
    $articleCategories = is_array($article['categories'] ?? null) ? $article['categories'] : [];
    $kicker = count($articleCategories) > 0 ? strtoupper((string) ($articleCategories[0]['name'] ?? 'ACTUALITE')) : 'ACTUALITE';
  ?>

<div class="news-shell">
  <header class="news-header">
    <div class="news-header-inner">
      <a href="/" class="news-logo" aria-label="Aller a l'accueil Iran Info">IRAN INFO</a>
      <nav class="news-nav" aria-label="Navigation principale">
        <a href="/" class="news-nav-link">Accueil</a>
        <?php foreach ($headerCategories as $category): ?>
          <?php
            $catName = (string) ($category['name'] ?? 'Categorie');
            $catSlug = (string) ($category['slug'] ?? '');
            if ($catSlug === '') {
                continue;
            }
          ?>
          <a href="/?category=<?= rawurlencode($catSlug) ?>" class="news-nav-link">
            <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="news-header-actions">
        <a href="/backoffice/login" class="news-admin-link">Admin</a>
      </div>
    </div>
  </header>

  <main class="news-main">
    <div class="news-article-layout">
      <article class="news-article-main">
        <header class="news-article-header">
          <p class="news-kicker"><?= htmlspecialchars($kicker, ENT_QUOTES, 'UTF-8') ?></p>
          <h1><?= htmlspecialchars($articleTitle, ENT_QUOTES, 'UTF-8') ?></h1>
          <?php if ($articleSummary !== ''): ?>
            <p class="news-article-standfirst"><?= htmlspecialchars($articleSummary, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <p class="news-meta-line">Par <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($dateFr, ENT_QUOTES, 'UTF-8') ?></p>
        </header>

        <?php if ($coverPath !== ''): ?>
          <figure class="news-article-hero">
            <img src="<?= htmlspecialchars($coverPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($article['coverImageAlt'] ?? $articleTitle), ENT_QUOTES, 'UTF-8') ?>">
          </figure>
        <?php endif; ?>

        <section class="news-article-content" aria-labelledby="article-content-title">
          <h2 id="article-content-title">Analyse</h2>
          <?php foreach ($paragraphs as $paragraph): ?>
            <p><?= htmlspecialchars(trim((string) $paragraph), ENT_QUOTES, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </section>
      </article>

      <aside class="news-sidebar" aria-label="Contexte">
        <section class="news-sidebar-block" aria-labelledby="context-title">
          <h2 id="context-title">Contexte</h2>
          <ul class="news-context-list">
            <li>Slug: <?= htmlspecialchars((string) ($article['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
            <li>Auteur: <?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?></li>
            <li>Date de publication: <?= htmlspecialchars($dateFr, ENT_QUOTES, 'UTF-8') ?></li>
          </ul>
        </section>

        <section class="news-sidebar-block" aria-labelledby="category-title">
          <h2 id="category-title">Categories</h2>
          <?php if (count($articleCategories) > 0): ?>
            <ul class="news-context-list">
              <?php foreach ($articleCategories as $category): ?>
                <li><?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>Aucune categorie.</p>
          <?php endif; ?>
        </section>

        <a href="/" class="news-all-articles-btn">Voir tous les articles</a>
      </aside>
    </div>
  </main>

  <footer class="news-footer">
    <div class="news-footer-inner">
      <p>Iran Info | Journal numerique international</p>
    </div>
  </footer>
</div>
</body>
</html>
