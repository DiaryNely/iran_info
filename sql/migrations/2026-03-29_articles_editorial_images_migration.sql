BEGIN;

-- 1) Add new editorial/image columns if missing.
ALTER TABLE articles
  ADD COLUMN IF NOT EXISTS cover_image_path VARCHAR(500),
  ADD COLUMN IF NOT EXISTS cover_image_alt VARCHAR(160),
  ADD COLUMN IF NOT EXISTS gallery_images JSONB,
  ADD COLUMN IF NOT EXISTS meta_keywords VARCHAR(255),
  ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE;

-- 2) Initialize JSON gallery storage.
UPDATE articles
SET gallery_images = '[]'::jsonb
WHERE gallery_images IS NULL;

-- 3) Backfill cover from old columns when available.
DO $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_name = 'articles' AND column_name = 'image'
  ) THEN
    EXECUTE $sql$
      UPDATE articles
      SET cover_image_path = COALESCE(cover_image_path, image)
      WHERE cover_image_path IS NULL
        AND image IS NOT NULL
        AND btrim(image) <> ''
    $sql$;
  END IF;
END $$;

DO $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_name = 'articles' AND column_name = 'images'
  ) THEN
    -- If cover still empty, use first image from legacy array.
    EXECUTE $sql$
      UPDATE articles
      SET cover_image_path = COALESCE(cover_image_path, images[1])
      WHERE cover_image_path IS NULL
        AND images IS NOT NULL
        AND array_length(images, 1) >= 1
    $sql$;

    -- Build gallery_images from legacy array items after index 1.
    EXECUTE $sql$
      UPDATE articles a
      SET gallery_images = COALESCE(src.gallery, '[]'::jsonb)
      FROM (
        SELECT
          id,
          COALESCE(
            jsonb_agg(
              jsonb_build_object('path', img_path, 'alt', LEFT(COALESCE(NULLIF(btrim(title), ''), 'Image article'), 160))
              ORDER BY idx
            ) FILTER (WHERE idx > 1 AND img_path IS NOT NULL AND btrim(img_path) <> ''),
            '[]'::jsonb
          ) AS gallery
        FROM (
          SELECT id, title, u.idx, u.img_path
          FROM articles
          CROSS JOIN LATERAL unnest(images) WITH ORDINALITY AS u(img_path, idx)
        ) t
        GROUP BY id
      ) src
      WHERE a.id = src.id
        AND (a.gallery_images IS NULL OR a.gallery_images = '[]'::jsonb)
    $sql$;
  END IF;
END $$;

-- 4) Fill defaults to satisfy non-null model.
UPDATE articles
SET
  cover_image_path = COALESCE(NULLIF(btrim(cover_image_path), ''), '/uploads/articles/default-cover.jpg'),
  cover_image_alt = COALESCE(NULLIF(btrim(cover_image_alt), ''), LEFT(COALESCE(NULLIF(btrim(title), ''), 'Image principale article'), 160)),
  meta_keywords = COALESCE(NULLIF(btrim(meta_keywords), ''), 'iran, actualites, geopolitique'),
  featured = COALESCE(featured, FALSE)
WHERE
  cover_image_path IS NULL
  OR btrim(cover_image_path) = ''
  OR cover_image_alt IS NULL
  OR btrim(cover_image_alt) = ''
  OR meta_keywords IS NULL
  OR btrim(meta_keywords) = ''
  OR featured IS NULL;

-- 5) Tighten existing SEO columns without dropping data.
-- Trim oversized values while preserving content.
UPDATE articles
SET
  meta_title = LEFT(COALESCE(meta_title, ''), 60),
  meta_description = LEFT(COALESCE(meta_description, ''), 160)
WHERE
  (meta_title IS NOT NULL AND LENGTH(meta_title) > 60)
  OR (meta_description IS NOT NULL AND LENGTH(meta_description) > 160);

-- Ensure metadata is present.
UPDATE articles
SET
  meta_title = COALESCE(NULLIF(btrim(meta_title), ''), LEFT(COALESCE(NULLIF(btrim(title), ''), 'Actualites Iran'), 60)),
  meta_description = COALESCE(NULLIF(btrim(meta_description), ''), LEFT(COALESCE(NULLIF(btrim(content), ''), 'Actualites et analyses geopolitique Iran.'), 160))
WHERE
  meta_title IS NULL OR btrim(meta_title) = ''
  OR meta_description IS NULL OR btrim(meta_description) = '';

-- Convert column lengths to match the new model.
ALTER TABLE articles
  ALTER COLUMN meta_title TYPE VARCHAR(60) USING LEFT(COALESCE(meta_title, ''), 60),
  ALTER COLUMN meta_description TYPE VARCHAR(160) USING LEFT(COALESCE(meta_description, ''), 160);

-- 6) Enforce not null for newly required fields.
ALTER TABLE articles
  ALTER COLUMN cover_image_path SET NOT NULL,
  ALTER COLUMN cover_image_alt SET NOT NULL,
  ALTER COLUMN gallery_images SET NOT NULL,
  ALTER COLUMN gallery_images SET DEFAULT '[]'::jsonb,
  ALTER COLUMN meta_title SET NOT NULL,
  ALTER COLUMN meta_description SET NOT NULL,
  ALTER COLUMN meta_keywords SET NOT NULL,
  ALTER COLUMN featured SET NOT NULL,
  ALTER COLUMN status SET DEFAULT 'published';

-- 7) Add constraints for future writes.
-- Use NOT VALID so old imperfect rows do not block migration; they can be fixed then validated.
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'articles_status_check'
  ) THEN
    ALTER TABLE articles
      ADD CONSTRAINT articles_status_check
      CHECK (status IN ('published'));
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'articles_meta_title_len_check'
  ) THEN
    ALTER TABLE articles
      ADD CONSTRAINT articles_meta_title_len_check
      CHECK (char_length(meta_title) BETWEEN 50 AND 60)
      NOT VALID;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'articles_meta_description_len_check'
  ) THEN
    ALTER TABLE articles
      ADD CONSTRAINT articles_meta_description_len_check
      CHECK (char_length(meta_description) BETWEEN 150 AND 160)
      NOT VALID;
  END IF;
END $$;

-- 8) Drop legacy columns once migration is complete.
ALTER TABLE articles
  DROP COLUMN IF EXISTS image,
  DROP COLUMN IF EXISTS images;

-- 9) Helpful index for front featured sorting.
CREATE INDEX IF NOT EXISTS idx_articles_featured_created_at
  ON articles (featured DESC, created_at DESC);

COMMIT;
