import { useEffect, useState } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './components/AdminLayout';
import { ProtectedRoute } from './components/ProtectedRoute';
import { Toast } from './components/Toast';
import { ArticlesPage } from './pages/ArticlesPage';
import { CategoriesPage } from './pages/CategoriesPage';
import { DashboardPage } from './pages/DashboardPage';
import { LoginPage } from './pages/LoginPage';

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
        <Route path="/login" element={<LoginPage onToast={showToast} />} />
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <DashboardPage />
              </AdminLayout>
            </ProtectedRoute>
          }
        />
        <Route
          path="/articles"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <ArticlesPage onToast={showToast} />
              </AdminLayout>
            </ProtectedRoute>
          }
        />
        <Route
          path="/categories"
          element={
            <ProtectedRoute>
              <AdminLayout onToast={showToast}>
                <CategoriesPage onToast={showToast} />
              </AdminLayout>
            </ProtectedRoute>
          }
        />
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
      <Toast toast={toast} onClose={() => setToast(null)} />
    </>
  );
}
