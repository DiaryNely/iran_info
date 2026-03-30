<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%
  String baseUrl = request.getScheme() + "://" + request.getServerName()
      + ((request.getServerPort() == 80 || request.getServerPort() == 443) ? "" : ":" + request.getServerPort())
      + request.getContextPath();
  String seoTitle = "Actualites Iran et international en continu | Iran Info";
  String seoDescription = "Suivez les actualites sur l Iran et l international avec analyses, contexte, reportages et dossiers de reference, mis a jour chaque jour par notre redaction.";
  String canonicalUrl = baseUrl + "/";
%>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><%= seoTitle %></title>
  <meta name="description" content="<%= seoDescription %>" />
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large" />
  <link rel="canonical" href="<%= canonicalUrl %>" />
  <meta property="og:title" content="<%= seoTitle %>" />
  <meta property="og:description" content="<%= seoDescription %>" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="<%= canonicalUrl %>" />
  <link rel="stylesheet" href="<%= request.getContextPath() %>/assets/frontoffice.css" />
  <jsp:include page="seo.jsp" />
</head>
<body>
  <div class="news-shell">
    <header class="news-header">
      <div class="news-header-inner">
        <a href="<%= request.getContextPath() %>/" class="news-logo" aria-label="Aller a l'accueil Iran Info">IRAN INFO</a>

        <nav class="news-nav" aria-label="Navigation principale">
          <a href="<%= request.getContextPath() %>/" class="news-nav-link active">Accueil</a>
        </nav>

        <div class="news-header-actions">
          <a href="<%= request.getContextPath() %>/backoffice/login" class="news-admin-link">Admin</a>
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
              <h2 id="latest-title">Tous les articles</h2>
            </div>

            <div class="news-filters" aria-label="Filtres articles">
              <label>
                Rechercher un article
                <input id="queryInput" type="search" placeholder="Titre, resume ou contenu" />
              </label>

              <label>
                Date
                <input id="dateInput" type="date" />
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
            <p id="emptyMessage" style="display: none;">Aucun article ne correspond aux filtres choisis.</p>
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
      var API_BASE = '<%= request.getContextPath() %>/api';
      var state = {
        articles: [],
        loading: true,
        query: '',
        selectedDate: '',
        sortOrder: 'newest',
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

      function toShortDate(value) {
        if (!value) return 'Date inconnue';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Date inconnue';
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

      function getFilteredArticles() {
        var queryTokens = normalizeTextForSearch(state.query);

        return state.articles
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
            if (!article.createdAt) return false;

            var articleDate = new Date(article.createdAt);
            if (Number.isNaN(articleDate.getTime())) return false;

            var yyyy = articleDate.getFullYear();
            var mm = String(articleDate.getMonth() + 1).padStart(2, '0');
            var dd = String(articleDate.getDate()).padStart(2, '0');
            return yyyy + '-' + mm + '-' + dd === state.selectedDate;
          })
          .sort(function (a, b) {
            var left = new Date(a.createdAt || 0).getTime();
            var right = new Date(b.createdAt || 0).getTime();
            return state.sortOrder === 'oldest' ? left - right : right - left;
          });
      }

      function articleCardHtml(article) {
        var summary = article.metaDescription || ((article.content || '').slice(0, 220) + '...');
        var imageHtml = '';
        var imageUrl = getArticleImageUrl(article, 1400);

        if (imageUrl) {
          imageHtml =
            '<a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(article.slug) + '" aria-label="Ouvrir ' + escapeHtml(article.title) + '" class="news-card-image-link-large">' +
            '<img src="' + imageUrl + '" alt="' + escapeHtml(article.coverImageAlt || article.title) + '" class="news-card-image news-card-image-large" loading="lazy" decoding="async" />' +
            '</a>';
        }

        return (
          '<article class="news-article-card news-article-card-large">' +
          imageHtml +
          '<p class="news-card-kicker">' + escapeHtml(readCategory(article)) + '</p>' +
          '<h3 class="news-card-title"><a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(article.slug) + '">' + escapeHtml(article.title) + '</a></h3>' +
          '<p class="news-card-summary-large">' + escapeHtml(summary) + '</p>' +
          '<p class="news-card-date">' + escapeHtml(toShortDate(article.createdAt)) + '</p>' +
          '</article>'
        );
      }

      function popularItemHtml(article) {
        return (
          '<article class="news-popular-item">' +
          '<h3><a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(article.slug) + '">' + escapeHtml(article.title) + '</a></h3>' +
          '<p>' + escapeHtml(toShortDate(article.createdAt)) + '</p>' +
          '</article>'
        );
      }

      function getArticleImageUrl(article, width) {
        var path = article && article.coverImagePath;
        if (!path) return '';

        return getMediaUrl(path, width);
      }

      function getMediaUrl(path, width) {
        if (!path) return '';

        if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0) {
          return SeoTools.optimizeImageUrl(path, width);
        }

        if (path.charAt(0) === '/') {
          return '<%= request.getContextPath() %>' + path;
        }

        return '<%= request.getContextPath() %>/' + path;
      }

      function featuredSectionHtml(articles, popularArticles) {
        if (articles.length === 0) return '';

        var lead = articles[0];
        var highlights = articles.slice(1, 5);
        var leadGallery = Array.isArray(lead.galleryImages) ? lead.galleryImages : [];

        if (highlights.length === 0 && leadGallery.length > 0) {
          highlights = leadGallery.slice(0, 4).map(function (image, index) {
            return {
              __fromGallery: true,
              id: 'gallery-' + index,
              slug: lead.slug,
              title: lead.title,
              createdAt: lead.createdAt,
              categoryName: readCategory(lead),
              coverImageAlt: image.alt || lead.coverImageAlt || lead.title,
              imagePath: image.path
            };
          });
        }

        var popularHtml = popularArticles.map(popularItemHtml).join('');
        var leadSummary = lead.metaDescription || ((lead.content || '').slice(0, 240) + '...');
        var leadImage = getArticleImageUrl(lead, 1600);
        var leadImageHtml = leadImage
          ? '<img src="' + leadImage + '" alt="' + escapeHtml(lead.coverImageAlt || lead.title) + '" class="news-lead-image" loading="lazy" decoding="async" />'
          : '<div class="news-lead-image news-image-fallback">Image indisponible</div>';

        var highlightsHtml = highlights.map(function (item) {
          var image = item.__fromGallery
            ? getMediaUrl(item.imagePath, 700)
            : getArticleImageUrl(item, 700);
          var thumb = image
            ? '<img src="' + image + '" alt="' + escapeHtml(item.coverImageAlt || item.title) + '" class="news-highlight-thumb" loading="lazy" decoding="async" />'
            : '<div class="news-highlight-thumb news-image-fallback">Image</div>';

          return (
            '<article class="news-highlight-item">' +
            '<a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(item.slug) + '" class="news-highlight-link">' +
            thumb +
            '<div class="news-highlight-copy">' +
            '<p class="news-card-kicker">' + escapeHtml(readCategory(item)) + '</p>' +
            '<h3>' + escapeHtml(item.title) + '</h3>' +
            '<p class="news-card-date">' + escapeHtml(toShortDate(item.createdAt)) + '</p>' +
            '</div>' +
            '</a>' +
            '</article>'
          );
        }).join('');

        return (
          '<section class="news-featured-block" aria-label="Image principale et a la une">' +
          '<article class="news-lead-card">' +
          '<a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(lead.slug) + '" class="news-lead-image-link">' + leadImageHtml + '</a>' +
          '<p class="news-card-kicker">Image principale</p>' +
          '<h3 class="news-lead-title"><a href="<%= request.getContextPath() %>/article/' + encodeURIComponent(lead.slug) + '">' + escapeHtml(lead.title) + '</a></h3>' +
          '<p class="news-card-summary-large">' + escapeHtml(leadSummary) + '</p>' +
          '</article>' +
          '<aside class="news-highlights">' +
          '<h3 class="news-highlights-title">A la une</h3>' +
          '<div class="news-highlights-list">' + highlightsHtml + '</div>' +
          '<section class="news-sidebar-block news-inline-side-block" aria-labelledby="popular-title">' +
          '<h4 id="popular-title">Articles populaires</h4>' +
          '<div class="news-popular-list">' + popularHtml + '</div>' +
          '</section>' +
          '<section class="news-sidebar-block news-inline-side-block" aria-labelledby="newsletter-title">' +
          '<h4 id="newsletter-title">Newsletter</h4>' +
          '<p>Contact redaction: contact@iraninfo.local</p>' +
          '</section>' +
          '</aside>' +
          '</section>'
        );
      }

      function render() {
        var filteredArticles = getFilteredArticles();
        var popularArticles = filteredArticles.slice(0, 5);
        var remainingArticles = filteredArticles.slice(5);
        var feedList = document.getElementById('news-feed-list');
        var emptyMessage = document.getElementById('emptyMessage');

        var featuredHtml = featuredSectionHtml(filteredArticles, popularArticles);
        var feedCardsHtml = remainingArticles.map(articleCardHtml).join('');
        feedList.innerHTML = featuredHtml + feedCardsHtml;

        if (!state.loading && filteredArticles.length === 0) {
          emptyMessage.style.display = '';
        } else {
          emptyMessage.style.display = 'none';
        }
      }

      function setSeo() {
        var title = SeoTools.buildSeoTitle('Actualites Iran et international en continu');
        var description = SeoTools.buildSeoDescription(
          "Suivez les actualites sur l'Iran, le Moyen-Orient et le monde avec analyses, contexte, reportages et articles de reference actualises chaque jour.",
          "Actualites, analyses et dossiers sur l'Iran et l'international."
        );

        SeoTools.setDocumentSeo({
          title: title,
          description: description,
          path: '/',
          type: 'website'
        });

        SeoTools.upsertJsonLd('ld-home-website', {
          '@context': 'https://schema.org',
          '@type': 'WebSite',
          name: 'Iran Info',
          url: 'https://iran-info.local/',
          inLanguage: 'fr-FR',
          potentialAction: {
            '@type': 'SearchAction',
            target: 'https://iran-info.local/?q={search_term_string}',
            'query-input': 'required name=search_term_string'
          }
        });
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

      setSeo();
      loadArticles();

      window.addEventListener('beforeunload', function () {
        SeoTools.removeJsonLd('ld-home-website');
      });
    })();
  </script>
</body>
</html>
