<?php

declare(strict_types=1);

use App\Controllers\Api\ArticleApiController;
use App\Controllers\Api\CategoryApiController;
use App\Core\Router;

return static function (Router $router): void {
    $articleApi = new ArticleApiController();
    $categoryApi = new CategoryApiController();

    $router->get('/api/articles', static fn () => $articleApi->index());
    $router->get('/api/article/{slug}', static fn (array $params) => $articleApi->showBySlug($params));
    $router->get('/api/categories', static fn () => $categoryApi->index());
    $router->get('/api/categorie/{slug}', static fn (array $params) => $categoryApi->showBySlug($params));
};
