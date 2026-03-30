import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  buildSeoDescription,
  buildSeoTitle,
  optimizeImageUrl,
  removeJsonLd,
  setDocumentSeo,
  upsertJsonLd,
} from './seo';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

function toShortDate(value) {
  if (!value) return 'Date inconnue';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? 'Date inconnue'
    : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(date);
}

export function ArticleFrontPage() {
  const { slug } = useParams();
  const [article, setArticle] = useState(null);
  const [allArticles, setAllArticles] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;

    async function loadArticle() {
      if (!slug) {
        setLoading(false);
        return;
      }

      setLoading(true);
      try {
        const [articleResponse, allArticlesResponse] = await Promise.all([
          fetch(`${API_BASE}/article/${slug}`),
          fetch(`${API_BASE}/articles`),
        ]);

        if (!articleResponse.ok) {
          throw new Error('Article introuvable');
        }

        const [articleData, allArticlesData] = await Promise.all([
          articleResponse.json(),
          allArticlesResponse.json(),
        ]);

        if (isMounted) {
          setArticle(articleData);
          setAllArticles(Array.isArray(allArticlesData) ? allArticlesData : []);
        }
      } catch {
        if (isMounted) {
          setArticle(null);
          setAllArticles([]);
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadArticle();
    return () => {
      isMounted = false;
    };
  }, [slug]);

  useEffect(() => {
    if (!article || !slug) {
      return;
    }

    const title = buildSeoTitle(article.title || 'Article d actualite');
    const description = buildSeoDescription(
      article.metaDescription || article.content || '',
      'Analyse detaillee, contexte et lecture complete de l actualite sur Iran Info.',
    );

    setDocumentSeo({
      title,
      description,
      path: `/article/${slug}`,
      type: 'article',
    });

    upsertJsonLd('ld-article', {
      '@context': 'https://schema.org',
      '@type': 'Article',
      headline: article.title,
      description,
      image: article.image ? [optimizeImageUrl(article.image, 1400)] : [],
      author: {
        '@type': 'Person',
        name: article.author?.username || 'Redaction',
      },
      datePublished: article.createdAt || new Date().toISOString(),
      dateModified: article.updatedAt || article.createdAt || new Date().toISOString(),
      mainEntityOfPage: `https://iran-info.local/article/${slug}`,
      publisher: {
        '@type': 'Organization',
        name: 'Iran Info',
      },
      inLanguage: 'fr-FR',
    });

    return () => {
      removeJsonLd('ld-article');
    };
  }, [article, slug]);

  const categories = useMemo(
    () => (Array.isArray(article?.categories) ? article.categories : []),
    [article],
  );

  const relatedArticles = useMemo(() => {
    if (!article) return [];

    const categorySlugs = new Set(categories.map((category) => category.slug));
    return allArticles
      .filter((item) => item.slug !== article.slug)
      .filter(
        (item) =>
          Array.isArray(item.categories) &&
          item.categories.some((category) => categorySlugs.has(category.slug)),
      )
      .slice(0, 4);
  }, [allArticles, article, categories]);

  const summary = article?.metaDescription || `${(article?.content ?? '').slice(0, 220)}...`;
  const paragraphs = useMemo(() => {
    if (!article?.content) return [];

    const parts = article.content
      .split(/\n{2,}|\r\n{2,}/)
      .map((part) => part.trim())
      .filter(Boolean);

    if (parts.length > 0) {
      return parts;
    }

    return article.content
      .split(/\.\s+/)
      .map((part) => part.trim())
      .filter(Boolean)
      .slice(0, 8)
      .map((part) => `${part}.`);
  }, [article]);

  if (loading) {
    return (
      <main className="news-main">
        <p>Chargement de l'article...</p>
      </main>
    );
  }

  if (!article) {
    return (
      <main className="news-main">
        <section className="news-empty-state">
          <h1>Article introuvable</h1>
          <p>L'URL demandee ne correspond a aucun contenu.</p>
          <Link to="/" className="news-link-inline">
            Retour a l'accueil
          </Link>
        </section>
      </main>
    );
  }

  return (
    <main className="news-main">
      <div className="news-article-layout">
        <article className="news-article-main">
          <header className="news-article-header">
            <p className="news-kicker">
              {categories.length > 0 ? categories[0].name.toUpperCase() : 'INTERNATIONAL'}
            </p>
            <h1>{article.title}</h1>
            <p className="news-article-standfirst">{summary}</p>
            <p className="news-meta-line">
              {article.author?.username ? `Par ${article.author.username} | ` : ''}
              {toShortDate(article.createdAt)}
            </p>
          </header>

          {article.image ? (
            <figure className="news-article-hero">
              <img
                src={optimizeImageUrl(article.image, 1600)}
                alt={article.title}
                loading="lazy"
                decoding="async"
              />
            </figure>
          ) : null}

          <section className="news-article-content" aria-labelledby="article-content-title">
            <h2 id="article-content-title">Analyse</h2>
            {paragraphs.map((paragraph, index) => (
              <p key={`paragraph-${index}`}>{paragraph}</p>
            ))}

            {paragraphs.length > 1 ? (
              <blockquote>
                {paragraphs[1].length > 200 ? `${paragraphs[1].slice(0, 200)}...` : paragraphs[1]}
              </blockquote>
            ) : null}
          </section>
        </article>

        <aside className="news-sidebar" aria-label="Contexte et lectures associees">
          <section className="news-sidebar-block" aria-labelledby="context-title">
            <h2 id="context-title">Contexte</h2>
            <ul className="news-context-list">
              <li>Rubrique: {categories.length > 0 ? categories[0].name : 'Actualite'}</li>
              <li>Date de publication: {toShortDate(article.createdAt)}</li>
              <li>Auteur: {article.author?.username || 'Redaction'}</li>
            </ul>
          </section>

          {article.image ? (
            <section className="news-sidebar-block" aria-labelledby="info-title">
              <h2 id="info-title">Image informative</h2>
              <img
                src={optimizeImageUrl(article.image, 700)}
                alt={`Illustration: ${article.title}`}
                className="news-info-image"
                loading="lazy"
                decoding="async"
              />
            </section>
          ) : null}

          <section className="news-sidebar-block" aria-labelledby="related-title">
            <h2 id="related-title">A lire aussi</h2>
            <div className="news-popular-list">
              {relatedArticles.length === 0 ? (
                <p>Pas encore d'articles lies.</p>
              ) : (
                relatedArticles.map((relatedArticle) => (
                  <article key={relatedArticle.id} className="news-popular-item">
                    <h3>
                      <Link to={`/article/${relatedArticle.slug}`}>{relatedArticle.title}</Link>
                    </h3>
                    <p>{toShortDate(relatedArticle.createdAt)}</p>
                  </article>
                ))
              )}
            </div>
          </section>

          <Link to="/" className="news-all-articles-btn">
            Voir tous les articles
          </Link>
        </aside>
      </div>
    </main>
  );
}
