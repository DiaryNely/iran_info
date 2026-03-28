import { Link, Route, Routes } from 'react-router-dom';
import { ArticlePage } from './pages/ArticlePage';
import { HomePage } from './pages/HomePage';

export default function App() {
  return (
    <div className="container">
      <header className="header">
        <h1>Iran Info</h1>
        <nav>
          <Link to="/">Accueil</Link>
        </nav>
      </header>

      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/article/:slug" element={<ArticlePage />} />
      </Routes>
    </div>
  );
}
