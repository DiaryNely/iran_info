<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" isELIgnored="true" %>
<%@ taglib prefix="c" uri="jakarta.tags.core" %>
<jsp:include page="/WEB-INF/backoffice/includes/header.jsp">
    <jsp:param name="pageTitle" value="Categories"/>
    <jsp:param name="activePage" value="categories"/>
</jsp:include>

<section class="bo-section" data-page="categories">
    <div class="bo-title-row">
        <h2>Gestion des categories</h2>
        <p>Creation, edition et suppression.</p>
    </div>

    <div id="bo-toast"></div>

    <div class="page-grid crud-layout">
        <div class="card crud-form-card">
            <h3 class="crud-title" id="cat-form-title">Nouvelle categorie</h3>
            <form id="category-form" class="form-grid">
                <input type="hidden" id="cat-id" />
                <label>Nom
                    <input id="cat-name" required />
                </label>
                <label>Slug
                    <input id="cat-slug" required />
                </label>
                <label>Description
                    <textarea id="cat-description" rows="3"></textarea>
                </label>
                <label>Meta title
                    <input id="cat-meta-title" />
                </label>
                <label>Meta description
                    <textarea id="cat-meta-description" rows="2"></textarea>
                </label>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Sauvegarder</button>
                    <button class="btn btn-secondary" type="button" id="cat-reset">Annuler</button>
                </div>
            </form>
        </div>

        <div class="card crud-list-card">
            <h3 class="crud-title">Liste des categories</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="categories-body">
                    <tr><td colspan="4">Chargement...</td></tr>
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
  const bodyEl = document.getElementById('categories-body');
  const form = document.getElementById('category-form');

  const idEl = document.getElementById('cat-id');
  const nameEl = document.getElementById('cat-name');
  const slugEl = document.getElementById('cat-slug');
  const descEl = document.getElementById('cat-description');
  const mtEl = document.getElementById('cat-meta-title');
  const mdEl = document.getElementById('cat-meta-description');
  const titleEl = document.getElementById('cat-form-title');

  function toast(message, type = 'success') {
    const host = document.getElementById('bo-toast');
    host.innerHTML = `<div class="toast-inline ${type === 'error' ? 'toast-error' : 'toast-success'}">${message}</div>`;
    setTimeout(() => { host.innerHTML = ''; }, 2600);
  }

  function authHeaders(extra = {}) {
    return { Authorization: `Bearer ${TOKEN}`, ...extra };
  }

  function resetForm() {
    idEl.value = '';
    form.reset();
    titleEl.textContent = 'Nouvelle categorie';
  }

  async function loadCategories() {
    const res = await fetch(`${API_BASE}/categories`, { headers: authHeaders() });
    const data = await res.json();
    if (!Array.isArray(data)) {
      bodyEl.innerHTML = '<tr><td colspan="4">Aucune categorie.</td></tr>';
      return;
    }
    bodyEl.innerHTML = data.map((cat) => `
      <tr>
        <td>${cat.id}</td>
        <td>${cat.name || ''}</td>
        <td>${cat.slug || ''}</td>
        <td class="actions-inline">
          <button class="btn btn-small" data-action="edit" data-id="${cat.id}" data-name="${cat.name || ''}" data-slug="${cat.slug || ''}" data-desc="${(cat.description || '').replace(/"/g, '&quot;')}" data-mt="${(cat.metaTitle || '').replace(/"/g, '&quot;')}" data-md="${(cat.metaDescription || '').replace(/"/g, '&quot;')}">Edit</button>
          <button class="btn btn-small btn-danger" data-action="delete" data-id="${cat.id}">Delete</button>
        </td>
      </tr>`).join('');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = idEl.value.trim();
    const payload = {
      name: nameEl.value.trim(),
      slug: slugEl.value.trim(),
      description: descEl.value.trim() || undefined,
      metaTitle: mtEl.value.trim() || undefined,
      metaDescription: mdEl.value.trim() || undefined,
    };

    const url = id ? `${API_BASE}/categories/${id}` : `${API_BASE}/categories`;
    const method = id ? 'PATCH' : 'POST';

    const res = await fetch(url, {
      method,
      headers: authHeaders({ 'Content-Type': 'application/json' }),
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      toast(err.message || 'Erreur de sauvegarde', 'error');
      return;
    }

    toast(id ? 'Categorie mise a jour' : 'Categorie creee');
    resetForm();
    await loadCategories();
  });

  document.getElementById('cat-reset').addEventListener('click', resetForm);

  bodyEl.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const id = btn.dataset.id;

    if (action === 'edit') {
      idEl.value = id;
      nameEl.value = btn.dataset.name || '';
      slugEl.value = btn.dataset.slug || '';
      descEl.value = btn.dataset.desc || '';
      mtEl.value = btn.dataset.mt || '';
      mdEl.value = btn.dataset.md || '';
      titleEl.textContent = 'Modifier categorie';
      return;
    }

    if (action === 'delete') {
      if (!window.confirm('Supprimer cette categorie ?')) return;
      const res = await fetch(`${API_BASE}/categories/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
      });
      if (!res.ok) {
        toast('Suppression impossible', 'error');
        return;
      }
      toast('Categorie supprimee');
      await loadCategories();
    }
  });

  loadCategories().catch(() => {
    bodyEl.innerHTML = '<tr><td colspan="4">Erreur de chargement.</td></tr>';
  });
})();
</script>

<jsp:include page="/WEB-INF/backoffice/includes/footer.jsp"/>