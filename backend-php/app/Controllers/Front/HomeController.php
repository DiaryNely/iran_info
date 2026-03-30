<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use Throwable;

final class HomeController
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

    public function index(): void
    {
        try {
            $items = $this->repo()->findAllPublished();
            $categories = $this->categoryRepo()->findAll();
            $selectedCategorySlug = trim((string) ($_GET['category'] ?? ''));

            view('front.home', [
                'title' => 'Accueil | Iran Info PHP',
                'articles' => $items,
                'categories' => $categories,
                'selectedCategorySlug' => $selectedCategorySlug,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
