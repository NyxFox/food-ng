<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;

final class AuthController extends BaseController
{
    public function loginForm(Request $request, array $params = []): void
    {
        if ($this->auth()->check()) {
            $this->redirect('admin');
        }

        $this->render('auth/login', [
            'pageTitle' => 'Login',
        ]);
    }

    public function login(Request $request, array $params = []): void
    {
        $this->requireValidCsrf();

        $username = (string) $request->input('username', '');
        $this->flash()->setOldInput(['username' => $username]);

        if ($this->auth()->attempt($username, (string) $request->input('password', ''), $request->ip())) {
            $this->flash()->success('Anmeldung erfolgreich.');
            $this->redirect('admin');
        }

        $this->flash()->error('Benutzername oder Passwort sind ungültig.');
        $this->redirect('login');
    }

    public function logout(Request $request, array $params = []): void
    {
        $this->requireValidCsrf();
        $this->auth()->logout($request->ip());
        $this->flash()->success('Abmeldung erfolgreich.');
        $this->redirect('');
    }
}
