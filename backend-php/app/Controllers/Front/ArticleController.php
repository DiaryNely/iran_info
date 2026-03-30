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
            $appConfig = require dirname(__DIR__, 3) . '/config/app.php';
            $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
            $article = $this->repo()->findPublishedBySlug($slug);
            if ($article === null) {
                http_response_code(404);
                view('errors.404');
                return;
            }

            $categories = $this->categoryRepo()->findAll();
            $canonicalUrl = $baseUrl . '/article/' . rawurlencode((string) ($article['slug'] ?? $slug));

            $allPublished = $this->repo()->findAllPublished();
            $currentId = (int) ($article['id'] ?? 0);
            $currentCategoryIds = [];
            foreach (($article['categories'] ?? []) as $cat) {
                if (is_array($cat) && isset($cat['id'])) {
                    $currentCategoryIds[] = (int) $cat['id'];
                }
            }

            $others = array_values(array_filter($allPublished, static function (array $item) use ($currentId): bool {
                return (int) ($item['id'] ?? 0) !== $currentId;
            }));

            $scoreRelated = static function (array $item) use ($currentCategoryIds): int {
                if (count($currentCategoryIds) === 0) {
                    return 0;
                }

                $itemCategoryIds = [];
                foreach (($item['categories'] ?? []) as $cat) {
                    if (is_array($cat) && isset($cat['id'])) {
                        $itemCategoryIds[] = (int) $cat['id'];
                    }
                }

                if (count($itemCategoryIds) === 0) {
                    return 0;
                }

                return count(array_intersect($currentCategoryIds, $itemCategoryIds));
            };

            usort($others, static function (array $a, array $b) use ($scoreRelated): int {
                $scoreA = $scoreRelated($a);
                $scoreB = $scoreRelated($b);
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $timeA = strtotime((string) ($a['createdAt'] ?? '')) ?: 0;
                $timeB = strtotime((string) ($b['createdAt'] ?? '')) ?: 0;
                return $timeB <=> $timeA;
            });

            $featuredArticle = $others[0] ?? null;
            $readAlsoArticles = [];
            foreach ($others as $item) {
                if ($featuredArticle !== null && (int) ($item['id'] ?? 0) === (int) ($featuredArticle['id'] ?? 0)) {
                    continue;
                }
                $readAlsoArticles[] = $item;
                if (count($readAlsoArticles) >= 5) {
                    break;
                }
            }

            view('front.article', [
                'title' => ($article['metaTitle'] ?: $article['title']) . ' | Iran Info',
                'article' => $article,
                'categories' => $categories,
                'baseUrl' => $baseUrl,
                'canonicalUrl' => $canonicalUrl,
                'featuredArticle' => $featuredArticle,
                'readAlsoArticles' => $readAlsoArticles,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }
}
