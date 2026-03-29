import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { login } from '../api/client';

export function LoginPage({ onToast }) {
  const [form, setForm] = useState({ username: 'admin', password: 'admin123' });
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  async function handleSubmit(event) {
    event.preventDefault();
    setLoading(true);

    try {
      await login(form);
      onToast({ type: 'success', message: 'Connexion reussie.' });
      navigate('/dashboard');
    } catch (error) {
      onToast({ type: 'error', message: error.message });
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="login-wrap">
      <div className="login-card">
        <h1>Admin Panel</h1>
        <p>Connecte-toi pour gerer les contenus.</p>

        <form onSubmit={handleSubmit} className="form-grid">
          <label>
            Username ou Email
            <input
              value={form.username}
              onChange={(event) => setForm((prev) => ({ ...prev, username: event.target.value }))}
              required
            />
          </label>

          <label>
            Password
            <input
              type="password"
              value={form.password}
              onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
              required
            />
          </label>

          <button disabled={loading} className="btn btn-primary" type="submit">
            {loading ? 'Connexion...' : 'Login'}
          </button>
        </form>

        <small>Compte par defaut: admin / admin123</small>
      </div>
    </section>
  );
}
