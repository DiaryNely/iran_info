import { useEffect, useRef, useState } from 'react';
import { articlesApi, categoriesApi } from '../api/client';

const emptyForm = {
  id: null,
  title: '',
  slug: '',
  content: '',
  status: 'published',
  featured: false,
  metaTitle: '',
  metaDescription: '',
  metaKeywords: '',
  originalCoverImagePath: null,
  coverImagePath: null,
  coverImageAlt: '',
  selectedCoverImage: null,
  selectedCoverImageUrl: '',
  originalGalleryImages: [],
  existingGalleryImages: [],
  selectedGalleryImages: [],
  categoryIds: [],
};

function makeSelectedImage(file, alt = '') {
  return {
    id: `${Date.now()}-${Math.random()}`,
    file,
    url: URL.createObjectURL(file),
    alt,
  };
}

function revokeSelectedImages(images) {
  images.forEach((item) => {
    try {
      URL.revokeObjectURL(item.url);
    } catch {
      // Ignore revoke errors.
    }
  });
}

function toAbsoluteUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  return `http://localhost:3000${path}`;
}

export function AdminArticlesPage({ onToast }) {
  const [articles, setArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const selectedImagesRef = useRef([]);
  const selectedCoverImageUrlRef = useRef('');

  useEffect(() => {
    selectedImagesRef.current = form.selectedGalleryImages;
    selectedCoverImageUrlRef.current = form.selectedCoverImageUrl;
  }, [form.selectedGalleryImages, form.selectedCoverImageUrl]);

  useEffect(
    () => () => {
      revokeSelectedImages(selectedImagesRef.current);
      if (selectedCoverImageUrlRef.current) {
        URL.revokeObjectURL(selectedCoverImageUrlRef.current);
      }
    },
    [],
  );

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
    revokeSelectedImages(form.selectedGalleryImages);
    if (form.selectedCoverImageUrl) {
      URL.revokeObjectURL(form.selectedCoverImageUrl);
    }

    const galleryImages = Array.isArray(article.galleryImages) ? article.galleryImages : [];
    setForm({
      id: article.id,
      title: article.title ?? '',
      slug: article.slug ?? '',
      content: article.content ?? '',
      status: 'published',
      featured: Boolean(article.featured),
      metaTitle: article.metaTitle ?? '',
      metaDescription: article.metaDescription ?? '',
      metaKeywords: article.metaKeywords ?? '',
      originalCoverImagePath: article.coverImagePath ?? null,
      coverImagePath: article.coverImagePath ?? null,
      coverImageAlt: article.coverImageAlt ?? '',
      selectedCoverImage: null,
      selectedCoverImageUrl: '',
      originalGalleryImages: galleryImages,
      existingGalleryImages: galleryImages,
      selectedGalleryImages: [],
      categoryIds: Array.isArray(article.categories) ? article.categories.map((cat) => cat.id) : [],
    });
  }

  function resetForm() {
    revokeSelectedImages(form.selectedGalleryImages);
    if (form.selectedCoverImageUrl) {
      URL.revokeObjectURL(form.selectedCoverImageUrl);
    }
    setForm(emptyForm);
  }

  async function handleSubmit(event) {
    event.preventDefault();

    if (form.metaTitle.length < 50 || form.metaTitle.length > 60) {
      onToast({ type: 'error', message: 'Le meta title doit contenir entre 50 et 60 caracteres.' });
      return;
    }

    if (form.metaDescription.length < 150 || form.metaDescription.length > 160) {
      onToast({ type: 'error', message: 'La meta description doit contenir entre 150 et 160 caracteres.' });
      return;
    }

    if (!form.coverImageAlt.trim()) {
      onToast({ type: 'error', message: "Le texte alternatif de l'image principale est obligatoire." });
      return;
    }

    if (!form.id && !form.selectedCoverImage) {
      onToast({ type: 'error', message: "L'image principale est obligatoire pour creer un article." });
      return;
    }

    if (form.id && !form.coverImagePath && !form.selectedCoverImage) {
      onToast({ type: 'error', message: "Un article doit toujours avoir une image principale." });
      return;
    }

    if (form.selectedGalleryImages.some((item) => !item.alt.trim())) {
      onToast({ type: 'error', message: 'Chaque image de la galerie doit avoir un texte alternatif.' });
      return;
    }

    setSaving(true);

    const payload = new FormData();
    payload.append('title', form.title);
    payload.append('slug', form.slug);
    payload.append('content', form.content);

    payload.append('featured', String(form.featured));
    payload.append('metaTitle', form.metaTitle);
    payload.append('metaDescription', form.metaDescription);
    payload.append('metaKeywords', form.metaKeywords);
    payload.append('coverImageAlt', form.coverImageAlt.trim());
    payload.append('categoryIds', JSON.stringify(form.categoryIds.map(Number)));
    const removedGalleryPaths = form.originalGalleryImages
      .filter((item) => !form.existingGalleryImages.some((current) => current.path === item.path))
      .map((item) => item.path);
    payload.append('removedGalleryPaths', JSON.stringify(removedGalleryPaths));

    if (form.id && form.originalCoverImagePath && !form.coverImagePath && !form.selectedCoverImage) {
      payload.append('removeCoverImage', 'true');
    }

    if (form.selectedCoverImage) {
      payload.append('coverImage', form.selectedCoverImage);
    }

    payload.append(
      'galleryAlts',
      JSON.stringify(form.selectedGalleryImages.map((item) => item.alt.trim())),
    );

    form.selectedGalleryImages.forEach((item) => {
      payload.append('galleryImages', item.file);
    });

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

  function removeExistingImage(pathToRemove) {
    setForm((prev) => ({
      ...prev,
      existingGalleryImages: prev.existingGalleryImages.filter((item) => item.path !== pathToRemove),
    }));
  }

  function handleSelectGalleryImages(event) {
    const files = Array.from(event.target.files ?? []);
    if (files.length === 0) {
      return;
    }

    const newItems = files.map((file) => makeSelectedImage(file));
    setForm((prev) => ({
      ...prev,
      selectedGalleryImages: [...prev.selectedGalleryImages, ...newItems],
    }));

    event.target.value = '';
  }

  function handleSelectCoverImage(event) {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    setForm((prev) => {
      if (prev.selectedCoverImageUrl) {
        URL.revokeObjectURL(prev.selectedCoverImageUrl);
      }

      const nextUrl = URL.createObjectURL(file);
      return {
        ...prev,
        selectedCoverImage: file,
        selectedCoverImageUrl: nextUrl,
        coverImagePath: null,
      };
    });

    event.target.value = '';
  }

  function clearSelectedCoverImage() {
    setForm((prev) => {
      if (prev.selectedCoverImageUrl) {
        URL.revokeObjectURL(prev.selectedCoverImageUrl);
      }

      return {
        ...prev,
        selectedCoverImage: null,
        selectedCoverImageUrl: '',
        coverImagePath: prev.originalCoverImagePath,
      };
    });
  }

  function removeExistingCoverImage() {
    setForm((prev) => ({
      ...prev,
      coverImagePath: null,
    }));
  }

  function removeSelectedImage(id) {
    setForm((prev) => {
      const toRemove = prev.selectedGalleryImages.find((item) => item.id === id);
      if (toRemove) {
        revokeSelectedImages([toRemove]);
      }

      return {
        ...prev,
        selectedGalleryImages: prev.selectedGalleryImages.filter((item) => item.id !== id),
      };
    });
  }

  function updateSelectedImageAlt(id, alt) {
    setForm((prev) => ({
      ...prev,
      selectedGalleryImages: prev.selectedGalleryImages.map((item) =>
        item.id === id ? { ...item, alt } : item,
      ),
    }));
  }

  function clearSelectedImages() {
    revokeSelectedImages(form.selectedGalleryImages);
    setForm((prev) => ({
      ...prev,
      selectedGalleryImages: [],
    }));
  }

  return (
    <section className="page-grid crud-layout">
      <div className="card crud-form-card">
        <h2 className="crud-title">{form.id ? 'Modifier article' : 'Nouvel article'}</h2>
        <form className="form-grid" onSubmit={handleSubmit}>
          <label>
            Titre
            <input value={form.title} onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))} required />
          </label>

          <label>
            Slug
            <input value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} required />
          </label>



          <label className="checkbox-row">
            <input
              type="checkbox"
              checked={form.featured}
              onChange={(event) => setForm((prev) => ({ ...prev, featured: event.target.checked }))}
            />
            Mettre cet article a la une
          </label>

          <label>
            Image principale
            <input
              type="file"
              accept="image/*"
              onChange={handleSelectCoverImage}
              required={!form.id}
            />
          </label>

          {form.coverImagePath ? (
            <div>
              <p>Image principale actuelle</p>
              <div className="thumb-wrap">
                <img src={toAbsoluteUrl(form.coverImagePath)} alt={form.coverImageAlt || 'Cover'} className="thumb cover-thumb" />
                <button type="button" className="thumb-remove" onClick={removeExistingCoverImage}>
                  Supprimer
                </button>
              </div>
            </div>
          ) : null}

          {form.selectedCoverImageUrl ? (
            <div>
              <p>Nouvelle image principale</p>
              <div className="thumb-wrap">
                <img src={form.selectedCoverImageUrl} alt={form.selectedCoverImage?.name || 'New cover'} className="thumb cover-thumb" />
                <button type="button" className="thumb-remove" onClick={clearSelectedCoverImage}>
                  Annuler selection
                </button>
              </div>
            </div>
          ) : null}

          <label>
            Alt image principale (obligatoire)
            <input
              value={form.coverImageAlt}
              onChange={(event) => setForm((prev) => ({ ...prev, coverImageAlt: event.target.value }))}
              required
              minLength={3}
              maxLength={160}
            />
          </label>

          <label>
            Galerie d'images
            <input
              type="file"
              accept="image/*"
              multiple
              onChange={handleSelectGalleryImages}
            />
          </label>

          {form.existingGalleryImages.length > 0 ? (
            <div>
              <p>Galerie actuelle</p>
              <div className="image-grid">
                {form.existingGalleryImages.map((image) => (
                  <div key={image.path} className="thumb-wrap thumb-extended">
                    <a href={toAbsoluteUrl(image.path)} target="_blank" rel="noreferrer">
                      <img src={toAbsoluteUrl(image.path)} alt={image.alt || 'Article'} className="thumb" />
                    </a>
                    <small className="thumb-label">Alt: {image.alt || 'non renseigne'}</small>
                    <button
                      type="button"
                      className="thumb-remove"
                      onClick={() => removeExistingImage(image.path)}
                    >
                      Supprimer
                    </button>
                  </div>
                ))}
              </div>
            </div>
          ) : null}

          {form.selectedGalleryImages.length > 0 ? (
            <div>
              <div className="selected-header">
                <p>Nouvelles images selectionnees: {form.selectedGalleryImages.length}</p>
                <button type="button" className="thumb-remove" onClick={clearSelectedImages}>
                  Annuler selection
                </button>
              </div>
              <div className="image-grid">
                {form.selectedGalleryImages.map((item) => (
                  <div key={item.id} className="thumb-wrap thumb-extended">
                    <img src={item.url} alt={item.file.name} className="thumb" />
                    <input
                      value={item.alt}
                      onChange={(event) => updateSelectedImageAlt(item.id, event.target.value)}
                      placeholder="Alt SEO (obligatoire)"
                      minLength={3}
                      maxLength={160}
                      required
                    />
                    <small className="thumb-label">{item.file.name}</small>
                    <button type="button" className="thumb-remove" onClick={() => removeSelectedImage(item.id)}>
                      Retirer
                    </button>
                  </div>
                ))}
              </div>
            </div>
          ) : null}

          <label>
            Meta title
            <input
              value={form.metaTitle}
              onChange={(event) => setForm((prev) => ({ ...prev, metaTitle: event.target.value }))}
              minLength={50}
              maxLength={60}
              required
            />
            <small className="hint">{form.metaTitle.length}/60 (objectif: 50-60)</small>
          </label>

          <label>
            Meta description
            <textarea
              value={form.metaDescription}
              onChange={(event) => setForm((prev) => ({ ...prev, metaDescription: event.target.value }))}
              rows={3}
              minLength={150}
              maxLength={160}
              required
            />
            <small className="hint">{form.metaDescription.length}/160 (objectif: 150-160)</small>
          </label>

          <label>
            Meta keywords
            <input
              value={form.metaKeywords}
              onChange={(event) => setForm((prev) => ({ ...prev, metaKeywords: event.target.value }))}
              placeholder="iran, geopolitique, analyse"
              maxLength={255}
              required
            />
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

      <div className="card crud-list-card">
        <h2 className="crud-title">Articles</h2>
        {loading ? <p>Chargement...</p> : null}
        <div className="table-wrap">
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
            <tbody>
              {articles.map((article) => (
                <tr key={article.id}>
                  <td>{article.id}</td>
                  <td>{article.title}</td>
                  <td>{article.slug}</td>
                  <td>{article.featured ? 'Oui' : 'Non'}</td>
                  <td>{Array.isArray(article.galleryImages) ? article.galleryImages.length : 0}</td>
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
