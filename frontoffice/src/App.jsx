import { useEffect, useState } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './components/AdminLayout';
import { ProtectedRoute } from './components/ProtectedRoute';
import { Toast } from './components/Toast';
import { AdminArticlesPage } from './pages/AdminArticlesPage';
import { AdminCategoriesPage } from './pages/AdminCategoriesPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { ArticleFrontPage } from './pages/frontoffice/ArticleFrontPage';
import { CategoriesFrontPage } from './pages/frontoffice/CategoriesFrontPage';
import { HomeFrontPage } from './pages/frontoffice/HomeFrontPage';
import { PublicLayout } from './pages/frontoffice/PublicLayout';

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
        <Route element={<PublicLayout />}>
          <Route path="/" element={<HomeFrontPage />} />
          <Route path="/article/:slug" element={<ArticleFrontPage />} />
          <Route path="/categorie" element={<CategoriesFrontPage />} />
          <Route path="/categorie/:slug" element={<CategoriesFrontPage />} />
        </Route>

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
