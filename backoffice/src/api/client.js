import { clearSession, getToken, saveSession } from '../utils/authStorage';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:3000/api';
const DEFAULT_ADMIN = {
  username: 'admin',
  email: 'admin@iran.local',
  password: 'admin123',
};

async function request(path, options = {}) {
  const headers = new Headers(options.headers ?? {});
  headers.set('Content-Type', 'application/json');

  const token = getToken();
  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    let message = 'Une erreur est survenue';
    try {
      const body = await response.json();
      message =
        typeof body?.message === 'string'
          ? body.message
          : JSON.stringify(body?.message ?? body);
    } catch {
      message = response.statusText || message;
    }

    if (response.status === 401) {
      clearSession();
    }

    throw new Error(message);
  }

  if (response.status === 204) {
    return null;
  }

  return response.json();
}

async function ensureDefaultAdmin() {
  try {
    await request('/auth/register', {
      method: 'POST',
      body: JSON.stringify(DEFAULT_ADMIN),
    });
  } catch {
    // Ignore if account already exists
  }
}

export async function login({ username, password }) {
  if (username === DEFAULT_ADMIN.username && password === DEFAULT_ADMIN.password) {
    await ensureDefaultAdmin();
    const auth = await request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email: DEFAULT_ADMIN.email, password }),
    });
    saveSession(auth.accessToken, auth.user);
    return auth.user;
  }

  const email = username.includes('@') ? username : '';
  if (!email) {
    throw new Error('Utilise admin/admin123 ou un email valide.');
  }

  const auth = await request('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  saveSession(auth.accessToken, auth.user);
  return auth.user;
}

export async function logout() {
  try {
    await request('/auth/logout', { method: 'POST' });
  } finally {
    clearSession();
  }
}

export const categoriesApi = {
  list: () => request('/categories'),
  create: (payload) => request('/categories', { method: 'POST', body: JSON.stringify(payload) }),
  update: (id, payload) => request(`/categories/${id}`, { method: 'PATCH', body: JSON.stringify(payload) }),
  remove: (id) => request(`/categories/${id}`, { method: 'DELETE' }),
};

export const articlesApi = {
  list: () => request('/articles'),
  create: (payload) => request('/articles', { method: 'POST', body: JSON.stringify(payload) }),
  update: (id, payload) => request(`/articles/${id}`, { method: 'PATCH', body: JSON.stringify(payload) }),
  remove: (id) => request(`/articles/${id}`, { method: 'DELETE' }),
};
