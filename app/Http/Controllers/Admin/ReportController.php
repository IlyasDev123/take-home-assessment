<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Pixelforge\Client\PixelforgeClient;
use App\Domain\Generation\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function __construct(private readonly PixelforgeClient $client) {}

    public function __invoke(Request $request): Response
    {
        // ── Daily breakdown (last 30 days) ────────────────────────────────
        $daily = DB::table('generations')
            ->select([
                DB::raw("DATE(completed_at) as date"),
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded"),
                DB::raw("COALESCE(SUM(actual_provider_cost), 0) as provider_cost"),
                DB::raw("COALESCE(SUM(actual_user_cost), 0) as user_cost"),
            ])
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(completed_at)')
            ->orderByDesc('date')
            ->get()
            ->map(fn ($row) => [
                'date'          => $row->date,
                'total'         => (int) $row->total,
                'succeeded'     => (int) $row->succeeded,
                'provider_cost' => number_format((float) $row->provider_cost, 6, '.', ''),
                'user_cost'     => number_format((float) $row->user_cost, 6, '.', ''),
                'profit'        => number_format(
                    (float) $row->user_cost - (float) $row->provider_cost,
                    6, '.', ''
                ),
            ]);

        // ── Per-user breakdown ────────────────────────────────────────────
        $users = DB::table('generations')
            ->join('users', 'users.id', '=', 'generations.user_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN generations.status = 'succeeded' THEN 1 ELSE 0 END) as succeeded"),
                DB::raw("COALESCE(SUM(actual_provider_cost), 0) as provider_cost"),
                DB::raw("COALESCE(SUM(actual_user_cost), 0) as user_cost"),
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('user_cost')
            ->get()
            ->map(fn ($row) => [
                'id'            => $row->id,
                'name'          => $row->name,
                'email'         => $row->email,
                'total'         => (int) $row->total,
                'succeeded'     => (int) $row->succeeded,
                'provider_cost' => number_format((float) $row->provider_cost, 6, '.', ''),
                'user_cost'     => number_format((float) $row->user_cost, 6, '.', ''),
                'profit'        => number_format(
                    (float) $row->user_cost - (float) $row->provider_cost,
                    6, '.', ''
                ),
            ]);

        // ── Totals ────────────────────────────────────────────────────────
        $totals = DB::table('generations')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded,
                COALESCE(SUM(actual_provider_cost), 0) as provider_cost,
                COALESCE(SUM(actual_user_cost), 0) as user_cost
            ")
            ->first();

        // ── Pixelforge usage API ──────────────────────────────────────────
        $pixelforgeUsage = null;
        try {
            $pixelforgeUsage = $this->client->getUsage(
                now()->subDays(30)->toDateString(),
                now()->toDateString(),
            );
        } catch (\Throwable $e) {
            Log::warning('Admin report: could not fetch Pixelforge usage.', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Admin/Report', [
            'daily'          => $daily,
            'users'          => $users,
            'totals'         => [
                'total'         => (int) $totals->total,
                'succeeded'     => (int) $totals->succeeded,
                'provider_cost' => number_format((float) $totals->provider_cost, 6, '.', ''),
                'user_cost'     => number_format((float) $totals->user_cost, 6, '.', ''),
                'profit'        => number_format(
                    (float) $totals->user_cost - (float) $totals->provider_cost,
                    6, '.', ''
                ),
            ],
            'pixelforgeUsage' => $pixelforgeUsage,
        ]);
    }
}
