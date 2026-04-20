<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\HttpException;
use App\Core\Request;

final class HomeController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->stats()->record(session_id(), '/');

        $activePlan = $this->mealPlans()->active();

        $this->render('home', [
            'pageTitle' => 'Aktueller Speiseplan',
            'activePlan' => $activePlan,
        ]);
    }

    public function imprint(Request $request, array $params = []): void
    {
        $this->render('legal', [
            'pageTitle' => 'Impressum',
            'heading' => 'Impressum',
            'content' => $this->settings()->get('imprint_text', ''),
        ]);
    }

    public function privacy(Request $request, array $params = []): void
    {
        $this->render('legal', [
            'pageTitle' => 'Datenschutz',
            'heading' => 'Datenschutz',
            'content' => $this->settings()->get('privacy_text', ''),
        ]);
    }

    public function appIcon(Request $request, array $params = []): void
    {
        $icon = $this->branding()->currentIcon();

        if ($icon === null) {
            throw new HttpException(404, 'Kein App-Icon hinterlegt.');
        }

        $this->streamBinaryFile(
            $icon['absolute_path'],
            $icon['mime'],
            'app-icon.' . $icon['extension']
        );
    }

    public function pdf(Request $request, array $params = []): void
    {
        $plan = $this->mealPlans()->find((int) ($params['id'] ?? 0));

        if ($plan === null) {
            throw new HttpException(404, 'Der angeforderte Speiseplan wurde nicht gefunden.');
        }

        if ((int) $plan['is_active'] !== 1 && !$this->auth()->check()) {
            throw new HttpException(404, 'Der angeforderte Speiseplan wurde nicht gefunden.');
        }

        $this->stats()->record(session_id(), '/meal-plans/' . $plan['id'] . '/pdf');

        $this->streamPdfFile(
            $this->mealPlans()->absoluteStoragePath((string) $plan['merged_pdf_path']),
            $this->mealPlans()->downloadFilename($plan),
            false
        );
    }

    private function streamBinaryFile(string $absolutePath, string $contentType, string $filename): never
    {
        if (!is_file($absolutePath)) {
            throw new HttpException(404, 'Die angeforderte Datei ist nicht vorhanden.');
        }

        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($absolutePath);
        exit;
    }

    private function streamPdfFile(string $absolutePath, string $filename, bool $download): never
    {
        if (!is_file($absolutePath)) {
            throw new HttpException(404, 'Die PDF-Datei ist nicht vorhanden.');
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . rawurlencode($filename) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($absolutePath);
        exit;
    }
}
