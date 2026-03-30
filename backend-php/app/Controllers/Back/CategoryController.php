<?php

declare(strict_types=1);

namespace App\Controllers\Back;

use App\Repositories\CategoryRepository;
use Throwable;

final class CategoryController
{
    private ?CategoryRepository $categories = null;

    private function repo(): CategoryRepository
    {
        if (!$this->categories instanceof CategoryRepository) {
            $this->categories = new CategoryRepository();
        }

        return $this->categories;
    }

    private function guardAdmin(): bool
    {
        if (!isset($_SESSION['admin_user']) || !is_array($_SESSION['admin_user'])) {
            header('Location: /backoffice/login', true, 302);
            return false;
        }

        return true;
    }

    public function index(): void
    {
        if (!$this->guardAdmin()) {
            return;
        }

        try {
            $items = $this->repo()->findAll();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            view('back.categories', [
                'title' => 'Categories | Iran Info PHP',
                'adminUser' => $_SESSION['admin_user'],
                'categories' => $items,
                'flash' => is_array($flash) ? $flash : null,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            view('errors.500');
        }
    }

    public function save(): void
    {
        if (!$this->guardAdmin()) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['name'] ?? ''));
        $providedSlug = trim((string) ($_POST['slug'] ?? ''));
        $slug = to_slug($providedSlug !== '' ? $providedSlug : $name);
        $description = trim((string) ($_POST['description'] ?? ''));
        $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
        $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));

        if (strlen($name) < 2) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Nom invalide (min 2 caracteres).'];
            header('Location: /backoffice/categories', true, 302);
            return;
        }

        if ($slug === '' || strlen($slug) < 2) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Slug invalide.'];
            header('Location: /backoffice/categories', true, 302);
            return;
        }

        try {
            $existing = $this->repo()->findBySlug($slug);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Slug deja utilise.'];
                header('Location: /backoffice/categories', true, 302);
                return;
            }

            if ($id > 0) {
                $this->repo()->update($id, [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Categorie mise a jour.'];
            } else {
                $this->repo()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Categorie creee.'];
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la sauvegarde categorie.'];
        }

        header('Location: /backoffice/categories', true, 302);
    }

    public function delete(): void
    {
        if (!$this->guardAdmin()) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'ID categorie invalide.'];
            header('Location: /backoffice/categories', true, 302);
            return;
        }

        try {
            $this->repo()->deleteById($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Categorie supprimee.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Suppression impossible (categorie utilisee ?).'];
        }

        header('Location: /backoffice/categories', true, 302);
    }
}
