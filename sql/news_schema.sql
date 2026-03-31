-- PostgreSQL schema for a news website
-- Covers: users, articles, categories, article_category
-- Includes SEO fields and slug-based URL rewriting support

BEGIN;

CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT users_role_check CHECK (role IN ('admin'))
);

CREATE TABLE IF NOT EXISTS categories (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(150) NOT NULL UNIQUE,
  description TEXT,
  meta_title VARCHAR(255),
  meta_description VARCHAR(300),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS articles (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  cover_image_path VARCHAR(500) NOT NULL,
  cover_image_alt VARCHAR(160) NOT NULL,
  gallery_images JSONB NOT NULL DEFAULT '[]'::jsonb,
  slug VARCHAR(180) NOT NULL UNIQUE,
  meta_title VARCHAR(60) NOT NULL,
  meta_description VARCHAR(160) NOT NULL,
  meta_keywords VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  featured BOOLEAN NOT NULL DEFAULT FALSE,
  published_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT articles_meta_title_len_check CHECK (char_length(meta_title) BETWEEN 50 AND 60),
  CONSTRAINT articles_meta_description_len_check CHECK (char_length(meta_description) BETWEEN 150 AND 160),
  CONSTRAINT articles_status_check CHECK (status IN ('published')),
  CONSTRAINT fk_articles_user FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS article_category (
  article_id BIGINT NOT NULL,
  category_id BIGINT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  PRIMARY KEY (article_id, category_id),
  CONSTRAINT fk_article_category_article FOREIGN KEY (article_id)
    REFERENCES articles(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_article_category_category FOREIGN KEY (category_id)
    REFERENCES categories(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- SEO and performance indexes
CREATE INDEX IF NOT EXISTS idx_articles_created_at ON articles (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_status_created_at ON articles (status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_published_at ON articles (published_at DESC);
CREATE INDEX IF NOT EXISTS idx_categories_created_at ON categories (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_article_category_category_id ON article_category (category_id);

-- Default admin seed (password: admin123).
-- Change this password immediately in production.
INSERT INTO users (username, email, password_hash, role)
VALUES (
  'admin',
  'admin@iran.local',
  '$2y$10$sbFxwXJSK3Jh4zKiLpQVP.4US75oNEyHKVpXGc8dxJ8RVWSkeLKxW',
  'admin'
)
ON CONFLICT DO NOTHING;

COMMIT;
