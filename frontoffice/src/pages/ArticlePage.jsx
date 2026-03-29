import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

export function ArticlePage() {
  const { slug } = useParams();
  const [article, setArticle] = useState(null);

  useEffect(() => {
    if (!slug) {
      return;
    }

    fetch(`${API_BASE}/article/${slug}`)
      .then((response) => response.json())
      .then((data) => setArticle(data))
      .catch(() => setArticle(null));
  }, [slug]);

  if (!article) {
    return (
      <main className="public-main">
        <p>Article introuvable.</p>
        <Link to="/">Retour a l'accueil</Link>
      </main>
    );
  }

  return (
    <main className="public-main">
      <h2>{article.title}</h2>
      <p>{article.content}</p>
      <Link to="/">Retour a l'accueil</Link>
    </main>
  );
}
