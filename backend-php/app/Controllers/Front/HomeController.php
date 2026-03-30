<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Repositories\ArticleRepository;
use Throwable;

final class HomeController
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
            $items = $this->repo()->findAllPublished();

            view('front.home', [
                'title' => 'Accueil | Iran Info PHP',
                'articles' => $items,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
