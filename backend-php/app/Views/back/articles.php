<?php
  $items = is_array($articles ?? null) ? $articles : [];
  $cats = is_array($categories ?? null) ? $categories : [];
  $edit = is_array($editArticle ?? null) ? $editArticle : null;
  $msg = is_array($flash ?? null) ? $flash : null;
  $selectedCatIds = [];
  if ($edit && !empty($edit['categories']) && is_array($edit['categories'])) {
      foreach ($edit['categories'] as $c) {
          $selectedCatIds[] = (int) ($c['id'] ?? 0);
      }
  }
  $editGallery = is_array($edit['galleryImages'] ?? null) ? $edit['galleryImages'] : [];
  $pageTitle = 'Articles';
  $activePage = 'articles';
  require __DIR__ . '/includes/header.php';
?>
<section class="bo-section">
  <div class="bo-title-row">
    <h2>Gestion des articles</h2>
    <p>Connecte: <?= htmlspecialchars((string) ($adminUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <?php if ($msg): ?>
    <div class="toast-inline <?= htmlspecialchars((string) ($msg['type'] ?? 'success'), ENT_QUOTES, 'UTF-8') === 'error' ? 'toast-error' : 'toast-success' ?>">
      <?= htmlspecialchars((string) ($msg['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="page-grid crud-layout">
    <article class="card crud-form-card">
      <h3 class="crud-title"><?= $edit ? 'Modifier article #' . (int) ($edit['id'] ?? 0) : 'Nouvel article' ?></h3>
      <form id="article-form" method="post" action="/backoffice/articles/save" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

        <label>
          Titre
          <input type="text" name="title" required minlength="5" value="<?= htmlspecialchars((string) ($edit['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Slug (optionnel)
          <input type="text" name="slug" value="<?= htmlspecialchars((string) ($edit['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Contenu
          <textarea name="content" required><?= htmlspecialchars((string) ($edit['content'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>

        <label>
          Image de couverture <?= $edit ? '(laisser vide pour conserver)' : '' ?>
          <input id="article-cover" type="file" name="cover_image" accept="image/png,image/jpeg,image/webp">
        </label>

        <div id="cover-preview-wrap" class="upload-preview-grid"></div>

        <label>
          Alt image couverture
          <input type="text" name="cover_image_alt" value="<?= htmlspecialchars((string) ($edit['coverImageAlt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Images galerie (optionnel, multiple)
          <input id="article-gallery" type="file" name="gallery_images[]" multiple accept="image/png,image/jpeg,image/webp">
        </label>

        <div id="gallery-preview-wrap" class="upload-preview-grid"></div>

        <label>
          Meta title
          <input type="text" name="meta_title" value="<?= htmlspecialchars((string) ($edit['metaTitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Meta description
          <textarea name="meta_description"><?= htmlspecialchars((string) ($edit['metaDescription'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>

        <label>
          Meta keywords
          <input type="text" name="meta_keywords" value="<?= htmlspecialchars((string) ($edit['metaKeywords'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <div class="check-row">
          <input type="checkbox" id="featured" name="featured" value="1" <?= !empty($edit['featured']) ? 'checked' : '' ?>>
          <label for="featured" style="display:inline;">Mettre en avant</label>
        </div>

        <div class="category-picker">
          <div class="category-picker-head">
            <p>Categories <span id="categories-selected-count" class="category-count">(0 selectionnee)</span></p>
            <div class="category-picker-actions">
              <button id="cat-select-all" class="btn btn-small" type="button">Tout selectionner</button>
              <button id="cat-clear-all" class="btn btn-small" type="button">Vider</button>
            </div>
          </div>
          <label class="category-search-label">Rechercher une categorie
            <input id="categories-search" type="search" placeholder="Ex: Politique, Economie...">
          </label>
          <div id="categories-chips" class="chips category-chips"></div>
          <div id="category-hidden-inputs"></div>
        </div>

        <div class="actions">
          <button class="btn btn-primary" type="submit"><?= $edit ? 'Mettre a jour' : 'Creer' ?></button>
          <?php if ($edit): ?>
            <a class="btn" href="/backoffice/articles" style="text-decoration:none;display:inline-flex;align-items:center;">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </article>

      <article class="card crud-list-card">
      <h3 class="crud-title">Liste des articles</h3>
      <?php if (count($items) === 0): ?>
        <p>Aucun article.</p>
      <?php else: ?>
        <div class="table-wrap"><table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Slug</th>
              <th>Cover</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $article): ?>
            <tr>
              <td><?= (int) ($article['id'] ?? 0) ?></td>
              <td><?= htmlspecialchars((string) ($article['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($article['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if (!empty($article['coverImagePath'])): ?>
                  <img class="thumb" src="<?= htmlspecialchars((string) $article['coverImagePath'], ENT_QUOTES, 'UTF-8') ?>" alt="cover">
                <?php endif; ?>
              </td>
              <td>
                <a class="btn" style="text-decoration:none;display:inline-flex;align-items:center;" href="/backoffice/articles?edit=<?= (int) ($article['id'] ?? 0) ?>">Editer</a>
                <form class="inline" method="post" action="/backoffice/articles/delete" onsubmit="return confirm('Supprimer cet article ?');">
                  <input type="hidden" name="id" value="<?= (int) ($article['id'] ?? 0) ?>">
                  <button class="btn btn-danger" type="submit">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </article>
  </div>
</section>

<script id="php-categories-json" type="application/json"><?= json_encode($cats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script id="php-selected-category-ids" type="application/json"><?= json_encode($selectedCatIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script id="php-edit-cover" type="application/json"><?= json_encode((string) ($edit['coverImagePath'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script id="php-edit-gallery" type="application/json"><?= json_encode($editGallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script>
(() => {
  const form = document.getElementById('article-form');
  if (!form) return;

  const categories = JSON.parse((document.getElementById('php-categories-json') || {}).textContent || '[]');
  let selectedCategoryIds = JSON.parse((document.getElementById('php-selected-category-ids') || {}).textContent || '[]');
  const existingCover = JSON.parse((document.getElementById('php-edit-cover') || {}).textContent || '""');
  const existingGallery = JSON.parse((document.getElementById('php-edit-gallery') || {}).textContent || '[]');

  const coverEl = document.getElementById('article-cover');
  const galleryEl = document.getElementById('article-gallery');
  const coverPreviewWrap = document.getElementById('cover-preview-wrap');
  const galleryPreviewWrap = document.getElementById('gallery-preview-wrap');
  const chipsEl = document.getElementById('categories-chips');
  const categoriesSearchEl = document.getElementById('categories-search');
  const categoriesSelectedCountEl = document.getElementById('categories-selected-count');
  const selectAllCategoriesBtn = document.getElementById('cat-select-all');
  const clearAllCategoriesBtn = document.getElementById('cat-clear-all');
  const hiddenInputsHost = document.getElementById('category-hidden-inputs');

  function escapeHtml(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function resolveMediaUrl(path) {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('/')) return path;
    return '/' + path;
  }

  function getFilteredCategories() {
    const query = (categoriesSearchEl.value || '').trim().toLowerCase();
    if (!query) return categories;
    return categories.filter((cat) => {
      const name = (cat.name || '').toLowerCase();
      const slug = (cat.slug || '').toLowerCase();
      return name.includes(query) || slug.includes(query);
    });
  }

  function syncHiddenCategoryInputs() {
    hiddenInputsHost.innerHTML = selectedCategoryIds
      .map((id) => '<input type="hidden" name="category_ids[]" value="' + Number(id) + '">')
      .join('');
  }

  function updateCategoryCounter() {
    const selected = selectedCategoryIds.length;
    categoriesSelectedCountEl.textContent = '(' + selected + ' selectionnee' + (selected > 1 ? 's' : '') + ')';
  }

  function renderCategoryChips() {
    const visibleCategories = getFilteredCategories();
    if (visibleCategories.length === 0) {
      chipsEl.innerHTML = '<p class="category-empty">Aucune categorie correspondante.</p>';
      updateCategoryCounter();
      syncHiddenCategoryInputs();
      return;
    }

    chipsEl.innerHTML = visibleCategories.map((cat) => {
      const active = selectedCategoryIds.includes(cat.id) ? 'chip active' : 'chip';
      return '<button type="button" class="' + active + '" data-cat-id="' + cat.id + '">' + escapeHtml(cat.name) + '</button>';
    }).join('');

    updateCategoryCounter();
    syncHiddenCategoryInputs();
  }

  function renderCoverPreview() {
    const selectedCover = coverEl && coverEl.files && coverEl.files[0];
    if (selectedCover) {
      const url = URL.createObjectURL(selectedCover);
      coverPreviewWrap.innerHTML = ''
        + '<article class="upload-preview-card">'
        + '<img src="' + url + '" alt="Preview cover" class="upload-preview-image">'
        + '<div class="upload-preview-meta">'
        + '<strong>' + escapeHtml(selectedCover.name) + '</strong>'
        + '<small>' + Math.round(selectedCover.size / 1024) + ' Ko</small>'
        + '</div>'
        + '<button type="button" class="btn btn-small btn-danger" data-action="clear-cover">Annuler selection</button>'
        + '</article>';
      return;
    }

    if (existingCover) {
      coverPreviewWrap.innerHTML = ''
        + '<article class="upload-preview-card">'
        + '<img src="' + escapeHtml(resolveMediaUrl(existingCover)) + '" alt="Cover actuelle" class="upload-preview-image">'
        + '<div class="upload-preview-meta">'
        + '<strong>Image actuelle</strong>'
        + '<small>Conservee si aucune nouvelle image n\'est choisie</small>'
        + '</div>'
        + '<span class="upload-badge">Actuelle</span>'
        + '</article>';
      return;
    }

    coverPreviewWrap.innerHTML = '';
  }

  function renderGalleryPreview() {
    const files = Array.from((galleryEl && galleryEl.files) || []);
    if (files.length > 0) {
      galleryPreviewWrap.innerHTML = files.map((file, idx) => {
        const url = URL.createObjectURL(file);
        return ''
          + '<article class="upload-preview-card">'
          + '<img src="' + url + '" alt="Preview galerie ' + (idx + 1) + '" class="upload-preview-image">'
          + '<div class="upload-preview-meta">'
          + '<strong>' + escapeHtml(file.name) + '</strong>'
          + '<small>' + Math.round(file.size / 1024) + ' Ko</small>'
          + '</div>'
          + '<button type="button" class="btn btn-small btn-danger" data-action="remove-gallery-file" data-index="' + idx + '">Retirer</button>'
          + '</article>';
      }).join('');
      return;
    }

    if (Array.isArray(existingGallery) && existingGallery.length > 0) {
      galleryPreviewWrap.innerHTML = existingGallery.slice(0, 8).map((img, idx) => ''
        + '<article class="upload-preview-card">'
        + '<img src="' + escapeHtml(resolveMediaUrl(img.path || '')) + '" alt="' + escapeHtml(img.alt || ('Image galerie ' + (idx + 1))) + '" class="upload-preview-image">'
        + '<div class="upload-preview-meta">'
        + '<strong>Image galerie ' + (idx + 1) + '</strong>'
        + '<small>' + escapeHtml(img.alt || 'Sans alt') + '</small>'
        + '</div>'
        + '<span class="upload-badge">Actuelle</span>'
        + '</article>'
      ).join('');
      return;
    }

    galleryPreviewWrap.innerHTML = '';
  }

  function removeGalleryFile(index) {
    const current = Array.from((galleryEl && galleryEl.files) || []);
    if (index < 0 || index >= current.length) return;
    const dt = new DataTransfer();
    current.forEach((file, idx) => {
      if (idx !== index) dt.items.add(file);
    });
    galleryEl.files = dt.files;
    renderGalleryPreview();
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

  categoriesSearchEl.addEventListener('input', renderCategoryChips);

  selectAllCategoriesBtn.addEventListener('click', () => {
    selectedCategoryIds = categories.map((cat) => Number(cat.id));
    renderCategoryChips();
  });

  clearAllCategoriesBtn.addEventListener('click', () => {
    selectedCategoryIds = [];
    renderCategoryChips();
  });

  if (coverEl) {
    coverEl.addEventListener('change', renderCoverPreview);
  }
  if (galleryEl) {
    galleryEl.addEventListener('change', renderGalleryPreview);
  }

  coverPreviewWrap.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action="clear-cover"]');
    if (!btn || !coverEl) return;
    coverEl.value = '';
    renderCoverPreview();
  });

  galleryPreviewWrap.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action="remove-gallery-file"]');
    if (!btn) return;
    removeGalleryFile(Number(btn.dataset.index));
  });

  form.addEventListener('submit', () => {
    syncHiddenCategoryInputs();
  });

  renderCategoryChips();
  renderCoverPreview();
  renderGalleryPreview();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
