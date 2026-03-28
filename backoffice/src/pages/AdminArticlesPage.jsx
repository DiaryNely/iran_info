import { useEffect, useState } from 'react';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';

export function AdminArticlesPage() {
  const [articles, setArticles] = useState([]);

  useEffect(() => {
    fetch(`${API_BASE}/articles`)
      .then((response) => response.json())
      .then((data) => setArticles(Array.isArray(data) ? data : []))
      .catch(() => setArticles([]));
  }, []);

  return (
    <main>
      <h2>Gestion des articles</h2>
      <p>Cette page servira ensuite pour CRUD complet (create, edit, delete).</p>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Slug</th>
          </tr>
        </thead>
        <tbody>
          {articles.map((article) => (
            <tr key={article.id}>
              <td>{article.id}</td>
              <td>{article.title}</td>
              <td>{article.slug}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </main>
  );
}
