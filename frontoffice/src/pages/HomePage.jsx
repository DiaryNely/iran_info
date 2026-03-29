import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

export function HomePage() {
  const [articles, setArticles] = useState([]);

  useEffect(() => {
    fetch(`${API_BASE}/articles`)
      .then((response) => response.json())
      .then((data) => setArticles(Array.isArray(data) ? data : []))
      .catch(() => setArticles([]));
  }, []);

  return (
    <main className="public-main">
      <h2>Actualites</h2>
      {articles.length === 0 && <p>Aucun article disponible pour le moment.</p>}
      <ul>
        {articles.map((article) => (
          <li key={article.id}>
            <h3>{article.title}</h3>
            <p>{article.metaDescription || (article.content ?? '').slice(0, 140)}...</p>
            <Link to={`/article/${article.slug}`}>Lire</Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
