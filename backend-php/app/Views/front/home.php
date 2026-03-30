<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
    $headerCategories = is_array($categories ?? null) ? $categories : [];
    $activeCategorySlug = trim((string) ($selectedCategorySlug ?? ''));
    $activeCategoryName = trim((string) ($selectedCategoryName ?? ''));
    $baseUrl = rtrim((string) ($baseUrl ?? ''), '/');
    $canonicalUrl = (string) ($canonicalUrl ?? ($baseUrl . '/'));
    $seoTitle = $activeCategoryName !== ''
      ? ($activeCategoryName . ' : actualites et analyses | Iran Info')
      : 'Actualites Iran et international en continu | Iran Info';
    $seoDescription = $activeCategoryName !== ''
      ? ('Retrouvez les articles de la categorie ' . $activeCategoryName . ' avec analyses, contexte et points cles pour suivre l actualite en continu.')
      : 'Suivez les actualites sur l Iran et l international avec analyses, contexte, reportages et dossiers de reference, mis a jour chaque jour par notre redaction.';

    $initialArticles = is_array($articles ?? null) ? $articles : [];
    $itemList = [];
    $position = 1;
    foreach ($initialArticles as $article) {
        $slug = (string) ($article['slug'] ?? '');
        $name = (string) ($article['title'] ?? 'Article');
        if ($slug === '') {
            continue;
        }
        $itemList[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'url' => $baseUrl . '/article/' . rawurlencode($slug),
        ];
        $position++;
        if ($position > 10) {
            break;
        }
    }

    $homeSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $seoTitle,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'inLanguage' => 'fr',
    ];

    if (count($itemList) > 0) {
        $homeSchema['mainEntity'] = [
            '@type' => 'ItemList',
            'itemListElement' => $itemList,
        ];
    }
  ?>
  <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <script type="application/ld+json"><?= json_encode($homeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <link rel="stylesheet" href="/assets/frontoffice.css">
</head>
<body>
<div class="news-shell">
  <header class="news-header">
    <div class="news-header-inner">
      <a href="/" class="news-logo" aria-label="Aller a l'accueil Iran Info">IRAN INFO</a>
      <nav class="news-nav" aria-label="Navigation principale">
        <a href="/" class="news-nav-link <?= $activeCategorySlug === '' ? 'active' : '' ?>">Accueil</a>
        <?php foreach ($headerCategories as $category): ?>
          <?php
            $catName = (string) ($category['name'] ?? 'Categorie');
            $catSlug = (string) ($category['slug'] ?? '');
            if ($catSlug === '') {
                continue;
            }
          ?>
          <a
            href="/?category=<?= rawurlencode($catSlug) ?>"
            class="news-nav-link <?= $activeCategorySlug === $catSlug ? 'active' : '' ?>"
          >
            <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="news-header-actions">
        <a href="/backoffice/login" class="news-admin-link">Admin</a>
      </div>
    </div>
  </header>

  <main class="news-main" id="main-content">
    <section class="news-page-title" aria-labelledby="home-title">
      <h1 id="home-title">L'actualite internationale et iranienne en continu</h1>
    </section>

    <div class="news-home-grid">
      <div class="news-home-main">
        <section class="news-section" aria-labelledby="latest-title">
          <div class="news-section-head">
            <h2 id="latest-title"><?= $activeCategoryName !== '' ? ('Articles : ' . htmlspecialchars($activeCategoryName, ENT_QUOTES, 'UTF-8')) : 'Tous les articles' ?></h2>
          </div>

          <div class="news-filters" aria-label="Filtres articles">
            <label>
              Rechercher un article
              <input id="queryInput" type="search" placeholder="Titre, resume ou contenu">
            </label>

            <label>
              Date
              <input id="dateInput" type="date">
            </label>

            <label>
              Tri
              <select id="sortSelect">
                <option value="newest">Plus recent</option>
                <option value="oldest">Plus ancien</option>
              </select>
            </label>
          </div>

          <div id="news-feed-list" class="news-feed-list"></div>
          <p id="emptyMessage" style="display:none;">Aucun article ne correspond aux filtres choisis.</p>
        </section>
      </div>
    </div>
  </main>

  <footer class="news-footer">
    <div class="news-footer-inner">
      <p>Iran Info | Journal numerique international</p>
    </div>
  </footer>
</div>

<script>
  (function () {
    var API_BASE = '/api';
    var state = {
      articles: [],
      loading: true,
      query: '',
      selectedDate: '',
      sortOrder: 'newest',
      selectedCategorySlug: <?= json_encode($activeCategorySlug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    function normalizeToken(token) {
      var cleaned = String(token || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');

      if (cleaned.length > 3 && cleaned.endsWith('es')) {
        return cleaned.slice(0, -2);
      }

      if (cleaned.length > 2 && (cleaned.endsWith('s') || cleaned.endsWith('x'))) {
        return cleaned.slice(0, -1);
      }

      return cleaned;
    }

    function normalizeTextForSearch(value) {
      return String(value || '')
        .split(/\s+/)
        .map(normalizeToken)
        .filter(Boolean);
    }

    function parsePostgresDate(value) {
      if (!value) return null;

      var raw = String(value).trim();
      if (!raw) return null;

      var normalized = raw.replace(' ', 'T');
      normalized = normalized.replace(/\.(\d{3})\d+/, '.$1');
      normalized = normalized.replace(/([+-]\d{2})$/, '$1:00');

      var date = new Date(normalized);
      if (!Number.isNaN(date.getTime())) {
        return date;
      }

      date = new Date(raw);
      if (!Number.isNaN(date.getTime())) {
        return date;
      }

      return null;
    }

    function articleDateValue(article) {
      if (!article) return '';
      return article.publishedAt || article.createdAt || article.updatedAt || '';
    }

    function toShortDate(value) {
      var date = parsePostgresDate(value);
      if (!date) return 'Date inconnue';
      return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(date);
    }

    function readCategory(article) {
      if (Array.isArray(article && article.categories) && article.categories.length > 0) {
        return article.categories[0].name;
      }
      return 'Actualite';
    }

    function escapeHtml(text) {
      return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function getMediaUrl(path) {
      if (!path) return '';
      if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0) {
        return path;
      }
      if (path.charAt(0) === '/') {
        return path;
      }
      return '/' + path;
    }

    function getArticleImageUrl(article) {
      var path = article && article.coverImagePath;
      if (!path) return '';
      return getMediaUrl(path);
    }

    function getSecondaryImageUrl(article) {
      var gallery = Array.isArray(article && article.galleryImages) ? article.galleryImages : [];
      if (gallery.length > 0 && gallery[0] && gallery[0].path) {
        return getMediaUrl(gallery[0].path);
      }
      return getArticleImageUrl(article);
    }

    function getFeaturedImageUrl(article) {
      var gallery = Array.isArray(article && article.galleryImages) ? article.galleryImages : [];
      var tagged = gallery.find(function (img) {
        var alt = String(img && img.alt || '').toLowerCase();
        return alt.includes('a la une') || alt.includes('alaune') || alt.includes('featured') || alt.includes('une');
      });

      if (tagged && tagged.path) {
        return getMediaUrl(tagged.path);
      }

      return getArticleImageUrl(article);
    }

    function articleUniqueKey(article) {
      if (article && article.id !== undefined && article.id !== null) {
        return 'id:' + String(article.id);
      }
      return 'slug:' + String(article && article.slug || '');
    }

    function uniqueArticles(articles) {
      var seen = new Set();
      return (Array.isArray(articles) ? articles : []).filter(function (article) {
        var key = articleUniqueKey(article);
        if (seen.has(key)) {
          return false;
        }
        seen.add(key);
        return true;
      });
    }

    function getFilteredArticles(options) {
      var excludeSelectedCategory = !!(options && options.excludeSelectedCategory);
      var queryTokens = normalizeTextForSearch(state.query);

      return state.articles
        .filter(function (article) {
          if (!state.selectedCategorySlug) return true;
          var categories = Array.isArray(article && article.categories) ? article.categories : [];
          var hasSelectedCategory = categories.some(function (category) {
            return String(category && category.slug || '') === state.selectedCategorySlug;
          });
          return excludeSelectedCategory ? !hasSelectedCategory : hasSelectedCategory;
        })
        .filter(function (article) {
          if (queryTokens.length === 0) return true;

          var textTokens = normalizeTextForSearch(
            (article.title || '') + ' ' + (article.metaDescription || '') + ' ' + (article.content || '')
          );
          var textSet = new Set(textTokens);

          return queryTokens.every(function (queryToken) {
            if (textSet.has(queryToken)) {
              return true;
            }

            return textTokens.some(function (textToken) {
              return textToken.includes(queryToken);
            });
          });
        })
        .filter(function (article) {
          if (!state.selectedDate) return true;
          var baseDate = articleDateValue(article);
          if (!baseDate) return false;

          var articleDate = parsePostgresDate(baseDate);
          if (!articleDate) return false;

          var yyyy = articleDate.getFullYear();
          var mm = String(articleDate.getMonth() + 1).padStart(2, '0');
          var dd = String(articleDate.getDate()).padStart(2, '0');
          return yyyy + '-' + mm + '-' + dd === state.selectedDate;
        })
        .sort(function (a, b) {
          var leftFeatured = a && a.featured ? 1 : 0;
          var rightFeatured = b && b.featured ? 1 : 0;
          if (leftFeatured !== rightFeatured) {
            return rightFeatured - leftFeatured;
          }

          var leftDate = parsePostgresDate(articleDateValue(a));
          var rightDate = parsePostgresDate(articleDateValue(b));
          var left = leftDate ? leftDate.getTime() : 0;
          var right = rightDate ? rightDate.getTime() : 0;
          return state.sortOrder === 'oldest' ? left - right : right - left;
        });
    }

    function articleCardHtml(article) {
      var summary = article.metaDescription || ((article.content || '').slice(0, 220) + '...');
      var imageHtml = '';
      var imageUrl = getSecondaryImageUrl(article);

      if (imageUrl) {
        imageHtml =
          '<a href="/article/' + encodeURIComponent(article.slug) + '" aria-label="Ouvrir ' + escapeHtml(article.title) + '" class="news-card-image-link-large">' +
          '<img src="' + imageUrl + '" alt="' + escapeHtml(article.coverImageAlt || article.title) + '" class="news-card-image news-card-image-large" loading="lazy" decoding="async" />' +
          '</a>';
      }

      return (
        '<article class="news-article-card news-article-card-large">' +
        imageHtml +
        '<p class="news-card-kicker">' + escapeHtml(readCategory(article)) + '</p>' +
        '<h3 class="news-card-title"><a href="/article/' + encodeURIComponent(article.slug) + '">' + escapeHtml(article.title) + '</a></h3>' +
        '<p class="news-card-summary-large">' + escapeHtml(summary) + '</p>' +
        '<p class="news-card-date">' + escapeHtml(toShortDate(articleDateValue(article))) + '</p>' +
        '</article>'
      );
    }

    function popularItemHtml(article, index) {
      var rank = Number(index || 0) + 1;
      return (
        '<article class="news-popular-item">' +
        '<p class="news-popular-rank">Top ' + rank + '</p>' +
        '<h3><a href="/article/' + encodeURIComponent(article.slug) + '">' + escapeHtml(article.title) + '</a></h3>' +
        '<p>' + escapeHtml(readCategory(article)) + ' | ' + escapeHtml(toShortDate(articleDateValue(article))) + '</p>' +
        '</article>'
      );
    }

    function featuredSectionHtml(articles, sideArticles, popularArticles, remainingArticles) {
      if (articles.length === 0) return '';

      var lead = articles[0];
      var leadKey = articleUniqueKey(lead);
      var sideCandidates = uniqueArticles(sideArticles).length > 0 ? uniqueArticles(sideArticles) : uniqueArticles(articles);
      var highlights = sideCandidates
        .filter(function (item) {
          return articleUniqueKey(item) !== leadKey;
        })
        .slice(0, 6);
      var cleanedPopular = uniqueArticles(popularArticles);

      var popularHtml = cleanedPopular.map(function (item, index) {
        return popularItemHtml(item, index);
      }).join('');
      var remainingHtml = (Array.isArray(remainingArticles) ? remainingArticles : []).map(articleCardHtml).join('');
      var leadSummary = lead.metaDescription || ((lead.content || '').slice(0, 240) + '...');
      var leadImage = getFeaturedImageUrl(lead);
      var leadImageHtml = leadImage
        ? '<img src="' + leadImage + '" alt="' + escapeHtml(lead.coverImageAlt || lead.title) + '" class="news-lead-image" loading="lazy" decoding="async" />'
        : '<div class="news-lead-image news-image-fallback">Image indisponible</div>';

      var highlightsHtml = highlights.map(function (item) {
        var image = getFeaturedImageUrl(item);
        var thumb = image
          ? '<img src="' + image + '" alt="' + escapeHtml(item.coverImageAlt || item.title) + '" class="news-highlight-thumb" loading="lazy" decoding="async" />'
          : '<div class="news-highlight-thumb news-image-fallback">Image</div>';

        return (
          '<article class="news-highlight-item">' +
          '<a href="/article/' + encodeURIComponent(item.slug) + '" class="news-highlight-link">' +
          thumb +
          '<div class="news-highlight-copy">' +
          '<p class="news-card-kicker">' + escapeHtml(readCategory(item)) + '</p>' +
          '<h3>' + escapeHtml(item.title) + '</h3>' +
          '<p class="news-card-date">' + escapeHtml(toShortDate(articleDateValue(item))) + '</p>' +
          '</div>' +
          '</a>' +
          '</article>'
        );
      }).join('');

      return (
        '<section class="news-featured-block" aria-label="Image principale et contenus de droite">' +
        '<div class="news-home-primary">' +
        '<article class="news-lead-card">' +
        '<a href="/article/' + encodeURIComponent(lead.slug) + '" class="news-lead-image-link">' + leadImageHtml + '</a>' +
        '<p class="news-card-kicker">Image principale</p>' +
        '<h3 class="news-lead-title"><a href="/article/' + encodeURIComponent(lead.slug) + '">' + escapeHtml(lead.title) + '</a></h3>' +
        '<p class="news-card-date">' + escapeHtml(toShortDate(articleDateValue(lead))) + '</p>' +
        '<p class="news-card-summary-large">' + escapeHtml(leadSummary) + '</p>' +
        '</article>' +
        '<div class="news-home-secondary-list">' + remainingHtml + '</div>' +
        '</div>' +
        '<aside class="news-home-right-rail">' +
        '<section class="news-sidebar-block" aria-labelledby="highlights-title">' +
        '<h3 id="highlights-title" class="news-highlights-title">A la une</h3>' +
        '<div class="news-highlights-list">' + (highlightsHtml || '<p>Aucun article a la une supplementaire.</p>') + '</div>' +
        '</section>' +
        '<section class="news-sidebar-block" aria-labelledby="popular-title">' +
        '<h3 id="popular-title">Articles populaires</h3>' +
        '<div class="news-popular-list">' + (popularHtml || '<p>Aucun autre article populaire.</p>') + '</div>' +
        '</section>' +
        '<section class="news-sidebar-block" aria-labelledby="newsletter-title">' +
        '<h3 id="newsletter-title">Newsletter</h3>' +
        '<p>Contact redaction: contact@iraninfo.local</p>' +
        '</section>' +
        '</aside>' +
        '</section>'
      );
    }

    function render() {
      var filteredArticles = uniqueArticles(getFilteredArticles());
      var sideArticles = state.selectedCategorySlug
        ? uniqueArticles(getFilteredArticles({ excludeSelectedCategory: true }))
        : filteredArticles;

      if (state.selectedCategorySlug && sideArticles.length === 0) {
        sideArticles = filteredArticles;
      }

      var popularArticles = sideArticles;
      var feedList = document.getElementById('news-feed-list');
      var emptyMessage = document.getElementById('emptyMessage');

      var remainingArticles = filteredArticles.length > 0 ? filteredArticles.slice(1) : [];
      var featuredHtml = featuredSectionHtml(filteredArticles, sideArticles, popularArticles, remainingArticles);
      feedList.innerHTML = featuredHtml;

      if (!state.loading && filteredArticles.length === 0) {
        emptyMessage.style.display = '';
      } else {
        emptyMessage.style.display = 'none';
      }
    }

    async function loadArticles() {
      state.loading = true;
      render();

      try {
        var response = await fetch(API_BASE + '/articles');
        var data = await response.json();
        state.articles = Array.isArray(data) ? data : [];
      } catch (error) {
        state.articles = [];
      } finally {
        state.loading = false;
        render();
      }
    }

    document.getElementById('queryInput').addEventListener('input', function (event) {
      state.query = event.target.value;
      render();
    });

    document.getElementById('dateInput').addEventListener('change', function (event) {
      state.selectedDate = event.target.value;
      render();
    });

    document.getElementById('sortSelect').addEventListener('change', function (event) {
      state.sortOrder = event.target.value;
      render();
    });

    loadArticles();
  })();
</script>
</body>
</html>
