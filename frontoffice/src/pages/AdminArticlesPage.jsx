import { useEffect, useState } from 'react';
import { articlesApi, categoriesApi } from '../api/client';

const emptyForm = {
  id: null,
  title: '',
  slug: '',
  content: '',
  image: '',
  metaTitle: '',
  metaDescription: '',
  categoryIds: [],
};

export function AdminArticlesPage({ onToast }) {
  const [articles, setArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  async function loadData() {
    setLoading(true);
    try {
      const [articlesData, categoriesData] = await Promise.all([articlesApi.list(), categoriesApi.list()]);
      setArticles(Array.isArray(articlesData) ? articlesData : []);
      setCategories(Array.isArray(categoriesData) ? categoriesData : []);
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  function handleEdit(article) {
    setForm({
      id: article.id,
      title: article.title ?? '',
      slug: article.slug ?? '',
      content: article.content ?? '',
      image: article.image ?? '',
      metaTitle: article.metaTitle ?? '',
      metaDescription: article.metaDescription ?? '',
      categoryIds: Array.isArray(article.categories) ? article.categories.map((cat) => cat.id) : [],
    });
  }

  function resetForm() {
    setForm(emptyForm);
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setSaving(true);

    const payload = {
      title: form.title,
      slug: form.slug,
      content: form.content,
      image: form.image || undefined,
      metaTitle: form.metaTitle || undefined,
      metaDescription: form.metaDescription || undefined,
      categoryIds: form.categoryIds.map(Number),
    };

    try {
      if (form.id) {
        await articlesApi.update(form.id, payload);
        onToast({ type: 'success', message: 'Article mis a jour.' });
      } else {
        await articlesApi.create(payload);
        onToast({ type: 'success', message: 'Article cree.' });
      }

      resetForm();
      await loadData();
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Supprimer cet article ?')) return;

    try {
      await articlesApi.remove(id);
      onToast({ type: 'success', message: 'Article supprime.' });
      await loadData();
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    }
  }

  function toggleCategory(id) {
    setForm((prev) => {
      const exists = prev.categoryIds.includes(id);
      return {
        ...prev,
        categoryIds: exists ? prev.categoryIds.filter((item) => item !== id) : [...prev.categoryIds, id],
      };
    });
  }

  return (
    <section className="page-grid">
      <div className="card">
        <h2>{form.id ? 'Modifier article' : 'Nouvel article'}</h2>
        <form className="form-grid" onSubmit={handleSubmit}>
          <label>
            Titre
            <input value={form.title} onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))} required />
          </label>

          <label>
            Slug
            <input value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} required />
          </label>

          <label>
            Image (URL)
            <input value={form.image} onChange={(event) => setForm((prev) => ({ ...prev, image: event.target.value }))} />
          </label>

          <label>
            Meta title
            <input value={form.metaTitle} onChange={(event) => setForm((prev) => ({ ...prev, metaTitle: event.target.value }))} />
          </label>

          <label>
            Meta description
            <textarea value={form.metaDescription} onChange={(event) => setForm((prev) => ({ ...prev, metaDescription: event.target.value }))} rows={2} />
          </label>

          <label>
            Contenu
            <textarea value={form.content} onChange={(event) => setForm((prev) => ({ ...prev, content: event.target.value }))} rows={7} required />
          </label>

          <div>
            <p>Categories</p>
            <div className="chips">
              {categories.map((category) => (
                <button
                  key={category.id}
                  type="button"
                  className={form.categoryIds.includes(category.id) ? 'chip active' : 'chip'}
                  onClick={() => toggleCategory(category.id)}
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>

          <div className="actions">
            <button className="btn btn-primary" disabled={saving} type="submit">
              {saving ? 'Sauvegarde...' : form.id ? 'Mettre a jour' : 'Creer'}
            </button>
            {form.id ? (
              <button type="button" className="btn btn-secondary" onClick={resetForm}>
                Annuler
              </button>
            ) : null}
          </div>
        </form>
      </div>

      <div className="card">
        <h2>Articles</h2>
        {loading ? <p>Chargement...</p> : null}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Slug</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {articles.map((article) => (
                <tr key={article.id}>
                  <td>{article.id}</td>
                  <td>{article.title}</td>
                  <td>{article.slug}</td>
                  <td className="actions-inline">
                    <button type="button" className="btn btn-small" onClick={() => handleEdit(article)}>
                      Edit
                    </button>
                    <button type="button" className="btn btn-small btn-danger" onClick={() => handleDelete(article.id)}>
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </section>
  );
}
