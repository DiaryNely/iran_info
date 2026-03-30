<?php
  $items = is_array($categories ?? null) ? $categories : [];
  $msg = is_array($flash ?? null) ? $flash : null;
  $pageTitle = 'Categories';
  $activePage = 'categories';
  require __DIR__ . '/includes/header.php';
?>
  <section class="bo-section">
    <div class="bo-title-row">
      <h2>Gestion des categories</h2>
      <p>Connecte: <?= htmlspecialchars((string) ($adminUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php if ($msg): ?>
      <div class="toast-inline <?= htmlspecialchars((string) ($msg['type'] ?? 'success'), ENT_QUOTES, 'UTF-8') === 'error' ? 'toast-error' : 'toast-success' ?>">
        <?= htmlspecialchars((string) ($msg['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div class="page-grid crud-layout">
      <article class="card crud-form-card">
        <h3 class="crud-title">Nouvelle categorie</h3>
        <form method="post" action="/backoffice/categories/save" class="form-grid">
          <label>
            Nom
            <input name="name" required minlength="2">
          </label>
          <label>
            Slug (optionnel)
            <input name="slug" placeholder="auto-genere si vide">
          </label>
          <label>
            Description
            <textarea name="description"></textarea>
          </label>
          <label>
            Meta title
            <input name="meta_title">
          </label>
          <label>
            Meta description
            <textarea name="meta_description"></textarea>
          </label>
          <div class="actions">
            <button class="btn btn-primary" type="submit">Creer</button>
          </div>
        </form>
      </article>

      <article class="card crud-list-card">
        <h3 class="crud-title">Liste des categories</h3>
        <?php if (count($items) === 0): ?>
          <p>Aucune categorie.</p>
        <?php else: ?>
          <div class="table-wrap"><table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom / Slug</th>
                <th>Articles</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $cat): ?>
                <tr>
                  <td><?= (int) ($cat['id'] ?? 0) ?></td>
                  <td>
                    <strong><?= htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <span class="small"><?= htmlspecialchars((string) ($cat['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td><?= (int) ($cat['articleCount'] ?? 0) ?></td>
                  <td>
                    <details>
                      <summary class="small" style="cursor:pointer;">Editer</summary>
                      <form method="post" action="/backoffice/categories/save" style="margin-top:0.4rem; display:grid; gap:0.4rem;">
                        <input type="hidden" name="id" value="<?= (int) ($cat['id'] ?? 0) ?>">
                        <input name="name" value="<?= htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required minlength="2">
                        <input name="slug" value="<?= htmlspecialchars((string) ($cat['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <textarea name="description"><?= htmlspecialchars((string) ($cat['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <input name="meta_title" value="<?= htmlspecialchars((string) ($cat['metaTitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <textarea name="meta_description"><?= htmlspecialchars((string) ($cat['metaDescription'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button class="btn" type="submit">Mettre a jour</button>
                      </form>
                    </details>

                    <form class="inline" method="post" action="/backoffice/categories/delete" onsubmit="return confirm('Supprimer cette categorie ?');">
                      <input type="hidden" name="id" value="<?= (int) ($cat['id'] ?? 0) ?>">
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
<?php require __DIR__ . '/includes/footer.php'; ?>
