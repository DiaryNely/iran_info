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
            $appConfig = require dirname(__DIR__, 3) . '/config/app.php';
            $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
            $items = $this->repo()->findAllPublished();
            $categories = $this->categoryRepo()->findAll();
            $selectedCategorySlug = trim((string) ($_GET['category'] ?? ''));
            $selectedCategoryName = '';

            if ($selectedCategorySlug !== '') {
                foreach ($categories as $category) {
                    $slug = (string) ($category['slug'] ?? '');
                    if ($slug === $selectedCategorySlug) {
                        $selectedCategoryName = (string) ($category['name'] ?? '');
                        break;
                    }
                }
            }

            $canonicalUrl = $baseUrl . '/';
            if ($selectedCategorySlug !== '') {
                $canonicalUrl .= '?category=' . rawurlencode($selectedCategorySlug);
            }

            view('front.home', [
                'title' => 'Accueil | Iran Info PHP',
                'articles' => $items,
                'categories' => $categories,
                'selectedCategorySlug' => $selectedCategorySlug,
                'selectedCategoryName' => $selectedCategoryName,
                'baseUrl' => $baseUrl,
                'canonicalUrl' => $canonicalUrl,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
