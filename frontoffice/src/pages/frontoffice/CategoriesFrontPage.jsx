import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  buildSeoDescription,
  buildSeoTitle,
  optimizeImageUrl,
  setDocumentSeo,
} from './seo';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

export function CategoriesFrontPage() {
  const { slug } = useParams();
  const [categories, setCategories] = useState([]);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const title = buildSeoTitle(
      slug ? `Categorie ${slug} - actualites et analyses` : 'Categorie actualites Iran Info',
    );
    const description = buildSeoDescription(
      slug
        ? `Retrouvez les articles, analyses et actualites de la categorie ${slug} avec une navigation claire par sujets et publications recentes.`
        : 'Parcourez les categories editoriales Iran Info pour acceder aux sujets politiques, internationaux, societaux et economiques via des URLs propres.',
      'Categories editoriales Iran Info pour explorer les contenus.',
    );

    setDocumentSeo({
      title,
      description,
      path: slug ? `/categorie/${slug}` : '/categorie',
      type: 'website',
    });
  }, [slug]);

  useEffect(() => {
    let isMounted = true;

    async function loadData() {
      setLoading(true);
      try {
        const [categoriesResponse, articlesResponse] = await Promise.all([
          fetch(`${API_BASE}/categories`),
          fetch(`${API_BASE}/articles`),
        ]);
        const [categoriesData, articlesData] = await Promise.all([
          categoriesResponse.json(),
          articlesResponse.json(),
        ]);

        if (isMounted) {
          setCategories(Array.isArray(categoriesData) ? categoriesData : []);
          setArticles(Array.isArray(articlesData) ? articlesData : []);
        }
      } catch {
        if (isMounted) {
          setCategories([]);
          setArticles([]);
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadData();
    return () => {
      isMounted = false;
    };
  }, []);

  const selectedCategory = useMemo(
    () => (slug ? categories.find((category) => category.slug === slug) ?? null : null),
    [categories, slug],
  );

  const filteredArticles = useMemo(() => {
    if (!slug) return [];

    return articles.filter((article) =>
      Array.isArray(article.categories) && article.categories.some((category) => category.slug === slug),
    );
  }, [articles, slug]);

  if (loading) {
    return (
      <main className="news-main">
        <p>Chargement des categories...</p>
      </main>
    );
  }

  if (slug) {
    return (
      <main className="news-main">
        <section className="news-section" aria-labelledby="category-detail-title">
          <h1 id="category-detail-title">{selectedCategory?.name ?? 'Categorie introuvable'}</h1>
          <p>{selectedCategory?.description || 'Cette categorie ne contient pas encore de description.'}</p>

          <h2 className="news-inner-title">Articles de cette categorie</h2>
          {filteredArticles.length === 0 ? (
            <p>Aucun article lie a cette categorie pour l'instant.</p>
          ) : (
            <div className="news-card-grid">
              {filteredArticles.map((article) => (
                <article className="news-article-card" key={article.id}>
                  {article.image ? (
                    <Link to={`/article/${article.slug}`}>
                      <img
                        src={optimizeImageUrl(article.image, 900)}
                        alt={article.title}
                        className="news-card-image"
                        loading="lazy"
                        decoding="async"
                      />
                    </Link>
                  ) : null}
                  <h3 className="news-card-title">
                    <Link to={`/article/${article.slug}`}>{article.title}</Link>
                  </h3>
                  <p>{article.metaDescription || `${(article.content ?? '').slice(0, 120)}...`}</p>
                </article>
              ))}
            </div>
          )}
        </section>
      </main>
    );
  }

  return (
    <main className="news-main">
      <section className="news-section" aria-labelledby="categories-title">
        <h1 id="categories-title">Toutes les categories</h1>
        <p>Explore les rubriques pour naviguer rapidement parmi les sujets.</p>

        {categories.length === 0 ? (
          <p>Aucune categorie disponible.</p>
        ) : (
          <div className="news-category-grid">
            {categories.map((category) => (
              <article className="news-category-card" key={category.id}>
                <h2 className="news-category-name">{category.name}</h2>
                <p>{category.description || 'Pas de description pour le moment.'}</p>
                <Link to={`/categorie/${category.slug}`} className="news-link-inline" aria-label={`Voir la categorie ${category.name}`}>
                  Ouvrir la categorie
                </Link>
              </article>
            ))}
          </div>
        )}
      </section>
    </main>
  );
}
