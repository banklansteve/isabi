<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PricingDocsController extends Controller
{
    public function __invoke(): Response
    {
        $pricing = config('pricing');
        $packs = collect($pricing['credits']['packs'])->map(function (array $pack): array {
            return [
                ...$pack,
                'cost_per_credit' => (int) round($pack['price'] / max(1, $pack['credits'])),
            ];
        })->values()->all();

        $bestRate = collect($packs)->min('cost_per_credit') ?: 0;
        $annualPrice = (int) $pricing['annual']['price'];
        $breakevenCredits = $bestRate > 0 ? (int) ceil($annualPrice / $bestRate) : null;

        return Inertia::render('Internal/PricingDocs', [
            'pricing' => [
                ...$pricing,
                'credits' => [
                    ...$pricing['credits'],
                    'packs' => $packs,
                ],
            ],
            'math' => [
                'best_cost_per_credit' => $bestRate,
                'annual_breakeven_credits' => $breakevenCredits,
                'approx_extra_actions_per_month' => $breakevenCredits
                    ? (int) round($breakevenCredits / 12)
                    : null,
            ],
            'updatedAt' => now()->toDateString(),
        ]);
    }
}
