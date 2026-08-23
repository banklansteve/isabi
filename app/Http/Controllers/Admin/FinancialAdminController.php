<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TokenPurchase;
use App\Support\Admin\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialAdminController extends Controller
{
    public function index(Request $request, DashboardMetrics $metrics): Response
    {
        return Inertia::render('Admin/Financials/Index', [
            ...$metrics->financials(),
            'filters' => [
                'tab' => (string) $request->query('tab', 'revenue'),
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'isabi-revenue-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Reference', 'User', 'Email', 'Pack', 'Tokens', 'Amount (NGN)', 'Processor', 'Status']);

            TokenPurchase::query()
                ->with(['user:id,name,email'])
                ->orderBy('id')
                ->chunk(200, function ($purchases) use ($handle) {
                    foreach ($purchases as $purchase) {
                        fputcsv($handle, [
                            ($purchase->paid_at ?? $purchase->created_at)?->toDateTimeString(),
                            $purchase->reference,
                            $purchase->user?->name,
                            $purchase->user?->email,
                            $purchase->pack_name,
                            $purchase->tokens,
                            $purchase->price,
                            $purchase->processor,
                            $purchase->status,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
