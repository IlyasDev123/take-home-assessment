<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Models\Generation;
use App\Http\Resources\GenerationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $stats = Generation::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as total_generations,
                COUNT(CASE WHEN status = ? THEN 1 END) as successful_generations,
                COALESCE(SUM(CASE WHEN actual_user_cost IS NOT NULL THEN actual_user_cost ELSE estimated_user_cost END), 0) as total_spent
            ", [GenerationStatus::Succeeded->value])
            ->first();

        $recentGenerations = Generation::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalGenerations'      => (int) $stats->total_generations,
                'successfulGenerations' => (int) $stats->successful_generations,
                'totalSpent'            => number_format((float) $stats->total_spent, 6),
            ],
            'recentGenerations' => $recentGenerations->map(
                fn (Generation $g) => (new GenerationResource($g))->resolve()
            ),
        ]);
    }
}
