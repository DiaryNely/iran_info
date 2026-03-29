import { NavLink, useNavigate } from 'react-router-dom';
import { logout } from '../api/client';
import { getUser } from '../utils/authStorage';

export function AdminLayout({ children, onToast }) {
  const user = getUser();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    onToast({ type: 'success', message: 'Deconnexion reussie.' });
    navigate('/login');
  }

  return (
    <div className="admin-shell">
      <aside className="sidebar">
        <h1>Iran Info</h1>
        <p className="sidebar-sub">BackOffice Admin</p>
        <nav>
          <NavLink to="/dashboard">Dashboard</NavLink>
          <NavLink to="/articles">Articles</NavLink>
          <NavLink to="/categories">Categories</NavLink>
        </nav>
        <div className="sidebar-footer">
          <p>{user?.username ?? 'admin'}</p>
          <button type="button" className="btn btn-outline" onClick={handleLogout}>
            Logout
          </button>
        </div>
      </aside>
      <main className="content">{children}</main>
    </div>
  );
}
