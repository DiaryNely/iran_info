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
  image TEXT,
  slug VARCHAR(180) NOT NULL UNIQUE,
  meta_title VARCHAR(255),
  meta_description VARCHAR(300),
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  published_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT articles_status_check CHECK (status IN ('draft', 'published', 'archived')),
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

COMMIT;
