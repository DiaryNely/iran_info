<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Repositories\ArticleRepository;
use Throwable;

final class ArticleController
{
    private ?ArticleRepository $articles = null;

    private function repo(): ArticleRepository
    {
        if (!$this->articles instanceof ArticleRepository) {
            $this->articles = new ArticleRepository();
        }
        return $this->articles;
    }

    /** @param array{slug?: string} $params */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        if ($slug === '') {
            http_response_code(404);
            view('errors.404');
            return;
        }

        try {
            $article = $this->repo()->findPublishedBySlug($slug);
            if ($article === null) {
                http_response_code(404);
                view('errors.404');
                return;
            }

            view('front.article', [
                'title' => ($article['metaTitle'] ?: $article['title']) . ' | Iran Info',
                'article' => $article,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
