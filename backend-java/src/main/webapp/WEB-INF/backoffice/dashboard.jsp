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

    <div class="page-grid dashboard-grid-insights">
        <div class="card">
            <h3>Heatmap de publication</h3>
            <p class="muted-note">Publications par jour et tranche horaire (matin, midi, soir).</p>
            <div id="publication-heatmap" class="publication-heatmap"></div>
            <div class="heatmap-legend">
                <span>Faible</span>
                <div class="legend-scale">
                    <i class="lvl-0"></i><i class="lvl-1"></i><i class="lvl-2"></i><i class="lvl-3"></i><i class="lvl-4"></i>
                </div>
                <span>Forte</span>
            </div>
        </div>

        <div class="card">
            <h3>Analyse editoriale</h3>
            <p class="muted-note">Visualisation des contenus par categorie et par statut.</p>

            <div id="category-insight" class="category-insight"></div>

            <div class="charts-side-by-side">
                <div class="chart-panel">
                    <h4 class="chart-subtitle">Top categories</h4>
                    <div id="bars-by-category" class="bars-wrap"></div>
                </div>
                <div class="chart-panel">
                    <h4 class="chart-subtitle">Articles par statut</h4>
                    <div id="bars-by-status" class="bars-wrap"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-grid dashboard-grid">
        <div class="card dashboard-latest-card">
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
    </div>
</section>

