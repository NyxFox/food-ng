<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use RuntimeException;

final class UpdateController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $this->render('admin/update/index', [
            'pageTitle' => 'Updates',
            'updateStatus' => $this->updater()->status(),
            'updateLogs' => $this->logger()->latest(['action' => 'update_run'], 10),
        ]);
    }

    public function run(Request $request, array $params = []): void
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();

        try {
            $result = $this->updater()->run((int) $actor['id'], $request->ip());
            $message = 'Update erfolgreich ausgeführt.';

            if ($result['version_changed']) {
                $message .= ' Version v' . $result['before_version'] . ' auf v' . $result['after_version'] . ' aktualisiert.';
            } else {
                $message .= ' Aktuelle Version bleibt v' . $result['after_version'] . '.';
            }

            $this->flash()->success($message);

            if ($result['output'] !== '') {
                $this->flash()->info('Git-Ausgabe: ' . mb_substr((string) $result['output'], 0, 800));
            }
        } catch (RuntimeException $exception) {
            $this->flash()->error($exception->getMessage());
        }

        $this->redirect('admin/update');
    }
}
