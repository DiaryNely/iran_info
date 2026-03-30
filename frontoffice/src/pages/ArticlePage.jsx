import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { articlesApi } from '../api/client';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

function toAbsoluteUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  return `http://localhost:3000${path}`;
}

export function ArticlePage() {
  const { slug } = useParams();
  const [article, setArticle] = useState(null);

  useEffect(() => {
    if (!slug) {
      return;
    }

    articlesApi
      .bySlug(slug)
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
      {article.coverImagePath ? (
        <img
          src={toAbsoluteUrl(article.coverImagePath)}
          alt={article.coverImageAlt || article.title}
          className="public-cover"
        />
      ) : null}
      {Array.isArray(article.galleryImages) && article.galleryImages.length > 0 ? (
        <div className="public-gallery">
          {article.galleryImages.map((image) => (
            <img
              key={image.path}
              src={toAbsoluteUrl(image.path)}
              alt={image.alt || article.title}
              className="public-gallery-item"
            />
          ))}
        </div>
      ) : null}
      <p>{article.content}</p>
      <Link to="/">Retour a l'accueil</Link>
    </main>
  );
}
