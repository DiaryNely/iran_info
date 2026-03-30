<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%
  String slug = request.getParameter("slug");
  if (slug == null) {
    slug = "";
  }
  String baseUrl = request.getScheme() + "://" + request.getServerName()
      + ((request.getServerPort() == 80 || request.getServerPort() == 443) ? "" : ":" + request.getServerPort())
      + request.getContextPath();
  String seoTitle = "Article d actualite | Iran Info";
  String seoDescription = "Analyse detaillee, contexte et lecture complete de l actualite sur Iran Info, avec points cles, reperes et lecture accessible pour tous les publics.";
  String canonicalUrl = baseUrl + "/frontoffice/ArticleFrontPage.jsp";
  if (!slug.isBlank()) {
    canonicalUrl += "?slug=" + java.net.URLEncoder.encode(slug, "UTF-8");
  }
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
  <meta property="og:type" content="article" />
  <meta property="og:url" content="<%= canonicalUrl %>" />
  <link rel="stylesheet" href="<%= request.getContextPath() %>/assets/frontoffice.css" />
  <jsp:include page="seo.jsp" />
</head>
<body>
  <div class="news-shell">
    <header class="news-header">
      <div class="news-header-inner">
        <a href="HomeFrontPage.jsp" class="news-logo" aria-label="Aller a l'accueil Iran Info">IRAN INFO</a>

        <nav class="news-nav" aria-label="Navigation principale">
          <a href="HomeFrontPage.jsp" class="news-nav-link">Accueil</a>
        </nav>

        <div class="news-header-actions">
          <a href="HomeFrontPage.jsp" class="news-subscribe-btn">S'abonner</a>
          <a href="<%= request.getContextPath() %>/backoffice/login" class="news-admin-link">Admin</a>
        </div>
      </div>
    </header>

    <main class="news-main">
      <div id="article-root"></div>
    </main>

    <footer class="news-footer">
      <div class="news-footer-inner">
        <p>Iran Info | Journal numerique international</p>
      </div>
    </footer>
  </div>

  <script>
    (function () {
      var slug = decodeURIComponent('<%= java.net.URLEncoder.encode(slug, "UTF-8") %>');
      var API_BASE = '<%= request.getContextPath() %>/api';
      var state = {
        article: null,
        allArticles: [],
        loading: true,
      };

      function toShortDate(value) {
        if (!value) return 'Date inconnue';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Date inconnue';
        return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(date);
      }

      function escapeHtml(text) {
        return String(text || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/\"/g, '&quot;')
          .replace(/'/g, '&#39;');
      }

      function getParagraphs(content) {
        if (!content) return [];

        var parts = content
          .split(/\n{2,}|\r\n{2,}/)
          .map(function (part) {
            return part.trim();
          })
          .filter(Boolean);

        if (parts.length > 0) {
          return parts;
        }

        return content
          .split(/\.\s+/)
          .map(function (part) {
            return part.trim();
          })
          .filter(Boolean)
          .slice(0, 8)
          .map(function (part) {
            return part + '.';
          });
      }

      function getRelatedArticles(article, allArticles, categories) {
        if (!article) return [];

        var categorySlugs = new Set(categories.map(function (category) {
          return category.slug;
        }));

        return allArticles
          .filter(function (item) {
            return item.slug !== article.slug;
          })
          .filter(function (item) {
            return Array.isArray(item.categories) && item.categories.some(function (category) {
              return categorySlugs.has(category.slug);
            });
          })
          .slice(0, 4);
      }

      function setSeo(article) {
        var title = SeoTools.buildSeoTitle(article.title || 'Article d actualite');
        var description = SeoTools.buildSeoDescription(
          article.metaDescription || article.content || '',
          'Analyse detaillee, contexte et lecture complete de l actualite sur Iran Info.'
        );

        SeoTools.setDocumentSeo({
          title: title,
          description: description,
          path: '/article/' + slug,
          type: 'article'
        });

        SeoTools.upsertJsonLd('ld-article', {
          '@context': 'https://schema.org',
          '@type': 'Article',
          headline: article.title,
          description: description,
          image: article.coverImagePath ? [resolveImageUrl(article.coverImagePath, 1400)] : [],
          author: {
            '@type': 'Person',
            name: (article.author && article.author.username) || 'Redaction'
          },
          datePublished: article.createdAt || new Date().toISOString(),
          dateModified: article.updatedAt || article.createdAt || new Date().toISOString(),
          mainEntityOfPage: 'https://iran-info.local/article/' + slug,
          publisher: {
            '@type': 'Organization',
            name: 'Iran Info'
          },
          inLanguage: 'fr-FR'
        });
      }

      function renderLoading() {
        document.getElementById('article-root').innerHTML =
          '<main class="news-main"><p>Chargement de l\'article...</p></main>';
      }

      function renderNotFound() {
        document.getElementById('article-root').innerHTML =
          '<main class="news-main">' +
          '<section class="news-empty-state">' +
          '<h1>Article introuvable</h1>' +
          '<p>L\'URL demandee ne correspond a aucun contenu.</p>' +
          '<a href="HomeFrontPage.jsp" class="news-link-inline">Retour a l\'accueil</a>' +
          '</section>' +
          '</main>';
      }

      function renderArticle() {
        var article = state.article;
        var categories = Array.isArray(article.categories) ? article.categories : [];
        var relatedArticles = getRelatedArticles(article, state.allArticles, categories);
        var summary = article.metaDescription || ((article.content || '').slice(0, 220) + '...');
        var paragraphs = getParagraphs(article.content);

        var paragraphsHtml = paragraphs
          .map(function (paragraph) {
            return '<p>' + escapeHtml(paragraph) + '</p>';
          })
          .join('');

        var quoteHtml = '';
        if (paragraphs.length > 1) {
          var quote = paragraphs[1].length > 200 ? paragraphs[1].slice(0, 200) + '...' : paragraphs[1];
          quoteHtml = '<blockquote>' + escapeHtml(quote) + '</blockquote>';
        }

        var imageMain = '';
        if (article.coverImagePath) {
          imageMain =
            '<figure class="news-article-hero">' +
            '<img src="' + resolveImageUrl(article.coverImagePath, 1600) + '" alt="' + escapeHtml(article.coverImageAlt || article.title) + '" loading="lazy" decoding="async" />' +
            '</figure>';
        }

        var imageInfo = '';
        if (article.coverImagePath) {
          imageInfo =
            '<section class="news-sidebar-block" aria-labelledby="info-title">' +
            '<h2 id="info-title">Image informative</h2>' +
            '<img src="' + resolveImageUrl(article.coverImagePath, 700) + '" alt="Illustration: ' + escapeHtml(article.coverImageAlt || article.title) + '" class="news-info-image" loading="lazy" decoding="async" />' +
            '</section>';
        }

        var relatedHtml = '';
        if (relatedArticles.length === 0) {
          relatedHtml = '<p>Pas encore d\'articles lies.</p>';
        } else {
          relatedHtml = relatedArticles.map(function (relatedArticle) {
            return (
              '<article class="news-popular-item">' +
              '<h3><a href="ArticleFrontPage.jsp?slug=' + encodeURIComponent(relatedArticle.slug) + '">' + escapeHtml(relatedArticle.title) + '</a></h3>' +
              '<p>' + escapeHtml(toShortDate(relatedArticle.createdAt)) + '</p>' +
              '</article>'
            );
          }).join('');
        }

        var authorName = (article.author && article.author.username) || 'Redaction';
        var categoryName = categories.length > 0 ? categories[0].name : 'Actualite';
        var categoryLabel = categories.length > 0 ? categories[0].name.toUpperCase() : 'INTERNATIONAL';

        document.getElementById('article-root').innerHTML =
          '<div class="news-article-layout">' +
          '<article class="news-article-main">' +
          '<header class="news-article-header">' +
          '<p class="news-kicker">' + escapeHtml(categoryLabel) + '</p>' +
          '<h1>' + escapeHtml(article.title) + '</h1>' +
          '<p class="news-article-standfirst">' + escapeHtml(summary) + '</p>' +
          '<p class="news-meta-line">' + ((article.author && article.author.username) ? ('Par ' + escapeHtml(article.author.username) + ' | ') : '') + escapeHtml(toShortDate(article.createdAt)) + '</p>' +
          '</header>' +
          imageMain +
          '<section class="news-article-content" aria-labelledby="article-content-title">' +
          '<h2 id="article-content-title">Analyse</h2>' +
          paragraphsHtml +
          quoteHtml +
          '</section>' +
          '</article>' +
          '<aside class="news-sidebar" aria-label="Contexte et lectures associees">' +
          '<section class="news-sidebar-block" aria-labelledby="context-title">' +
          '<h2 id="context-title">Contexte</h2>' +
          '<ul class="news-context-list">' +
          '<li>Rubrique: ' + escapeHtml(categoryName) + '</li>' +
          '<li>Date de publication: ' + escapeHtml(toShortDate(article.createdAt)) + '</li>' +
          '<li>Auteur: ' + escapeHtml(authorName) + '</li>' +
          '</ul>' +
          '</section>' +
          imageInfo +
          '<section class="news-sidebar-block" aria-labelledby="related-title">' +
          '<h2 id="related-title">A lire aussi</h2>' +
          '<div class="news-popular-list">' + relatedHtml + '</div>' +
          '</section>' +
          '<a href="HomeFrontPage.jsp" class="news-all-articles-btn">Voir tous les articles</a>' +
          '</aside>' +
          '</div>';
      }

      async function loadArticle() {
        if (!slug) {
          state.loading = false;
          state.article = null;
          renderNotFound();
          return;
        }

        state.loading = true;
        renderLoading();

        try {
          var responses = await Promise.all([
            fetch(API_BASE + '/article/' + encodeURIComponent(slug)),
            fetch(API_BASE + '/articles')
          ]);

          var articleResponse = responses[0];
          var allArticlesResponse = responses[1];

          if (!articleResponse.ok) {
            throw new Error('Article introuvable');
          }

          var payloads = await Promise.all([
            articleResponse.json(),
            allArticlesResponse.json()
          ]);

          state.article = payloads[0];
          state.allArticles = Array.isArray(payloads[1]) ? payloads[1] : [];
          state.loading = false;
          setSeo(state.article);
          renderArticle();
        } catch (error) {
          state.article = null;
          state.allArticles = [];
          state.loading = false;
          renderNotFound();
        }
      }

      loadArticle();

      function resolveImageUrl(path, width) {
        if (!path) return '';

        if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0) {
          return SeoTools.optimizeImageUrl(path, width);
        }

        if (path.charAt(0) === '/') {
          return '<%= request.getContextPath() %>' + path;
        }

        return '<%= request.getContextPath() %>/' + path;
      }

      window.addEventListener('beforeunload', function () {
        SeoTools.removeJsonLd('ld-article');
      });
    })();
  </script>
</body>
</html>
