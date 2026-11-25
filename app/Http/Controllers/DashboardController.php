<?php

namespace App\Http\Controllers;

use App\Services\ItemService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ItemService $itemService): Response
    {
        return Inertia::render('dashboard', [
            'summary' => $itemService->dashboardSummary(),
        ]);
    }
}
