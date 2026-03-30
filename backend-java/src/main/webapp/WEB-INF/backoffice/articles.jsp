<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" isELIgnored="true" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<jsp:include page="/WEB-INF/backoffice/includes/header.jsp">
    <jsp:param name="pageTitle" value="Articles"/>
    <jsp:param name="activePage" value="articles"/>
</jsp:include>

<section class="bo-section" data-page="articles">
    <div class="bo-title-row">
        <h2>Gestion des articles</h2>
        <p>CRUD editorial + SEO + images.</p>
    </div>

    <div id="bo-toast"></div>

    <div class="page-grid crud-layout">
        <div class="card crud-form-card">
            <h3 class="crud-title" id="article-form-title">Nouvel article</h3>
            <form id="article-form" class="form-grid">
                <input type="hidden" id="article-id" />

                <label>Titre
                    <input id="article-title" required minlength="5" maxlength="255" />
                </label>

                <label>Slug
                    <input id="article-slug" required minlength="5" maxlength="180" pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" />
                </label>

                <label>Meta title (50-60)
                    <input id="article-meta-title" required minlength="50" maxlength="60" />
                </label>

                <label>Meta description (150-160)
                    <textarea id="article-meta-description" rows="3" required minlength="150" maxlength="160"></textarea>
                </label>

                <label>Meta keywords
                    <input id="article-meta-keywords" required maxlength="255" />
                </label>

                <label class="checkbox-row">
                    <input id="article-featured" type="checkbox" />
                    Article a la une
                </label>

                <label>Image principale
                    <input id="article-cover" type="file" accept="image/*" />
                </label>

                <label>Alt image principale
                    <input id="article-cover-alt" required minlength="3" maxlength="160" />
                </label>

                <label>Galerie (multi)
                    <input id="article-gallery" type="file" accept="image/*" multiple />
                </label>

                <div id="gallery-alts-wrap" class="form-grid"></div>

                <label>Contenu
                    <textarea id="article-content" rows="7" required minlength="30"></textarea>
                </label>

                <div>
                    <p>Categories</p>
                    <div id="categories-chips" class="chips"></div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Sauvegarder</button>
                    <button id="article-reset" class="btn btn-secondary" type="button">Annuler</button>
                </div>
            </form>
        </div>

        <div class="card crud-list-card">
            <h3 class="crud-title">Liste des articles</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Slug</th>
                        <th>A la une</th>
                        <th>Galerie</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="articles-body">
                    <tr><td colspan="6">Chargement...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
  const API_BASE = `${window.location.origin}<%= request.getContextPath() %>/api`;
  const TOKEN = "<%= session.getAttribute("accessToken") != null ? session.getAttribute("accessToken") : "" %>";
  const articlesBody = document.getElementById('articles-body');

  const form = document.getElementById('article-form');
  const idEl = document.getElementById('article-id');
  const titleEl = document.getElementById('article-title');
  const slugEl = document.getElementById('article-slug');
  const contentEl = document.getElementById('article-content');
  const mtEl = document.getElementById('article-meta-title');
  const mdEl = document.getElementById('article-meta-description');
  const mkEl = document.getElementById('article-meta-keywords');
  const featuredEl = document.getElementById('article-featured');
  const coverEl = document.getElementById('article-cover');
  const coverAltEl = document.getElementById('article-cover-alt');
  const galleryEl = document.getElementById('article-gallery');
  const galleryAltsWrap = document.getElementById('gallery-alts-wrap');
  const chipsEl = document.getElementById('categories-chips');
  const formTitleEl = document.getElementById('article-form-title');

  let categories = [];
  let selectedCategoryIds = [];
  let articlesCache = [];

  function toast(message, type = 'success') {
    const host = document.getElementById('bo-toast');
    host.innerHTML = `<div class="toast-inline ${type === 'error' ? 'toast-error' : 'toast-success'}">${message}</div>`;
    setTimeout(() => { host.innerHTML = ''; }, 2600);
  }

  function authHeaders(extra = {}) {
    return { Authorization: `Bearer ${TOKEN}`, ...extra };
  }

  function renderCategoryChips() {
    chipsEl.innerHTML = categories.map((cat) => {
      const active = selectedCategoryIds.includes(cat.id) ? 'chip active' : 'chip';
      return `<button type="button" class="${active}" data-cat-id="${cat.id}">${cat.name}</button>`;
    }).join('');
  }

  function resetForm() {
    idEl.value = '';
    selectedCategoryIds = [];
    form.reset();
    galleryAltsWrap.innerHTML = '';
    formTitleEl.textContent = 'Nouvel article';
    renderCategoryChips();
  }

  function renderGalleryAltInputs() {
    const files = Array.from(galleryEl.files || []);
    galleryAltsWrap.innerHTML = files.map((file, i) => `
      <label>Alt galerie ${i + 1} - ${file.name}
        <input name="galleryAlt" data-index="${i}" required minlength="3" maxlength="160" />
      </label>
    `).join('');
  }

  async function loadCategories() {
    const res = await fetch(`${API_BASE}/categories`, { headers: authHeaders() });
    const data = await res.json();
    categories = Array.isArray(data) ? data : [];
    renderCategoryChips();
  }

  function mapArticleForEdit(article) {
    idEl.value = article.id;
    titleEl.value = article.title || '';
    slugEl.value = article.slug || '';
    contentEl.value = article.content || '';
    mtEl.value = article.metaTitle || '';
    mdEl.value = article.metaDescription || '';
    mkEl.value = article.metaKeywords || '';
    featuredEl.checked = !!article.featured;
    coverAltEl.value = article.coverImageAlt || '';
    selectedCategoryIds = Array.isArray(article.categories) ? article.categories.map((c) => c.id) : [];
    formTitleEl.textContent = 'Modifier article';
    renderCategoryChips();
  }

  async function loadArticles() {
    const res = await fetch(`${API_BASE}/admin/articles`, { headers: authHeaders() });
    const data = await res.json();
    articlesCache = Array.isArray(data) ? data : [];

    if (articlesCache.length === 0) {
      articlesBody.innerHTML = '<tr><td colspan="6">Aucun article.</td></tr>';
      return;
    }

    articlesBody.innerHTML = articlesCache.map((a) => `
      <tr>
        <td>${a.id}</td>
        <td>${a.title || ''}</td>
        <td>${a.slug || ''}</td>
        <td>${a.featured ? 'Oui' : 'Non'}</td>
        <td>${Array.isArray(a.galleryImages) ? a.galleryImages.length : 0}</td>
        <td class="actions-inline">
          <button class="btn btn-small" data-action="edit" data-id="${a.id}">Edit</button>
          <button class="btn btn-small btn-danger" data-action="delete" data-id="${a.id}">Delete</button>
        </td>
      </tr>
    `).join('');
  }

  chipsEl.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-cat-id]');
    if (!btn) return;
    const id = Number(btn.dataset.catId);
    selectedCategoryIds = selectedCategoryIds.includes(id)
      ? selectedCategoryIds.filter((x) => x !== id)
      : [...selectedCategoryIds, id];
    renderCategoryChips();
  });

  galleryEl.addEventListener('change', renderGalleryAltInputs);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (selectedCategoryIds.length === 0) {
      toast('Selectionne au moins une categorie', 'error');
      return;
    }

    const id = idEl.value.trim();
    const fd = new FormData();
    fd.append('title', titleEl.value.trim());
    fd.append('slug', slugEl.value.trim());
    fd.append('content', contentEl.value.trim());
    fd.append('metaTitle', mtEl.value.trim());
    fd.append('metaDescription', mdEl.value.trim());
    fd.append('metaKeywords', mkEl.value.trim());
    fd.append('featured', String(featuredEl.checked));
    fd.append('coverImageAlt', coverAltEl.value.trim());
    fd.append('categoryIds', JSON.stringify(selectedCategoryIds));

    if (coverEl.files && coverEl.files[0]) {
      fd.append('coverImage', coverEl.files[0]);
    }

    const alts = [];
    const altInputs = Array.from(galleryAltsWrap.querySelectorAll('input[name="galleryAlt"]'));
    const galleryFiles = Array.from(galleryEl.files || []);
    if (galleryFiles.length !== altInputs.length) {
      toast('Renseigne un alt pour chaque image de galerie', 'error');
      return;
    }

    galleryFiles.forEach((file, idx) => {
      const alt = (altInputs[idx].value || '').trim();
      if (!alt) {
        throw new Error('Alt galerie manquant');
      }
      alts.push(alt);
      fd.append('galleryImages', file);
    });

    fd.append('galleryAlts', JSON.stringify(alts));

    const url = id ? `${API_BASE}/articles/${id}` : `${API_BASE}/articles`;
    const method = id ? 'PATCH' : 'POST';

    const res = await fetch(url, {
      method,
      headers: authHeaders(),
      body: fd,
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      toast(err.message || 'Erreur de sauvegarde', 'error');
      return;
    }

    toast(id ? 'Article mis a jour' : 'Article cree');
    resetForm();
    await loadArticles();
  });

  document.getElementById('article-reset').addEventListener('click', resetForm);

  articlesBody.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const id = Number(btn.dataset.id);
    const action = btn.dataset.action;

    if (action === 'edit') {
      const article = articlesCache.find((a) => a.id === id);
      if (article) mapArticleForEdit(article);
      return;
    }

    if (action === 'delete') {
      if (!window.confirm('Supprimer cet article ?')) return;
      const res = await fetch(`${API_BASE}/articles/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
      });
      if (!res.ok) {
        toast('Suppression impossible', 'error');
        return;
      }
      toast('Article supprime');
      await loadArticles();
    }
  });

  Promise.all([loadCategories(), loadArticles()]).catch(() => {
    articlesBody.innerHTML = '<tr><td colspan="6">Erreur de chargement.</td></tr>';
  });
})();
</script>

<jsp:include page="/WEB-INF/backoffice/includes/footer.jsp"/>