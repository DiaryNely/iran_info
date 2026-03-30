<?php

declare(strict_types=1);

use App\Controllers\Back\AuthController;
use App\Controllers\Back\ArticleController as BackArticleController;
use App\Controllers\Back\CategoryController;
use App\Controllers\Back\DashboardController;
use App\Controllers\Front\ArticleController as FrontArticleController;
use App\Controllers\Front\HomeController;
use App\Core\Router;

return static function (Router $router): void {
    $home = new HomeController();
    $article = new FrontArticleController();
    $auth = new AuthController();
    $dashboard = new DashboardController();
    $categories = new CategoryController();
    $articlesBack = new BackArticleController();

    $router->get('/', static fn () => $home->index());
    $router->get('/article/{slug}', static fn (array $params) => $article->show($params));

    $router->get('/backoffice/login', static fn () => $auth->loginForm());
    $router->post('/backoffice/login', static fn () => $auth->login());
    $router->post('/backoffice/logout', static fn () => $auth->logout());
    $router->get('/backoffice/dashboard', static fn () => $dashboard->index());
    $router->get('/backoffice/categories', static fn () => $categories->index());
    $router->post('/backoffice/categories/save', static fn () => $categories->save());
    $router->post('/backoffice/categories/delete', static fn () => $categories->delete());
    $router->get('/backoffice/articles', static fn () => $articlesBack->index());
    $router->post('/backoffice/articles/save', static fn () => $articlesBack->save());
    $router->post('/backoffice/articles/delete', static fn () => $articlesBack->delete());
};
