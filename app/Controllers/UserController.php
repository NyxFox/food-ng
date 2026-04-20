<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\HttpException;
use App\Core\Request;
use RuntimeException;

final class UserController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $this->render('admin/users/index', [
            'pageTitle' => 'Benutzerverwaltung',
            'users' => $this->users()->listAll(),
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $this->render('admin/users/form', [
            'pageTitle' => 'Benutzer anlegen',
            'user' => null,
            'formAction' => url('admin/users/create'),
            'isCreate' => true,
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();

        $oldInput = [
            'username' => (string) $request->input('username', ''),
            'display_name' => (string) $request->input('display_name', ''),
            'role' => (string) $request->input('role', 'editor'),
        ];
        $this->flash()->setOldInput($oldInput);

        try {
            $this->users()->create($request->all(), (int) $actor['id'], $request->ip());
            $this->flash()->success('Benutzer wurde angelegt.');
            $this->redirect('admin/users');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
            $this->redirect('admin/users/create');
        }
    }

    public function edit(Request $request, array $params = []): void
    {
        $this->requireAdmin();
        $user = $this->findUserOrFail((int) ($params['id'] ?? 0));

        $this->render('admin/users/form', [
            'pageTitle' => 'Benutzer bearbeiten',
            'user' => $user,
            'formAction' => url('admin/users/' . $user['id'] . '/edit'),
            'isCreate' => false,
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();
        $userId = (int) ($params['id'] ?? 0);

        $oldInput = [
            'username' => (string) $request->input('username', ''),
            'display_name' => (string) $request->input('display_name', ''),
            'role' => (string) $request->input('role', 'editor'),
            'is_active' => !empty($request->input('is_active')) ? '1' : '0',
        ];
        $this->flash()->setOldInput($oldInput);

        try {
            $this->users()->update($userId, $request->all(), (int) $actor['id'], $request->ip());
            $this->flash()->success('Benutzer wurde aktualisiert.');
            $this->redirect('admin/users/' . $userId . '/edit');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
            $this->redirect('admin/users/' . $userId . '/edit');
        }
    }

    public function resetPassword(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();
        $userId = (int) ($params['id'] ?? 0);

        try {
            $this->users()->resetPassword($userId, (string) $request->input('password', ''), (int) $actor['id'], $request->ip());
            $this->flash()->success('Passwort wurde neu gesetzt.');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/users/' . $userId . '/edit');
    }

    public function toggle(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();

        try {
            $this->users()->toggleActive((int) ($params['id'] ?? 0), (int) $actor['id'], $request->ip());
            $this->flash()->success('Benutzerstatus wurde umgeschaltet.');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/users');
    }

    private function findUserOrFail(int $id): array
    {
        $user = $this->users()->find($id);

        if ($user === null) {
            throw new HttpException(404, 'Der Benutzer wurde nicht gefunden.');
        }

        return $user;
    }
}
