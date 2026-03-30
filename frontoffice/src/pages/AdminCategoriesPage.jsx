import { useEffect, useState } from 'react';
import { categoriesApi } from '../api/client';

const emptyForm = {
  id: null,
  name: '',
  slug: '',
  description: '',
  metaTitle: '',
  metaDescription: '',
};

export function AdminCategoriesPage({ onToast }) {
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);

  async function loadData() {
    setLoading(true);
    try {
      const data = await categoriesApi.list();
      setCategories(Array.isArray(data) ? data : []);
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  function handleEdit(category) {
    setForm({
      id: category.id,
      name: category.name ?? '',
      slug: category.slug ?? '',
      description: category.description ?? '',
      metaTitle: category.metaTitle ?? '',
      metaDescription: category.metaDescription ?? '',
    });
  }

  function resetForm() {
    setForm(emptyForm);
  }

  async function handleSubmit(event) {
    event.preventDefault();
    const payload = {
      name: form.name,
      slug: form.slug,
      description: form.description || undefined,
      metaTitle: form.metaTitle || undefined,
      metaDescription: form.metaDescription || undefined,
    };

    try {
      if (form.id) {
        await categoriesApi.update(form.id, payload);
        onToast({ type: 'success', message: 'Categorie mise a jour.' });
      } else {
        await categoriesApi.create(payload);
        onToast({ type: 'success', message: 'Categorie creee.' });
      }

      resetForm();
      await loadData();
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Supprimer cette categorie ?')) return;

    try {
      await categoriesApi.remove(id);
      onToast({ type: 'success', message: 'Categorie supprimee.' });
      await loadData();
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    }
  }

  return (
    <section className="page-grid crud-layout">
      <div className="card crud-form-card">
        <h2 className="crud-title">{form.id ? 'Modifier categorie' : 'Nouvelle categorie'}</h2>
        <form className="form-grid" onSubmit={handleSubmit}>
          <label>
            Nom
            <input value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} required />
          </label>

          <label>
            Slug
            <input value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} required />
          </label>

          <label>
            Description
            <textarea value={form.description} onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))} rows={3} />
          </label>

          <label>
            Meta title
            <input value={form.metaTitle} onChange={(event) => setForm((prev) => ({ ...prev, metaTitle: event.target.value }))} />
          </label>

          <label>
            Meta description
            <textarea value={form.metaDescription} onChange={(event) => setForm((prev) => ({ ...prev, metaDescription: event.target.value }))} rows={2} />
          </label>

          <div className="actions">
            <button className="btn btn-primary" type="submit">
              {form.id ? 'Mettre a jour' : 'Creer'}
            </button>
            {form.id ? (
              <button type="button" className="btn btn-secondary" onClick={resetForm}>
                Annuler
              </button>
            ) : null}
          </div>
        </form>
      </div>

      <div className="card crud-list-card">
        <h2 className="crud-title">Categories</h2>
        {loading ? <p>Chargement...</p> : null}
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((category) => (
                <tr key={category.id}>
                  <td>{category.id}</td>
                  <td>{category.name}</td>
                  <td>{category.slug}</td>
                  <td className="actions-inline">
                    <button type="button" className="btn btn-small" onClick={() => handleEdit(category)}>
                      Edit
                    </button>
                    <button type="button" className="btn btn-small btn-danger" onClick={() => handleDelete(category.id)}>
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
