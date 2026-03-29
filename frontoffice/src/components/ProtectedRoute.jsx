import { Navigate } from 'react-router-dom';
import { getToken } from '../utils/authStorage';

export function ProtectedRoute({ children }) {
  const token = getToken();
  if (!token) {
    return <Navigate to="/admin/login" replace />;
  }

  return children;
}
