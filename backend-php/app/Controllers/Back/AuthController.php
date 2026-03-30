<?php

declare(strict_types=1);

namespace App\Controllers\Back;

use App\Repositories\UserRepository;
use Throwable;

final class AuthController
{
    private ?UserRepository $users = null;

    private function userRepo(): UserRepository
    {
        if (!$this->users instanceof UserRepository) {
            $this->users = new UserRepository();
        }
        return $this->users;
    }

    public function loginForm(): void
    {
        if (isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])) {
            header('Location: /backoffice/dashboard', true, 302);
            return;
        }

        $error = '';
        if (isset($_SESSION['auth_error'])) {
            $error = (string) $_SESSION['auth_error'];
            unset($_SESSION['auth_error']);
        }

        view('back.login', [
            'title' => 'Connexion backoffice | Iran Info PHP',
            'error' => $error,
        ]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['auth_error'] = 'Email et mot de passe requis.';
            header('Location: /backoffice/login', true, 302);
            return;
        }

        try {
            $user = $this->userRepo()->findByEmail($email);
            if ($user === null) {
                $_SESSION['auth_error'] = 'Identifiants invalides.';
                header('Location: /backoffice/login', true, 302);
                return;
            }

            $hash = (string) ($user['passwordHash'] ?? '');
            $isValid = $hash !== '' && password_verify($password, $hash);

            if (!$isValid) {
                $_SESSION['auth_error'] = 'Identifiants invalides.';
                header('Location: /backoffice/login', true, 302);
                return;
            }

            if (($user['role'] ?? '') !== 'admin') {
                $_SESSION['auth_error'] = 'Acces refuse.';
                header('Location: /backoffice/login', true, 302);
                return;
            }

            $_SESSION['admin_user'] = [
                'id' => (int) $user['id'],
                'username' => (string) $user['username'],
                'email' => (string) $user['email'],
                'role' => (string) $user['role'],
            ];

            header('Location: /backoffice/dashboard', true, 302);
        } catch (Throwable $e) {
            $_SESSION['auth_error'] = 'Erreur interne de connexion.';
            header('Location: /backoffice/login', true, 302);
        }
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user']);
        $_SESSION['auth_error'] = 'Vous etes deconnecte.';
        header('Location: /backoffice/login', true, 302);
    }
}
