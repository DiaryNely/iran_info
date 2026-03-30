<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ArticleRepository;
use Throwable;

final class ArticleApiController
{
    private ?ArticleRepository $articles = null;

    private function repo(): ArticleRepository
    {
        if (!$this->articles instanceof ArticleRepository) {
            $this->articles = new ArticleRepository();
        }
        return $this->articles;
    }

    public function index(): void
    {
        try {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode(
                $this->repo()->findAllPublished(),
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
        $slug = $params['slug'] ?? '';

        if ($slug === '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Slug is required'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        try {
            $article = $this->repo()->findPublishedBySlug($slug);
            if ($article === null) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Article not found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
