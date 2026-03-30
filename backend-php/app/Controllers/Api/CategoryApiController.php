<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CategoryRepository;
use Throwable;

final class CategoryApiController
{
    private ?CategoryRepository $categories = null;

    private function repo(): CategoryRepository
    {
        if (!$this->categories instanceof CategoryRepository) {
            $this->categories = new CategoryRepository();
        }

        return $this->categories;
    }

    public function index(): void
    {
        try {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                $this->repo()->findAll(),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    /** @param array{slug?: string} $params */
    public function showBySlug(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        if ($slug === '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Slug is required'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        try {
            $category = $this->repo()->findBySlug($slug);
            if ($category === null) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Category not found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($category, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
