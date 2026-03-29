import { useEffect, useState } from 'react';
import { articlesApi, categoriesApi } from '../api/client';

export function AdminDashboardPage() {
  const [stats, setStats] = useState({ articles: 0, categories: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadStats() {
      try {
        const [articles, categories] = await Promise.all([articlesApi.list(), categoriesApi.list()]);
        setStats({
          articles: Array.isArray(articles) ? articles.length : 0,
          categories: Array.isArray(categories) ? categories.length : 0,
        });
      } finally {
        setLoading(false);
      }
    }

    loadStats();
  }, []);

  return (
    <section>
      <h2>Dashboard</h2>
      <p>Vision rapide de l'etat du contenu.</p>

      <div className="kpis">
        <article className="kpi-card">
          <h3>Articles</h3>
          <strong>{loading ? '...' : stats.articles}</strong>
        </article>
        <article className="kpi-card">
          <h3>Categories</h3>
          <strong>{loading ? '...' : stats.categories}</strong>
        </article>
      </div>
    </section>
  );
}
