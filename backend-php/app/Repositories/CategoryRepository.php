<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(): array
    {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.slug,
                c.description,
                c.meta_title,
                c.meta_description,
                c.created_at,
                c.updated_at,
                COUNT(ac.article_id)::int AS article_count
            FROM categories c
            LEFT JOIN article_category ac ON ac.category_id = c.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ";

        $rows = $this->db->query($sql)->fetchAll();
        if (!$rows) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'metaTitle' => $row['meta_title'],
                'metaDescription' => $row['meta_description'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
                'articleCount' => (int) $row['article_count'],
            ];
        }, $rows);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.slug,
                c.description,
                c.meta_title,
                c.meta_description,
                c.created_at,
                c.updated_at,
                COUNT(ac.article_id)::int AS article_count
            FROM categories c
            LEFT JOIN article_category ac ON ac.category_id = c.id
            WHERE c.id = :id
            GROUP BY c.id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'metaTitle' => $row['meta_title'],
            'metaDescription' => $row['meta_description'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
            'articleCount' => (int) $row['article_count'],
        ];
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.slug,
                c.description,
                c.meta_title,
                c.meta_description,
                c.created_at,
                c.updated_at,
                COUNT(ac.article_id)::int AS article_count
            FROM categories c
            LEFT JOIN article_category ac ON ac.category_id = c.id
            WHERE c.slug = :slug
            GROUP BY c.id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'metaTitle' => $row['meta_title'],
            'metaDescription' => $row['meta_description'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
            'articleCount' => (int) $row['article_count'],
        ];
    }

    /** @param array{name:string,slug:string,description:string,meta_title:string,meta_description:string} $payload */
    public function create(array $payload): void
    {
        $sql = '
            INSERT INTO categories (name, slug, description, meta_title, meta_description)
            VALUES (:name, :slug, :description, :meta_title, :meta_description)
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] !== '' ? $payload['description'] : null,
            'meta_title' => $payload['meta_title'] !== '' ? $payload['meta_title'] : null,
            'meta_description' => $payload['meta_description'] !== '' ? $payload['meta_description'] : null,
        ]);
    }

    /** @param array{name:string,slug:string,description:string,meta_title:string,meta_description:string} $payload */
    public function update(int $id, array $payload): void
    {
        $sql = '
            UPDATE categories
            SET name = :name,
                slug = :slug,
                description = :description,
                meta_title = :meta_title,
                meta_description = :meta_description,
                updated_at = NOW()
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] !== '' ? $payload['description'] : null,
            'meta_title' => $payload['meta_title'] !== '' ? $payload['meta_title'] : null,
            'meta_description' => $payload['meta_description'] !== '' ? $payload['meta_description'] : null,
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
