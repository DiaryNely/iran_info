import { Link, NavLink, Outlet } from 'react-router-dom';

export function PublicLayout() {
  return (
    <div className="news-shell">
      <header className="news-header">
        <div className="news-header-inner">
          <Link to="/" className="news-logo" aria-label="Aller a l'accueil Iran Info">
            IRAN INFO
          </Link>

          <nav className="news-nav" aria-label="Navigation principale">
            <NavLink to="/" end className={({ isActive }) => (isActive ? 'news-nav-link active' : 'news-nav-link')}>
              Accueil
            </NavLink>
            <NavLink to="/categorie" className={({ isActive }) => (isActive ? 'news-nav-link active' : 'news-nav-link')}>
              Categorie
            </NavLink>
          </nav>

          <div className="news-header-actions">
            <Link to="/categorie" className="news-subscribe-btn">
              S'abonner
            </Link>
            <Link to="/admin/login" className="news-admin-link">
              Admin
            </Link>
          </div>
        </div>
      </header>

      <Outlet />

      <footer className="news-footer">
        <div className="news-footer-inner">
          <p>Iran Info | Journal numerique international</p>
        </div>
      </footer>
    </div>
  );
}
