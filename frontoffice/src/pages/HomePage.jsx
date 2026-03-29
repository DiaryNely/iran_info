import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { articlesApi } from '../api/client';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

function toAbsoluteUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  return `http://localhost:3000${path}`;
}

export function HomePage() {
  const [articles, setArticles] = useState([]);

  useEffect(() => {
    articlesApi
      .listPublic()
      .then((data) => setArticles(Array.isArray(data) ? data : []))
      .catch(() => setArticles([]));
  }, []);

  const featured = articles.filter((article) => article.featured);
  const regular = articles.filter((article) => !article.featured);

  return (
    <main className="public-main">
      <h2>Actualites</h2>
      {articles.length === 0 && <p>Aucun article disponible pour le moment.</p>}

      {featured.length > 0 ? (
        <section>
          <h3>A la une</h3>
          <ul>
            {featured.map((article) => (
              <li key={article.id} className="featured-card">
                {article.coverImagePath ? (
                  <img
                    src={toAbsoluteUrl(article.coverImagePath)}
                    alt={article.coverImageAlt || article.title}
                    className="public-thumb"
                  />
                ) : null}
                <h3>{article.title}</h3>
                <p>{article.metaDescription || (article.content ?? '').slice(0, 140)}...</p>
                <Link to={`/article/${article.slug}`}>Lire</Link>
              </li>
            ))}
          </ul>
        </section>
      ) : null}

      <ul>
        {regular.map((article) => (
          <li key={article.id}>
            {article.coverImagePath ? (
              <img
                src={toAbsoluteUrl(article.coverImagePath)}
                alt={article.coverImageAlt || article.title}
                className="public-thumb"
              />
            ) : null}
            <h3>{article.title}</h3>
            <p>{article.metaDescription || (article.content ?? '').slice(0, 140)}...</p>
            <Link to={`/article/${article.slug}`}>Lire</Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
