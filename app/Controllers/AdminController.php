<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;

final class AdminController extends BaseController
{
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireLogin();

        $this->render('admin/dashboard', [
            'pageTitle' => 'Admin',
            'summary' => $this->mealPlans()->summary(),
            'userCount' => $this->users()->count(),
            'documentStatus' => $this->documentProcessor()->status(),
            'updateStatus' => $this->updater()->status(),
            'recentLogs' => $this->logger()->latest([], 8),
            'statsSummary' => $this->stats()->summary(),
        ]);
    }
}