<script id="dashboard-articles-data" type="application/json">${dashboardArticlesJson}</script>
<script>
(() => {
    const raw = document.getElementById('dashboard-articles-data');
    const articles = raw ? JSON.parse(raw.textContent || '[]') : [];

    const heatmapHost = document.getElementById('publication-heatmap');
    const categoryBarsHost = document.getElementById('bars-by-category');
    const categoryInsightHost = document.getElementById('category-insight');
    const statusBarsHost = document.getElementById('bars-by-status');

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildHeatmap() {
        const days = [
            { index: 1, label: 'Lundi' },
            { index: 2, label: 'Mardi' },
            { index: 3, label: 'Mercredi' },
            { index: 4, label: 'Jeudi' },
            { index: 5, label: 'Vendredi' },
            { index: 6, label: 'Samedi' },
            { index: 0, label: 'Dimanche' }
        ];

        const slots = [
            { key: 'matin', label: 'Matin' },
            { key: 'midi', label: 'Midi' },
            { key: 'soir', label: 'Soir' }
        ];

        const counts = new Map();
        days.forEach((d) => {
            slots.forEach((slot) => {
                counts.set(d.index + '-' + slot.key, 0);
            });
        });

        function getSlotByHour(hour) {
            if (hour >= 5 && hour < 12) return 'matin';
            if (hour >= 12 && hour < 18) return 'midi';
            return 'soir';
        }

        articles
            .filter((a) => a.status === 'published')
            .forEach((a) => {
                const d = new Date(a.publishedAt || a.createdAt || 0);
                if (Number.isNaN(d.getTime())) return;
                const dayIndex = d.getDay();
                const slot = getSlotByHour(d.getHours());
                const key = dayIndex + '-' + slot;
                counts.set(key, (counts.get(key) || 0) + 1);
            });

        const maxCount = Math.max(1, ...Array.from(counts.values(), (v) => v));

        let html = '<div class="heatmap-matrix">';
        html += '<div class="heat-cell-head"></div>';
        slots.forEach((slot) => {
            html += '<div class="heat-cell-head">' + slot.label + '</div>';
        });

        days.forEach((day) => {
            html += '<div class="heat-day-label">' + day.label + '</div>';
            slots.forEach((slot) => {
                const key = day.index + '-' + slot.key;
                const count = counts.get(key) || 0;
                const ratio = count / maxCount;
                const level = count === 0 ? 0 : Math.min(4, Math.max(1, Math.ceil(ratio * 4)));
                html += '<div class="heat-cell lvl-' + level + '" title="' + day.label + ' - ' + slot.label + ': ' + count + '">' + count + '</div>';
            });
        });

        html += '</div>';
        heatmapHost.innerHTML = html;
    }

    function buildBars(host, entries, emptyText) {
        if (!entries.length) {
            host.innerHTML = '<p class="muted-note">' + escapeHtml(emptyText) + '</p>';
            return;
        }

        const maxValue = Math.max(1, ...entries.map((x) => x.value));
        host.innerHTML = '<div class="bars-vertical">' + entries.map((item) => {
            const height = Math.max(8, Math.round((item.value / maxValue) * 100));
            return '<div class="vbar-item">'
                + '<div class="vbar-value">' + item.value + '</div>'
                + '<div class="vbar-track">'
                + '<div class="vbar-fill" style="height:' + height + '%"></div>'
                + '</div>'
                + '<div class="vbar-label" title="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</div>'
                + '</div>';
        }).join('') + '</div>';
    }

    function buildCategoryBars() {
        const map = new Map();
        articles.forEach((article) => {
            const cats = Array.isArray(article.categories) ? article.categories : [];
            if (cats.length === 0) {
                map.set('Sans categorie', (map.get('Sans categorie') || 0) + 1);
                return;
            }
            cats.forEach((cat) => {
                const name = cat && cat.name ? cat.name : 'Sans categorie';
                map.set(name, (map.get(name) || 0) + 1);
            });
        });

        const sorted = Array.from(map.entries())
            .map(([label, value]) => ({ label, value }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 8);

        if (!sorted.length) {
            categoryInsightHost.innerHTML = '<p class="muted-note">Aucune donnee categorie pour le moment.</p>';
            buildBars(categoryBarsHost, sorted, 'Aucune donnee categorie pour le moment.');
            return;
        }

        const total = sorted.reduce((acc, item) => acc + item.value, 0);
        const top = sorted[0];
        const topPct = total > 0 ? Math.round((top.value / total) * 100) : 0;

        categoryInsightHost.innerHTML = ''
            + '<div class="insight-pill">'
            + '<span class="insight-label">Categorie dominante</span>'
            + '<strong>' + escapeHtml(top.label) + '</strong>'
            + '<small>' + top.value + ' article(s) - ' + topPct + '%</small>'
            + '</div>'
            + '<div class="insight-pill">'
            + '<span class="insight-label">Articles classes</span>'
            + '<strong>' + total + '</strong>'
            + '<small>Top 8 categories</small>'
            + '</div>';

        const maxValue = Math.max(1, ...sorted.map((x) => x.value));
        categoryBarsHost.innerHTML = '<div class="bars-vertical bars-vertical-categories">' + sorted.map((item, idx) => {
            const height = Math.max(8, Math.round((item.value / maxValue) * 100));
            const pct = total > 0 ? Math.round((item.value / total) * 100) : 0;
            return ''
                + '<div class="vbar-item vbar-item-category">'
                + '<div class="vbar-rank">#' + (idx + 1) + '</div>'
                + '<div class="vbar-value">' + item.value + '</div>'
                + '<div class="vbar-track">'
                + '<div class="vbar-fill" style="height:' + height + '%"></div>'
                + '</div>'
                + '<div class="vbar-meta">'
                + '<div class="vbar-label" title="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</div>'
                + '<div class="vbar-percent">' + pct + '%</div>'
                + '</div>'
                + '</div>';
        }).join('') + '</div>';
    }

    function buildStatusBars() {
        const map = new Map();
        articles.forEach((article) => {
            const status = article && article.status ? article.status : 'unknown';
            map.set(status, (map.get(status) || 0) + 1);
        });

        const sorted = Array.from(map.entries())
            .map(([label, value]) => ({ label, value }))
            .sort((a, b) => b.value - a.value);

        buildBars(statusBarsHost, sorted, 'Aucun statut disponible.');
    }

    buildHeatmap();
    buildCategoryBars();
    buildStatusBars();
})();
</script>

<jsp:include page="/WEB-INF/backoffice/includes/footer.jsp"/>