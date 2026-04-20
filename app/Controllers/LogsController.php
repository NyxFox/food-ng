<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;

final class LogsController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $filters = [
            'level' => trim((string) $request->query('level', '')),
            'action' => trim((string) $request->query('action', '')),
            'query' => trim((string) $request->query('query', '')),
        ];

        $this->render('admin/logs/index', [
            'pageTitle' => 'Logs',
            'filters' => $filters,
            'logs' => $this->logger()->latest($filters, 250),
        ]);
    }
}
