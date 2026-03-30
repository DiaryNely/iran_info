<?php

declare(strict_types=1);

namespace App\Controllers\Back;

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use Throwable;

final class DashboardController
{
    private ?ArticleRepository $articles = null;
    private ?CategoryRepository $categories = null;

    private function articleRepo(): ArticleRepository
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
        if (!isset($_SESSION['admin_user']) || !is_array($_SESSION['admin_user'])) {
            header('Location: /backoffice/login', true, 302);
            return;
        }

        try {
            $allArticles = $this->articleRepo()->findAllForAdmin();
            $categories = $this->categoryRepo()->findAll();

            $totalPublished = 0;
            $totalFeatured = 0;
            foreach ($allArticles as $article) {
                if (($article['status'] ?? '') === 'published') {
                    $totalPublished++;
                }
                if (!empty($article['featured'])) {
                    $totalFeatured++;
                }
            }

            view('back.dashboard', [
                'title' => 'Dashboard | Iran Info PHP',
                'adminUser' => $_SESSION['admin_user'],
                'totalArticles' => count($allArticles),
                'totalCategories' => count($categories),
                'totalPublished' => $totalPublished,
                'totalFeatured' => $totalFeatured,
                'articles' => array_slice($allArticles, 0, 10),
                'dashboardArticlesJson' => json_encode($allArticles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
