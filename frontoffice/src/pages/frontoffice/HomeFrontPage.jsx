import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  buildSeoDescription,
  buildSeoTitle,
  optimizeImageUrl,
  removeJsonLd,
  setDocumentSeo,
  upsertJsonLd,
} from './seo';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

function normalizeToken(token) {
  const cleaned = token
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '');

  if (cleaned.length > 3 && cleaned.endsWith('es')) {
    return cleaned.slice(0, -2);
  }

  if (cleaned.length > 2 && (cleaned.endsWith('s') || cleaned.endsWith('x'))) {
    return cleaned.slice(0, -1);
  }

  return cleaned;
}

function normalizeTextForSearch(value) {
  return String(value ?? '')
    .split(/\s+/)
    .map((token) => normalizeToken(token))
    .filter(Boolean);
}

function toShortDate(value) {
  if (!value) return 'Date inconnue';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? 'Date inconnue'
    : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(date);
}

export function HomeFrontPage() {
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [sortOrder, setSortOrder] = useState('newest');

  useEffect(() => {
    const title = buildSeoTitle('Actualites Iran et international en continu');
    const description = buildSeoDescription(
      "Suivez les actualites sur l'Iran, le Moyen-Orient et le monde avec analyses, contexte, reportages et articles de reference actualises chaque jour.",
      "Actualites, analyses et dossiers sur l'Iran et l'international.",
    );

    setDocumentSeo({
      title,
      description,
      path: '/',
      type: 'website',
    });

    upsertJsonLd('ld-home-website', {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: 'Iran Info',
      url: 'https://iran-info.local/',
      inLanguage: 'fr-FR',
      potentialAction: {
        '@type': 'SearchAction',
        target: 'https://iran-info.local/?q={search_term_string}',
        'query-input': 'required name=search_term_string',
      },
    });

    return () => {
      removeJsonLd('ld-home-website');
    };
  }, []);

  useEffect(() => {
    let isMounted = true;

    async function loadArticles() {
      setLoading(true);
      try {
        const response = await fetch(`${API_BASE}/articles`);
        const data = await response.json();
        if (isMounted) {
          setArticles(Array.isArray(data) ? data : []);
        }
      } catch {
        if (isMounted) {
          setArticles([]);
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    loadArticles();
    return () => {
      isMounted = false;
    };
  }, []);

  const filteredArticles = useMemo(() => {
    const queryTokens = normalizeTextForSearch(query);

    return articles
      .filter((article) => {
        if (queryTokens.length === 0) return true;

        const textTokens = normalizeTextForSearch(
          `${article.title ?? ''} ${article.metaDescription ?? ''} ${article.content ?? ''}`,
        );
        const textSet = new Set(textTokens);

        return queryTokens.every((queryToken) => {
          if (textSet.has(queryToken)) {
            return true;
          }

          return textTokens.some((textToken) => textToken.includes(queryToken));
        });
      })
      .filter((article) => {
        if (!selectedDate) return true;
        if (!article.createdAt) return false;

        const articleDate = new Date(article.createdAt);
        if (Number.isNaN(articleDate.getTime())) return false;

        const yyyy = articleDate.getFullYear();
        const mm = String(articleDate.getMonth() + 1).padStart(2, '0');
        const dd = String(articleDate.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}` === selectedDate;
      })
      .sort((a, b) => {
        const left = new Date(a.createdAt ?? 0).getTime();
        const right = new Date(b.createdAt ?? 0).getTime();
        return sortOrder === 'oldest' ? left - right : right - left;
      });
  }, [articles, query, selectedDate, sortOrder]);

  const popularArticles = useMemo(() => filteredArticles.slice(0, 5), [filteredArticles]);

  function readCategory(article) {
    if (Array.isArray(article?.categories) && article.categories.length > 0) {
      return article.categories[0].name;
    }
    return 'Actualite';
  }

  return (
    <main className="news-main" id="main-content">
      <section className="news-page-title" aria-labelledby="home-title">
        <h1 id="home-title">L'actualite internationale et iranienne en continu</h1>
      </section>

      <div className="news-home-grid">
        <div className="news-home-main">
          <section className="news-section" aria-labelledby="latest-title">
            <div className="news-section-head">
              <h2 id="latest-title">Tous les articles</h2>
            </div>

            <div className="news-filters" aria-label="Filtres articles">
              <label>
                Rechercher un article
                <input
                  type="search"
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  placeholder="Titre, resume ou contenu"
                />
              </label>

              <label>
                Date
                <input
                  type="date"
                  value={selectedDate}
                  onChange={(event) => setSelectedDate(event.target.value)}
                />
              </label>

              <label>
                Tri
                <select
                  value={sortOrder}
                  onChange={(event) => setSortOrder(event.target.value)}
                >
                  <option value="newest">Plus recent</option>
                  <option value="oldest">Plus ancien</option>
                </select>
              </label>
            </div>

            <div className="news-feed-list">
              {filteredArticles.map((article) => (
                <article className="news-article-card news-article-card-large" key={article.id}>
                  {article.image ? (
                    <Link to={`/article/${article.slug}`} aria-label={`Ouvrir ${article.title}`} className="news-card-image-link-large">
                      <img
                        src={optimizeImageUrl(article.image, 1400)}
                        alt={article.title}
                        className="news-card-image news-card-image-large"
                        loading="lazy"
                        decoding="async"
                      />
                    </Link>
                  ) : null}
                  <p className="news-card-kicker">{readCategory(article)}</p>
                  <h3 className="news-card-title">
                    <Link to={`/article/${article.slug}`}>{article.title}</Link>
                  </h3>
                  <p className="news-card-summary-large">
                    {article.metaDescription || `${(article.content ?? '').slice(0, 220)}...`}
                  </p>
                  <p className="news-card-date">{toShortDate(article.createdAt)}</p>
                </article>
              ))}
            </div>
            {!loading && filteredArticles.length === 0 ? (
              <p>Aucun article ne correspond aux filtres choisis.</p>
            ) : null}
          </section>
        </div>

        <aside className="news-sidebar" aria-label="Informations complementaires">
          <section className="news-sidebar-block" aria-labelledby="popular-title">
            <h2 id="popular-title">Articles populaires</h2>
            <div className="news-popular-list">
              {popularArticles.map((article) => (
                <article key={article.id} className="news-popular-item">
                  <h3>
                    <Link to={`/article/${article.slug}`}>{article.title}</Link>
                  </h3>
                  <p>{toShortDate(article.createdAt)}</p>
                </article>
              ))}
            </div>
          </section>

          <section className="news-sidebar-block" aria-labelledby="newsletter-title">
            <h2 id="newsletter-title">Newsletter</h2>
            <p>Contact redaction: contact@iraninfo.local</p>
          </section>
        </aside>
      </div>
    </main>
  );
}
