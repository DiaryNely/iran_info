import { useEffect, useState } from 'react';
import { articlesApi, categoriesApi } from '../api/client';
import {
  Chart as ChartJS,
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend,
} from 'chart.js';
import { Doughnut, Line } from 'react-chartjs-2';

const HEATMAP_DAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
const HEATMAP_SLOT_LABELS = ['Nuit', 'Matin', 'Aprem', 'Soir'];

ChartJS.register(
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend,
);

function monthKey(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function buildLastMonths(size) {
  const now = new Date();
  const keys = [];
  for (let i = size - 1; i >= 0; i -= 1) {
    const current = new Date(now.getFullYear(), now.getMonth() - i, 1);
    keys.push({
      key: monthKey(current),
      label: current.toLocaleDateString('fr-FR', { month: 'short' }),
    });
  }
  return keys;
}

function asDate(value) {
  if (!value) {
    return null;
  }
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

export function AdminDashboardPage() {
  const [loading, setLoading] = useState(true);
  const [articlesRaw, setArticlesRaw] = useState([]);
  const [stats, setStats] = useState({
    articles: 0,
    categories: 0,
    published: 0,
    featured: 0,
  });
  const [monthly, setMonthly] = useState({ labels: [], created: [], published: [] });

  useEffect(() => {
    async function loadStats() {
      try {
        const [articles, categories] = await Promise.all([articlesApi.list(), categoriesApi.list()]);
        const safeArticles = Array.isArray(articles) ? articles : [];

        const labels = buildLastMonths(6);
        const createdByMonth = Object.fromEntries(labels.map((item) => [item.key, 0]));
        const publishedByMonth = Object.fromEntries(labels.map((item) => [item.key, 0]));

        safeArticles.forEach((article) => {
          const createdAt = asDate(article.createdAt);
          if (!createdAt) {
            return;
          }

          const key = monthKey(createdAt);
          if (!Object.prototype.hasOwnProperty.call(createdByMonth, key)) {
            return;
          }

          createdByMonth[key] += 1;
          if (article.status === 'published') {
            publishedByMonth[key] += 1;
          }
        });

        setStats({
          articles: safeArticles.length,
          categories: Array.isArray(categories) ? categories.length : 0,
          published: safeArticles.filter((item) => item.status === 'published').length,
          featured: safeArticles.filter((item) => item.featured).length,
        });
        setArticlesRaw(safeArticles);

        setMonthly({
          labels: labels.map((item) => item.label),
          created: labels.map((item) => createdByMonth[item.key]),
          published: labels.map((item) => publishedByMonth[item.key]),
        });
      } finally {
        setLoading(false);
      }
    }

    loadStats();
  }, []);

  const publishedRatio = stats.articles > 0 ? Math.round((stats.published / stats.articles) * 100) : 0;

  const heatmap = Array.from({ length: 7 }, () => Array.from({ length: 4 }, () => 0));
  articlesRaw.forEach((article) => {
    const date = asDate(article.createdAt);
    if (!date) {
      return;
    }

    const dayIndex = (date.getDay() + 6) % 7;
    const hour = date.getHours();
    const slotIndex = hour < 6 ? 0 : hour < 12 ? 1 : hour < 18 ? 2 : 3;
    heatmap[dayIndex][slotIndex] += 1;
  });
  const heatmapMax = Math.max(...heatmap.flat(), 1);

  const doughnutData = {
    labels: ['Publies', 'Non publies'],
    datasets: [
      {
        data: [stats.published, Math.max(stats.articles - stats.published, 0)],
        backgroundColor: ['#0b7a52', '#d6ddd8'],
        borderWidth: 0,
      },
    ],
  };

  const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
      legend: { position: 'bottom' },
    },
  };

  const lineData = {
    labels: monthly.labels,
    datasets: [
      {
        label: 'Crees',
        data: monthly.created,
        borderColor: '#0ea5e9',
        backgroundColor: 'rgba(14, 165, 233, 0.12)',
        fill: true,
        tension: 0.35,
      },
      {
        label: 'Publies',
        data: monthly.published,
        borderColor: '#0b7a52',
        backgroundColor: 'rgba(11, 122, 82, 0.12)',
        fill: true,
        tension: 0.35,
      },
    ],
  };

  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        ticks: { stepSize: 1 },
      },
      x: {
        grid: { display: false },
      },
    },
    plugins: {
      legend: { position: 'top' },
    },
  };

  return (
    <section className="dashboard-wrap-v2">
      <div className="dashboard-header">
        <div>
          <h2>Dashboard</h2>
          <p>Vue essentielle de l'activite editoriale.</p>
        </div>
      </div>

      <div className="kpis-v2 kpis-v2-compact">
        <article className="kpi-card-v2">
          <div className="kpi-body">
            <span className="kpi-label">Articles</span>
            <strong className="kpi-value">{loading ? '...' : stats.articles}</strong>
          </div>
        </article>

        <article className="kpi-card-v2">
          <div className="kpi-body">
            <span className="kpi-label">Categories</span>
            <strong className="kpi-value">{loading ? '...' : stats.categories}</strong>
          </div>
        </article>

        <article className="kpi-card-v2">
          <div className="kpi-body">
            <span className="kpi-label">Publies</span>
            <strong className="kpi-value">{loading ? '...' : stats.published}</strong>
          </div>
        </article>

        <article className="kpi-card-v2">
          <div className="kpi-body">
            <span className="kpi-label">A la une</span>
            <strong className="kpi-value">{loading ? '...' : stats.featured}</strong>
          </div>
        </article>
      </div>

      <div className="charts-grid-v2 charts-grid-v2-compact">
        <article className="chart-card-v2">
          <h3>Taux de publication</h3>
          {loading ? (
            <div className="chart-placeholder">Chargement...</div>
          ) : (
            <>
              <div className="chart-container chart-container-compact">
                <Doughnut data={doughnutData} options={doughnutOptions} />
              </div>
              <p className="chart-footnote">{publishedRatio}% des articles sont publies</p>
            </>
          )}
        </article>

        <article className="chart-card-v2">
          <h3>Evolution mensuelle (6 mois)</h3>
          {loading ? (
            <div className="chart-placeholder">Chargement...</div>
          ) : (
            <div className="chart-container chart-container-compact">
              <Line data={lineData} options={lineOptions} />
            </div>
          )}
        </article>

        <article className="chart-card-v2 chart-span-2-v2">
          <h3>Heatmap activite editoriale</h3>
          {loading ? (
            <div className="chart-placeholder">Chargement...</div>
          ) : (
            <div className="heatmap-wrap">
              <div className="heatmap-header">
                <small></small>
                {HEATMAP_SLOT_LABELS.map((slot) => (
                  <small key={slot}>{slot}</small>
                ))}
              </div>
              {heatmap.map((row, dayIndex) => (
                <div className="heatmap-row" key={HEATMAP_DAY_LABELS[dayIndex]}>
                  <span>{HEATMAP_DAY_LABELS[dayIndex]}</span>
                  <div className="heatmap-cells">
                    {row.map((value, slotIndex) => {
                      const opacity = value === 0 ? 0.08 : 0.2 + (value / heatmapMax) * 0.8;
                      return (
                        <div
                          key={`${dayIndex}-${slotIndex}`}
                          className="heatmap-cell-v2"
                          style={{ background: `rgba(11, 122, 82, ${Math.min(opacity, 1)})` }}
                          title={`${HEATMAP_DAY_LABELS[dayIndex]} - ${HEATMAP_SLOT_LABELS[slotIndex]}: ${value}`}
                        >
                          {value > 0 && <span className="heatmap-val">{value}</span>}
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          )}
        </article>
      </div>
    </section>
  );
}
