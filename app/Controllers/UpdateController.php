<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use Throwable;

final class UpdateController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $this->render('admin/update/index', [
            'pageTitle' => 'Updater',
            'updateStatus' => $this->updater()->status(true),
            'updateLogs' => $this->logger()->latest(['action' => 'update_run'], 10),
        ]);
    }

    public function stream(Request $request, array $params = []): never
    {
        $actor = $this->requireAdmin();
        $this->requireValidCsrf();
        session_write_close();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ignore_user_abort(true);
        set_time_limit(0);

        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Accel-Buffering: no');

        $send = static function (array $payload): void {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            flush();
        };

        try {
            $this->updater()->runStreaming($send, (int) $actor['id'], $request->ip());
        } catch (Throwable $exception) {
            $send([
                'type' => 'result',
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        exit;
    }
}
