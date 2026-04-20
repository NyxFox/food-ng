<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\HttpException;
use App\Core\Request;
use RuntimeException;

final class MealPlanController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireLogin();

        $this->render('admin/meal-plans/index', [
            'pageTitle' => 'Speisepläne',
            'plans' => $this->mealPlans()->listAll(),
            'documentStatus' => $this->documentProcessor()->status(),
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->requireLogin();

        $this->render('admin/meal-plans/upload', [
            'pageTitle' => 'Speiseplan hochladen',
            'documentStatus' => $this->documentProcessor()->status(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireLogin();
        $this->requireValidCsrf();

        $title = (string) $request->input('title', '');
        $this->flash()->setOldInput(['title' => $title]);

        try {
            $planId = $this->mealPlans()->createFromUpload(
                $title,
                $request->file('normal') ?? [],
                $request->file('vegetarian') ?? [],
                (int) $user['id'],
                $request->ip()
            );
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
            $this->redirect('admin/meal-plans/upload');
        }

        $this->flash()->success('Speiseplan wurde als Entwurf gespeichert und kann jetzt geprüft werden.');
        $this->redirect('admin/meal-plans/' . $planId . '/preview');
    }

    public function show(Request $request, array $params = []): void
    {
        $this->requireLogin();
        $plan = $this->findPlanOrFail((int) ($params['id'] ?? 0));

        $this->render('admin/meal-plans/show', [
            'pageTitle' => $plan['title'],
            'plan' => $plan,
        ]);
    }

    public function preview(Request $request, array $params = []): void
    {
        $this->requireLogin();
        $plan = $this->findPlanOrFail((int) ($params['id'] ?? 0));

        $this->render('admin/meal-plans/preview', [
            'pageTitle' => 'Prüfansicht',
            'plan' => $plan,
        ]);
    }

    public function activate(Request $request, array $params = []): void
    {
        $user = $this->requireLogin();
        $this->requireValidCsrf();

        try {
            $this->mealPlans()->activate((int) ($params['id'] ?? 0), (int) $user['id'], $request->ip());
            $this->flash()->success('Speiseplan wurde aktiviert.');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/meal-plans');
    }

    public function archive(Request $request, array $params = []): void
    {
        $user = $this->requireLogin();
        $this->requireValidCsrf();

        try {
            $this->mealPlans()->archive((int) ($params['id'] ?? 0), (int) $user['id'], $request->ip());
            $this->flash()->success('Speiseplan wurde archiviert.');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/meal-plans');
    }

    public function delete(Request $request, array $params = []): void
    {
        $user = $this->requireLogin();
        $this->requireValidCsrf();

        try {
            $this->mealPlans()->delete((int) ($params['id'] ?? 0), (int) $user['id'], $request->ip());
            $this->flash()->success('Speiseplan wurde gelöscht.');
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/meal-plans');
    }

    public function download(Request $request, array $params = []): void
    {
        $this->requireLogin();
        $plan = $this->findPlanOrFail((int) ($params['id'] ?? 0));
        $absolutePath = $this->mealPlans()->absoluteStoragePath((string) $plan['merged_pdf_path']);

        if (!is_file($absolutePath)) {
            throw new HttpException(404, 'Die PDF-Datei ist nicht vorhanden.');
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Content-Disposition: attachment; filename="' . rawurlencode($this->mealPlans()->downloadFilename($plan)) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($absolutePath);
        exit;
    }

    private function findPlanOrFail(int $id): array
    {
        $plan = $this->mealPlans()->find($id);

        if ($plan === null) {
            throw new HttpException(404, 'Der angeforderte Speiseplan wurde nicht gefunden.');
        }

        return $plan;
    }
}
