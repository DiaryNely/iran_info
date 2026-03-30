<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iran Info | Layout public</title>
  <link rel="stylesheet" href="<%= request.getContextPath() %>/assets/frontoffice.css" />
  <jsp:include page="seo.jsp" />
</head>
<body>
  <div class="news-shell">
    <header class="news-header">
      <div class="news-header-inner">
        <a href="HomeFrontPage.jsp" class="news-logo" aria-label="Aller a l'accueil Iran Info">
          IRAN INFO
        </a>

        <nav class="news-nav" aria-label="Navigation principale">
          <a href="HomeFrontPage.jsp" class="news-nav-link">Accueil</a>
        </nav>

        <div class="news-header-actions">
          <a href="HomeFrontPage.jsp" class="news-subscribe-btn">S'abonner</a>
          <a href="<%= request.getContextPath() %>/backoffice/login" class="news-admin-link">Admin</a>
        </div>
      </div>
    </header>

    <main class="news-main" id="main-content">
      <section class="news-section" aria-labelledby="layout-title">
        <h1 id="layout-title">Public Layout</h1>
        <p>Ce fichier correspond au composant de layout React. Les pages JSP concretes sont:</p>
        <ul>
          <li><a href="HomeFrontPage.jsp">HomeFrontPage.jsp</a></li>
          <li><a href="ArticleFrontPage.jsp">ArticleFrontPage.jsp?slug=...</a></li>
        </ul>
      </section>
    </main>

    <footer class="news-footer">
      <div class="news-footer-inner">
        <p>Iran Info | Journal numerique international</p>
      </div>
    </footer>
  </div>

  <script>
    (function () {
      var current = window.location.pathname.split('/').pop();
      var links = document.querySelectorAll('.news-nav-link');
      links.forEach(function (link) {
        var href = link.getAttribute('href') || '';
        if ((current === '' && href === 'HomeFrontPage.jsp') || current === href) {
          link.classList.add('active');
        }
      });
    })();
  </script>
</body>
</html>
