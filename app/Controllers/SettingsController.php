<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use RuntimeException;

final class SettingsController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $this->render('admin/settings/index', [
            'pageTitle' => 'Einstellungen',
            'documentStatus' => $this->documentProcessor()->status(),
            'updateStatus' => $this->updater()->status(),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();

        $values = [
            'site_title' => trim((string) $request->input('site_title', 'Speiseplan')),
            'site_subtitle' => trim((string) $request->input('site_subtitle', '')),
            'banner_enabled' => !empty($request->input('banner_enabled')) ? '1' : '0',
            'banner_style' => (string) $request->input('banner_style', 'info'),
            'banner_text' => trim((string) $request->input('banner_text', '')),
            'theme_mode' => (string) $request->input('theme_mode', 'light'),
            'accent_color' => trim((string) $request->input('accent_color', '#0f766e')),
            'github_url' => trim((string) $request->input('github_url', '')),
            'imprint_text' => trim((string) $request->input('imprint_text', '')),
            'privacy_text' => trim((string) $request->input('privacy_text', '')),
        ];

        try {
            $this->validate($values);
            $this->settings()->save($values, (int) $actor['id']);

            $brandingChanged = false;
            $removeAppIcon = !empty($request->input('remove_app_icon'));
            $uploadedAppIcon = $request->file('app_icon') ?? null;
            $hasNewIcon = is_array($uploadedAppIcon) && (int) ($uploadedAppIcon['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($removeAppIcon) {
                $brandingChanged = $this->branding()->removeIcon((int) $actor['id']) || $brandingChanged;
            }

            if ($hasNewIcon) {
                $this->branding()->replaceIcon($uploadedAppIcon, (int) $actor['id']);
                $brandingChanged = true;
            }

            $this->logger()->log('settings_updated', 'Einstellungen aktualisiert.', [], (int) $actor['id'], 'info', $request->ip());
            $message = 'Einstellungen wurden gespeichert.';

            if ($brandingChanged) {
                $message .= ' Branding wurde aktualisiert.';
            }

            $this->flash()->success($message);
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/settings');
    }

    private function validate(array $values): void
    {
        if ($values['site_title'] === '' || mb_strlen($values['site_title']) > 120) {
            throw new RuntimeException('Der Seitentitel muss zwischen 1 und 120 Zeichen lang sein.');
        }

        if (mb_strlen($values['site_subtitle']) > 120) {
            throw new RuntimeException('Die Unterzeile in der Navigation darf maximal 120 Zeichen lang sein.');
        }

        if (!in_array($values['banner_style'], ['info', 'warn', 'important'], true)) {
            throw new RuntimeException('Ungültiger Banner-Stil.');
        }

        if (!in_array($values['theme_mode'], ['light', 'dark'], true)) {
            throw new RuntimeException('Ungültiger Theme-Modus.');
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $values['accent_color'])) {
            throw new RuntimeException('Bitte eine gültige Hex-Akzentfarbe angeben, z. B. #0f766e.');
        }

        if ($values['github_url'] !== '' && filter_var($values['github_url'], FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Bitte eine gültige GitHub-URL angeben.');
        }
    }
}
