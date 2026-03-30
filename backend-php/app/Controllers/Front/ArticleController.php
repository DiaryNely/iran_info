<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use Throwable;

final class ArticleController
{
    private ?ArticleRepository $articles = null;
    private ?CategoryRepository $categories = null;

    private function repo(): ArticleRepository
    {
        if (!$this->articles instanceof ArticleRepository) {
            $this->articles = new ArticleRepository();
        }
        return $this->articles;
    }

    private function categoryRepo(): CategoryRepository
    {
        if (!$this->categories instanceof CategoryRepository) {
            $this->categories = new CategoryRepository();
        }

        return $this->categories;
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

            $categories = $this->categoryRepo()->findAll();

            view('front.article', [
                'title' => ($article['metaTitle'] ?: $article['title']) . ' | Iran Info',
                'article' => $article,
                'categories' => $categories,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
