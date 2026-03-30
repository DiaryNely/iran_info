<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
    $baseUrl = rtrim((string) ($baseUrl ?? ''), '/');
    $articleSlug = (string) ($article['slug'] ?? '');
    $canonicalUrl = (string) ($canonicalUrl ?? ($baseUrl . '/article/' . rawurlencode($articleSlug)));
    $metaDescription = trim((string) (($article['metaDescription'] ?? '') ?: ''));
    if ($metaDescription === '') {
        $metaDescription = mb_substr(trim((string) ($article['content'] ?? '')), 0, 160);
    }

    $coverPath = (string) ($article['coverImagePath'] ?? '');
    $coverImageUrl = '';
    if ($coverPath !== '') {
        if (str_starts_with($coverPath, 'http://') || str_starts_with($coverPath, 'https://')) {
            $coverImageUrl = $coverPath;
        } else {
            $coverImageUrl = $baseUrl . (str_starts_with($coverPath, '/') ? $coverPath : ('/' . $coverPath));
        }
    }

    $publishedAt = (string) ($article['publishedAt'] ?? $article['createdAt'] ?? '');
    $updatedAt = (string) ($article['updatedAt'] ?? $article['createdAt'] ?? '');
    $authorName = (string) (($article['author']['username'] ?? '') ?: 'Redaction');

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => (string) ($article['title'] ?? 'Article'),
        'description' => $metaDescription,
        'mainEntityOfPage' => $canonicalUrl,
        'url' => $canonicalUrl,
        'author' => [
            '@type' => 'Person',
            'name' => $authorName,
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Iran Info',
        ],
        'inLanguage' => 'fr',
    ];

    if ($coverImageUrl !== '') {
        $articleSchema['image'] = [$coverImageUrl];
    }
    if ($publishedAt !== '') {
        $articleSchema['datePublished'] = date(DATE_ATOM, strtotime($publishedAt));
    }
    if ($updatedAt !== '') {
        $articleSchema['dateModified'] = date(DATE_ATOM, strtotime($updatedAt));
    }
  ?>
  <title><?= htmlspecialchars($title ?? 'Article', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= htmlspecialchars((string) ($article['title'] ?? 'Article'), ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($coverImageUrl !== ''): ?>
    <meta property="og:image" content="<?= htmlspecialchars($coverImageUrl, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <script type="application/ld+json"><?= json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
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
    $createdAt = (string) ($article['createdAt'] ?? '');
    $dateFr = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : 'Date inconnue';
    $articleCategories = is_array($article['categories'] ?? null) ? $article['categories'] : [];
    $kicker = count($articleCategories) > 0 ? strtoupper((string) ($articleCategories[0]['name'] ?? 'ACTUALITE')) : 'ACTUALITE';
    $featuredArticle = is_array($featuredArticle ?? null) ? $featuredArticle : null;
    $readAlsoArticles = is_array($readAlsoArticles ?? null) ? $readAlsoArticles : [];
    $toMediaUrl = static function (string $path) use ($baseUrl): string {
      if ($path === '') {
        return '';
      }
      if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
      }

      return $baseUrl . (str_starts_with($path, '/') ? $path : ('/' . $path));
    };
  $resolveFeaturedImagePath = static function (array $item): string {
    $gallery = is_array($item['galleryImages'] ?? null) ? $item['galleryImages'] : [];
    foreach ($gallery as $img) {
      if (!is_array($img)) {
        continue;
      }
      $alt = strtolower(trim((string) ($img['alt'] ?? '')));
      $path = trim((string) ($img['path'] ?? ''));
      if ($path === '') {
        continue;
      }
      if (
        str_contains($alt, 'a la une') ||
        str_contains($alt, 'alaune') ||
        str_contains($alt, 'featured') ||
        str_contains($alt, 'une')
      ) {
        return $path;
      }
    }

    $cover = trim((string) ($item['coverImagePath'] ?? ''));
    if ($cover !== '') {
      return $cover;
    }

    foreach ($gallery as $img) {
      if (!is_array($img)) {
        continue;
      }
      $path = trim((string) ($img['path'] ?? ''));
      if ($path !== '') {
        return $path;
      }
    }

    return '';
  };
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
        <?php if ($featuredArticle !== null): ?>
          <?php
            $featuredTitle = (string) ($featuredArticle['title'] ?? 'Article');
            $featuredSlug = (string) ($featuredArticle['slug'] ?? '');
            $featuredCover = $resolveFeaturedImagePath($featuredArticle);
            $featuredImage = $toMediaUrl($featuredCover);
            $featuredCategory = 'Actualite';
            if (is_array($featuredArticle['categories'] ?? null) && count($featuredArticle['categories']) > 0) {
                $featuredCategory = (string) (($featuredArticle['categories'][0]['name'] ?? 'Actualite'));
            }
          ?>
          <section class="news-sidebar-block" aria-labelledby="featured-title">
            <h2 id="featured-title">A la une</h2>
            <article class="news-side-featured">
              <a href="/article/<?= rawurlencode($featuredSlug) ?>" class="news-side-featured-link">
                <?php if ($featuredImage !== ''): ?>
                  <img src="<?= htmlspecialchars($featuredImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($featuredTitle, ENT_QUOTES, 'UTF-8') ?>" class="news-side-featured-image" loading="lazy" decoding="async">
                <?php else: ?>
                  <div class="news-side-featured-image news-image-fallback">Image indisponible</div>
                <?php endif; ?>
                <p class="news-card-kicker"><?= htmlspecialchars($featuredCategory, ENT_QUOTES, 'UTF-8') ?></p>
                <h3><?= htmlspecialchars($featuredTitle, ENT_QUOTES, 'UTF-8') ?></h3>
              </a>
            </article>
          </section>
        <?php endif; ?>

        <section class="news-sidebar-block" aria-labelledby="read-also-title">
          <h2 id="read-also-title">A lire aussi</h2>
          <?php if (count($readAlsoArticles) > 0): ?>
            <div class="news-readalso-list">
              <?php foreach ($readAlsoArticles as $item): ?>
                <?php
                  $itemTitle = (string) ($item['title'] ?? 'Article');
                  $itemSlug = (string) ($item['slug'] ?? '');
                  $itemCover = $toMediaUrl($resolveFeaturedImagePath($item));
                  $itemCategory = 'Actualite';
                  if (is_array($item['categories'] ?? null) && count($item['categories']) > 0) {
                      $itemCategory = (string) (($item['categories'][0]['name'] ?? 'Actualite'));
                  }
                ?>
                <article class="news-readalso-item">
                  <a href="/article/<?= rawurlencode($itemSlug) ?>" class="news-readalso-link">
                    <?php if ($itemCover !== ''): ?>
                      <img src="<?= htmlspecialchars($itemCover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?>" class="news-readalso-thumb" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="news-readalso-copy">
                      <p class="news-card-kicker"><?= htmlspecialchars($itemCategory, ENT_QUOTES, 'UTF-8') ?></p>
                      <h3><?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                  </a>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p>Aucun autre article disponible pour le moment.</p>
          <?php endif; ?>
        </section>

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
