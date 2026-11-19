<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Billing\Services\CreditService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly CreditService $creditService) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user    = $request->user();
        $balance = null;

        if ($user !== null) {
            // Lazily create credit record if it doesn't exist yet
            $this->creditService->ensureCreditRecord($user);
            $balance = $this->creditService->getBalance($user)->toDecimalString(6);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only('id', 'name', 'email', 'is_admin'),
            ],
            'credits' => [
                'balance' => $balance,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
