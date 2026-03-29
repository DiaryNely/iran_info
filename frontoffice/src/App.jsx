import { useEffect, useState } from 'react';
import { Link, Navigate, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './components/AdminLayout';
import { ProtectedRoute } from './components/ProtectedRoute';
import { Toast } from './components/Toast';
import { AdminArticlesPage } from './pages/AdminArticlesPage';
import { AdminCategoriesPage } from './pages/AdminCategoriesPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { ArticlePage } from './pages/ArticlePage';
import { HomePage } from './pages/HomePage';

export default function App() {
  const [toast, setToast] = useState(null);

  useEffect(() => {
    if (!toast) return;
    const timer = window.setTimeout(() => setToast(null), 3000);
    return () => window.clearTimeout(timer);
  }, [toast]);

  function showToast(nextToast) {
    setToast(nextToast);
  }

  return (
    <>
      <Routes>
        <Route
          path="/"
          element={
            <div className="public-shell">
              <header className="public-header">
                <h1>Iran Info</h1>
                <nav>
                  <Link to="/">Accueil</Link>
                  <Link to="/admin/login">Admin</Link>
                </nav>
              </header>
              <HomePage />
            </div>
          }
        />
        <Route
          path="/article/:slug"
          element={
            <div className="public-shell">
              <header className="public-header">
                <h1>Iran Info</h1>
                <nav>
                  <Link to="/">Accueil</Link>
                  <Link to="/admin/login">Admin</Link>
                </nav>
              </header>
              <ArticlePage />
            </div>
          }
        />

        <Route path="/admin/login" element={<AdminLoginPage onToast={showToast} />} />

        <Route
          path="/admin/dashboard"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <AdminDashboardPage />
              </AdminLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/admin/articles"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <AdminArticlesPage onToast={showToast} />
              </AdminLayout>
            </ProtectedRoute>
          }
        />

        <Route
          path="/admin/categories"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <AdminCategoriesPage onToast={showToast} />
              </AdminLayout>
            </ProtectedRoute>
          }
        />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>

      <Toast toast={toast} onClose={() => setToast(null)} />
    </>
  );
}
