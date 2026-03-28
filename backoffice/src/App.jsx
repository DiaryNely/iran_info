import { Link, Route, Routes } from 'react-router-dom';
import { AdminArticlesPage } from './pages/AdminArticlesPage';
import { DashboardPage } from './pages/DashboardPage';

export default function App() {
  return (
    <div className="container">
      <header className="header">
        <h1>BackOffice Iran Info</h1>
        <nav className="nav">
          <Link to="/">Dashboard</Link>
          <Link to="/articles">Articles</Link>
        </nav>
      </header>

      <Routes>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/articles" element={<AdminArticlesPage />} />
      </Routes>
    </div>
  );
}
