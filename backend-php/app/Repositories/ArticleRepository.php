<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ArticleRepository
{
    private PDO $db;

    private const BASE_SELECT = "
        SELECT
            a.id,
            a.user_id,
            a.title,
            a.content,
            a.cover_image_path,
            a.cover_image_alt,
            a.gallery_images,
            a.slug,
            a.meta_title,
            a.meta_description,
            a.meta_keywords,
            a.status,
            a.featured,
            a.published_at,
            a.created_at,
            a.updated_at,
            u.id AS author_id,
            u.username AS author_username,
            u.email AS author_email,
            u.role AS author_role
        FROM articles a
        JOIN users u ON u.id = a.user_id
    ";

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllPublished(): array
    {
        $sql = self::BASE_SELECT . " WHERE a.status = 'published' ORDER BY a.featured DESC, a.created_at DESC";
        return $this->queryArticlesWithCategories($sql);
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllForAdmin(): array
    {
        $sql = self::BASE_SELECT . " ORDER BY a.created_at DESC";
        return $this->queryArticlesWithCategories($sql);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $sql = self::BASE_SELECT . ' WHERE a.id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $categoriesByArticle = $this->fetchCategoriesByArticleIds([(int) $row['id']]);
        return $this->mapArticle($row, $categoriesByArticle[(int) $row['id']] ?? []);
    }

    /** @return array<string, mixed>|null */
    public function findPublishedBySlug(string $slug): ?array
    {
        $sql = self::BASE_SELECT . " WHERE a.status = 'published' AND a.slug = :slug LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $categoriesByArticle = $this->fetchCategoriesByArticleIds([(int) $row['id']]);
        return $this->mapArticle($row, $categoriesByArticle[(int) $row['id']] ?? []);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM articles WHERE slug = :slug AND id <> :id LIMIT 1');
            $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
            return (bool) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare('SELECT 1 FROM articles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int> $categoryIds
     */
    public function create(array $payload, array $categoryIds): int
    {
        $this->db->beginTransaction();

        try {
            $sql = '
                INSERT INTO articles (
                    user_id, title, content, cover_image_path, cover_image_alt,
                    gallery_images, slug, meta_title, meta_description, meta_keywords,
                    status, featured, published_at
                ) VALUES (
                    :user_id, :title, :content, :cover_image_path, :cover_image_alt,
                    :gallery_images::jsonb, :slug, :meta_title, :meta_description, :meta_keywords,
                    :status,
                    CASE
                        WHEN lower(trim(CAST(:featured_text AS text))) IN (\'1\', \'true\', \'t\', \'on\', \'yes\') THEN TRUE
                        ELSE FALSE
                    END,
                    NOW()
                )
                RETURNING id
            ';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $payload['user_id'],
                'title' => $payload['title'],
                'content' => $payload['content'],
                'cover_image_path' => $payload['cover_image_path'],
                'cover_image_alt' => $payload['cover_image_alt'],
                'gallery_images' => json_encode($payload['gallery_images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'slug' => $payload['slug'],
                'meta_title' => $payload['meta_title'],
                'meta_description' => $payload['meta_description'],
                'meta_keywords' => $payload['meta_keywords'],
                'status' => $payload['status'],
                'featured_text' => isset($payload['featured']) ? (string) $payload['featured'] : '',
            ]);

            $id = (int) $stmt->fetchColumn();
            $this->syncCategories($id, $categoryIds);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int> $categoryIds
     */
    public function update(int $id, array $payload, array $categoryIds): void
    {
        $this->db->beginTransaction();

        try {
            $sql = '
                UPDATE articles
                SET title = :title,
                    content = :content,
                    cover_image_path = :cover_image_path,
                    cover_image_alt = :cover_image_alt,
                    gallery_images = :gallery_images::jsonb,
                    slug = :slug,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    meta_keywords = :meta_keywords,
                    status = :status,
                    featured = CASE
                        WHEN lower(trim(CAST(:featured_text AS text))) IN (\'1\', \'true\', \'t\', \'on\', \'yes\') THEN TRUE
                        ELSE FALSE
                    END,
                    updated_at = NOW()
                WHERE id = :id
            ';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'title' => $payload['title'],
                'content' => $payload['content'],
                'cover_image_path' => $payload['cover_image_path'],
                'cover_image_alt' => $payload['cover_image_alt'],
                'gallery_images' => json_encode($payload['gallery_images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'slug' => $payload['slug'],
                'meta_title' => $payload['meta_title'],
                'meta_description' => $payload['meta_description'],
                'meta_keywords' => $payload['meta_keywords'],
                'status' => $payload['status'],
                'featured_text' => isset($payload['featured']) ? (string) $payload['featured'] : '',
            ]);

            $this->syncCategories($id, $categoryIds);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function syncCategories(int $articleId, array $categoryIds): void
    {
        $del = $this->db->prepare('DELETE FROM article_category WHERE article_id = :article_id');
        $del->execute(['article_id' => $articleId]);

        if (count($categoryIds) === 0) {
            return;
        }

        $ins = $this->db->prepare('INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)');
        foreach ($categoryIds as $categoryId) {
            $ins->execute([
                'article_id' => $articleId,
                'category_id' => $categoryId,
            ]);
        }
    }

    /** @param array<int> $articleIds
     *  @return array<int, array<int, array<string, mixed>>> */
    private function fetchCategoriesByArticleIds(array $articleIds): array
    {
        if (count($articleIds) === 0) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($articleIds), '?'));
        $sql = "
            SELECT
                ac.article_id,
                c.id,
                c.name,
                c.slug,
                c.description,
                c.meta_title,
                c.meta_description,
                c.created_at,
                c.updated_at
            FROM article_category ac
            JOIN categories c ON c.id = ac.category_id
            WHERE ac.article_id IN ($placeholders)
            ORDER BY c.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($articleIds as $idx => $id) {
            $stmt->bindValue($idx + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $articleId = (int) $row['article_id'];
            if (!isset($grouped[$articleId])) {
                $grouped[$articleId] = [];
            }
            $grouped[$articleId][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'metaTitle' => $row['meta_title'],
                'metaDescription' => $row['meta_description'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ];
        }

        return $grouped;
    }

    /** @return array<int, array<string, mixed>> */
    private function queryArticlesWithCategories(string $sql): array
    {
        $rows = $this->db->query($sql)->fetchAll();
        if (!$rows) {
            return [];
        }

        $articleIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $categoriesByArticle = $this->fetchCategoriesByArticleIds($articleIds);

        return array_map(function (array $row) use ($categoriesByArticle): array {
            $id = (int) $row['id'];
            return $this->mapArticle($row, $categoriesByArticle[$id] ?? []);
        }, $rows);
    }

    /** @param array<string, mixed> $row
     *  @param array<int, array<string, mixed>> $categories
     *  @return array<string, mixed> */
    private function mapArticle(array $row, array $categories): array
    {
        $gallery = [];
        if (!empty($row['gallery_images'])) {
            $decoded = json_decode((string) $row['gallery_images'], true);
            if (is_array($decoded)) {
                $gallery = $decoded;
            }
        }

        return [
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'coverImagePath' => $row['cover_image_path'],
            'coverImageAlt' => $row['cover_image_alt'],
            'galleryImages' => $gallery,
            'slug' => $row['slug'],
            'metaTitle' => $row['meta_title'],
            'metaDescription' => $row['meta_description'],
            'metaKeywords' => $row['meta_keywords'],
            'status' => $row['status'],
            'featured' => (bool) $row['featured'],
            'publishedAt' => $row['published_at'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
            'author' => [
                'id' => (int) $row['author_id'],
                'username' => $row['author_username'],
                'email' => $row['author_email'],
                'role' => $row['author_role'],
            ],
            'categories' => $categories,
        ];
    }
}
