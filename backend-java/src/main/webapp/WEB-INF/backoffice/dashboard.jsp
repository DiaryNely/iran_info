<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<jsp:include page="/WEB-INF/backoffice/includes/header.jsp">
    <jsp:param name="pageTitle" value="Dashboard"/>
    <jsp:param name="activePage" value="dashboard"/>
</jsp:include>

<section class="bo-section">
    <div class="bo-title-row">
        <h2>Dashboard</h2>
        <p>Vue rapide des contenus.</p>
    </div>

    <c:if test="${not empty error}">
        <div class="toast-inline toast-error">${error}</div>
    </c:if>

    <div class="kpis">
        <article class="kpi-card">
            <h3>Articles</h3>
            <strong>${totalArticles != null ? totalArticles : 0}</strong>
        </article>
        <article class="kpi-card">
            <h3>Categories</h3>
            <strong>${totalCategories != null ? totalCategories : 0}</strong>
        </article>
        <article class="kpi-card">
            <h3>Publies</h3>
            <strong>${totalPublished != null ? totalPublished : 0}</strong>
        </article>
        <article class="kpi-card">
            <h3>A la une</h3>
            <strong>${totalFeatured != null ? totalFeatured : 0}</strong>
        </article>
    </div>

    <div class="page-grid dashboard-grid">
        <div class="card">
            <h3>Derniers articles</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Slug</th>
                        <th>A la une</th>
                    </tr>
                    </thead>
                    <tbody>
                    <c:choose>
                        <c:when test="${not empty articles}">
                            <c:forEach var="article" items="${articles}" end="7">
                                <tr>
                                    <td>${article.id}</td>
                                    <td>${article.title}</td>
                                    <td>${article.slug}</td>
                                    <td>${article.featured ? 'Oui' : 'Non'}</td>
                                </tr>
                            </c:forEach>
                        </c:when>
                        <c:otherwise>
                            <tr><td colspan="4">Aucun article.</td></tr>
                        </c:otherwise>
                    </c:choose>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Categories</h3>
            <div class="chips">
                <c:choose>
                    <c:when test="${not empty categories}">
                        <c:forEach var="cat" items="${categories}">
                            <span class="chip">${cat.name}</span>
                        </c:forEach>
                    </c:when>
                    <c:otherwise>
                        <p>Aucune categorie.</p>
                    </c:otherwise>
                </c:choose>
            </div>
        </div>
    </div>
</section>

<jsp:include page="/WEB-INF/backoffice/includes/footer.jsp"/>