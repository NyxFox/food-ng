<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;

final class LogsController extends BaseController
{
    private const PER_PAGE = 10;

    public function index(Request $request, array $params = []): void
    {
        $this->requireAdmin();

        $filters = [
            'level' => trim((string) $request->query('level', '')),
            'action' => trim((string) $request->query('action', '')),
            'query' => trim((string) $request->query('query', '')),
        ];
        $page = max(1, (int) $request->query('page', 1));
        $totalLogs = $this->logger()->countMatching($filters);
        $totalPages = max(1, (int) ceil($totalLogs / self::PER_PAGE));
        $page = min($page, $totalPages);
        $paginationQuery = array_filter(
            $filters,
            static fn (string $value): bool => $value !== ''
        );

        $this->render('admin/logs/index', [
            'pageTitle' => 'Logs',
            'currentPage' => $page,
            'filters' => $filters,
            'logs' => $this->logger()->page($filters, $page, self::PER_PAGE),
            'paginationQuery' => $paginationQuery,
            'perPage' => self::PER_PAGE,
            'totalLogs' => $totalLogs,
            'totalPages' => $totalPages,
        ]);
    }
}
