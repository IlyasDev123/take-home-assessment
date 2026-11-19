<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\ValueObjects\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreditsController extends Controller
{
    public function __construct(private readonly CreditService $creditService) {}

    public function index(Request $request): Response
    {
        $user    = $request->user();
        $balance = $this->creditService->getBalance($user);

        $paginator    = CreditTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $transactions = [
            'data'  => $paginator->map(fn (CreditTransaction $t) => [
                'id'            => $t->id,
                'type'          => $t->type,
                'amount'        => $t->amount,
                'description'   => $t->description,
                'balance_after' => $t->balance_after,
                'generation_id' => $t->generation_id,
                'created_at'    => $t->created_at->toIso8601String(),
            ])->values()->all(),
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'from'         => $paginator->firstItem(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
            ],
        ];

        return Inertia::render('Credits/Index', [
            'balance'      => $balance->toDecimalString(6),
            'transactions' => $transactions,
        ]);
    }

    public function buy(Request $request): Response
    {
        $balance = $this->creditService->getBalance($request->user());

        return Inertia::render('Credits/Buy', [
            'balance'  => $balance->toDecimalString(6),
            'packages' => [
                ['amount' => '5.000000',  'label' => '$5',  'description' => 'Starter'],
                ['amount' => '10.000000', 'label' => '$10', 'description' => 'Basic'],
                ['amount' => '25.000000', 'label' => '$25', 'description' => 'Pro'],
                ['amount' => '50.000000', 'label' => '$50', 'description' => 'Studio'],
            ],
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000'],
        ]);

        $amount = Money::fromDecimalString(
            number_format((float) $request->input('amount'), 6, '.', '')
        );

        $this->creditService->topUp(
            $request->user(),
            $amount,
            'Credit purchase — $' . number_format((float) $request->input('amount'), 2),
        );

        return redirect()
            ->route('credits.index')
            ->with('success', sprintf('$%.2f added to your balance!', $request->input('amount')));
    }
}
