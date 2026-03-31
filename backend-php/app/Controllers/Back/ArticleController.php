<?php

declare(strict_types=1);

namespace App\Controllers\Back;

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use Throwable;

final class ArticleController
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
            $items = $this->articleRepo()->findAllForAdmin();
            $cats = $this->categoryRepo()->findAll();

            $editArticle = null;
            $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
            if ($editId > 0) {
                $editArticle = $this->articleRepo()->findById($editId);
            }

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            view('back.articles', [
                'title' => 'Articles | Iran Info PHP',
                'adminUser' => $_SESSION['admin_user'],
                'articles' => $items,
                'categories' => $cats,
                'editArticle' => $editArticle,
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
        $existing = $id > 0 ? $this->articleRepo()->findById($id) : null;

        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $inputSlug = trim((string) ($_POST['slug'] ?? ''));
        $slug = to_slug($inputSlug !== '' ? $inputSlug : $title);

        if (strlen($title) < 5 || strlen($content) < 30 || strlen($slug) < 2) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Titre, contenu ou slug invalide.'];
            header('Location: /backoffice/articles' . ($id > 0 ? ('?edit=' . $id) : ''), true, 302);
            return;
        }

        if ($this->articleRepo()->slugExists($slug, $id > 0 ? $id : null)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Slug article deja utilise.'];
            header('Location: /backoffice/articles' . ($id > 0 ? ('?edit=' . $id) : ''), true, 302);
            return;
        }

        $coverPath = $existing['coverImagePath'] ?? '';
        $coverUploaded = $this->saveUploadedImage('cover_image');
        if ($coverUploaded !== null) {
            if (is_string($coverPath) && $coverPath !== '') {
                $this->removeLocalImage($coverPath);
            }
            $coverPath = $coverUploaded;
        }

        if (!is_string($coverPath) || $coverPath === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image de couverture obligatoire.'];
            header('Location: /backoffice/articles' . ($id > 0 ? ('?edit=' . $id) : ''), true, 302);
            return;
        }

        $gallery = is_array($existing['galleryImages'] ?? null) ? $existing['galleryImages'] : [];
        $newGallery = $this->saveUploadedGallery('gallery_images');
        if (count($newGallery) > 0) {
            $gallery = array_merge($gallery, $newGallery);
        }

        $categoryIds = [];
        $rawCats = $_POST['category_ids'] ?? [];
        if (is_array($rawCats)) {
            foreach ($rawCats as $catId) {
                $val = (int) $catId;
                if ($val > 0) {
                    $categoryIds[] = $val;
                }
            }
        }

        $metaTitle = $this->metaTitle((string) ($_POST['meta_title'] ?? ''), $title);
        $metaDescription = $this->metaDescription((string) ($_POST['meta_description'] ?? ''), $content);
        $metaKeywords = trim((string) ($_POST['meta_keywords'] ?? 'iran,actualites,international'));
        $coverAlt = trim((string) ($_POST['cover_image_alt'] ?? ''));
        if ($coverAlt === '') {
            $coverAlt = $title;
        }

        $payload = [
            'user_id' => (int) ($_SESSION['admin_user']['id'] ?? 0),
            'title' => $title,
            'content' => $content,
            'cover_image_path' => $coverPath,
            'cover_image_alt' => $coverAlt,
            'gallery_images' => $gallery,
            'slug' => $slug,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'status' => 'published',
            'featured' => isset($_POST['featured']) ? 1 : 0,
        ];

        try {
            if ($id > 0) {
                $this->articleRepo()->update($id, $payload, $categoryIds);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article mis a jour.'];
            } else {
                $this->articleRepo()->create($payload, $categoryIds);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article cree.'];
            }
        } catch (Throwable $e) {
            error_log('Article save failed: ' . $e->getMessage());
            $message = 'Erreur lors de la sauvegarde article.';
            $debug = (getenv('APP_DEBUG') ?: '1') === '1';
            if ($debug) {
                $message .= ' Details: ' . $e->getMessage();
            }
            $_SESSION['flash'] = ['type' => 'error', 'message' => $message];
            header('Location: /backoffice/articles' . ($id > 0 ? ('?edit=' . $id) : ''), true, 302);
            return;
        }

        header('Location: /backoffice/articles', true, 302);
    }

    public function delete(): void
    {
        if (!$this->guardAdmin()) {
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'ID article invalide.'];
            header('Location: /backoffice/articles', true, 302);
            return;
        }

        try {
            $existing = $this->articleRepo()->findById($id);
            if ($existing !== null) {
                $cover = (string) ($existing['coverImagePath'] ?? '');
                if ($cover !== '') {
                    $this->removeLocalImage($cover);
                }

                $gallery = is_array($existing['galleryImages'] ?? null) ? $existing['galleryImages'] : [];
                foreach ($gallery as $image) {
                    if (is_array($image) && !empty($image['path'])) {
                        $this->removeLocalImage((string) $image['path']);
                    }
                }
            }

            $this->articleRepo()->deleteById($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Article supprime.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Suppression impossible.'];
        }

        header('Location: /backoffice/articles', true, 302);
    }

    private function metaTitle(string $provided, string $title): string
    {
        $base = trim($provided);
        if ($base === '') {
            $base = $title . ' | Iran Info actualites internationales';
        }

        if ($this->textLength($base) > 60) {
            $base = $this->textSlice($base, 60);
        }

        while ($this->textLength($base) < 50) {
            $base .= ' news';
        }

        if ($this->textLength($base) > 60) {
            $base = $this->textSlice($base, 60);
        }

        return trim($base);
    }

    private function metaDescription(string $provided, string $content): string
    {
        $base = trim($provided);
        if ($base === '') {
            $clean = trim(strip_tags($content));
            $base = $clean !== '' ? $clean : 'Analyse et actualites sur l Iran et l international.';
        }

        if ($this->textLength($base) > 160) {
            $base = $this->textSlice($base, 160);
        }

        while ($this->textLength($base) < 150) {
            $base .= ' Actualites, analyse et contexte editorial.';
            if ($this->textLength($base) > 160) {
                $base = $this->textSlice($base, 160);
                break;
            }
        }

        return trim($base);
    }

    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    private function textSlice(string $value, int $maxChars): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxChars, 'UTF-8');
        }

        return substr($value, 0, $maxChars);
    }

    private function saveUploadedImage(string $field): ?string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $targetDir = dirname(__DIR__, 3) . '/public/uploads/articles';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $basename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $targetDir . '/' . $basename;

        if (!move_uploaded_file($tmpName, $target)) {
            return null;
        }

        return '/uploads/articles/' . $basename;
    }

    /** @return array<int, array{path:string,alt:string}> */
    private function saveUploadedGallery(string $field): array
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return [];
        }

        $files = $_FILES[$field];
        $names = $files['name'] ?? [];
        $tmpNames = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];

        if (!is_array($names) || !is_array($tmpNames) || !is_array($errors)) {
            return [];
        }

        $saved = [];
        foreach ($names as $i => $name) {
            if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp = (string) ($tmpNames[$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $targetDir = dirname(__DIR__, 3) . '/public/uploads/articles';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            $basename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
            $target = $targetDir . '/' . $basename;
            if (!move_uploaded_file($tmp, $target)) {
                continue;
            }

            $saved[] = [
                'path' => '/uploads/articles/' . $basename,
                'alt' => pathinfo((string) $name, PATHINFO_FILENAME),
            ];
        }

        return $saved;
    }

    private function removeLocalImage(string $path): void
    {
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $fullPath = dirname(__DIR__, 3) . '/public' . $path;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
