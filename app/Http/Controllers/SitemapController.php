<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkLog;
use App\Support\ArtisanDirectory;
use App\Support\NigeriaLocations;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        $push = function (string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.6') use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $push(url('/'), now()->toAtomString(), 'weekly', '1.0');
        $push(route('how-it-works'), null, 'monthly', '0.8');
        $push(route('faq'), null, 'monthly', '0.7');
        $push(route('public.directory'), now()->toAtomString(), 'daily', '0.8');

        foreach (ArtisanDirectory::catalogTrades() as $trade) {
            $tradeSlug = ArtisanDirectory::tradeSlug($trade);
            $push(
                route('public.directory.trade', $tradeSlug),
                now()->toAtomString(),
                'weekly',
                '0.7',
            );

            foreach (NigeriaLocations::states() as $state) {
                $push(
                    route('public.directory.trade-state', [
                        $tradeSlug,
                        ArtisanDirectory::stateSlug($state),
                    ]),
                    now()->toAtomString(),
                    'weekly',
                    '0.6',
                );
            }
        }

        foreach (['about', 'contact', 'careers', 'terms', 'privacy', 'cookies', 'acceptable-use'] as $name) {
            $push(route($name), null, 'monthly', '0.4');
        }

        User::query()
            ->publiclyListed()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($push): void {
                foreach ($users as $user) {
                    $push(
                        route('public.profile', $user->slug),
                        $user->updated_at?->toAtomString(),
                        'weekly',
                        '0.8',
                    );
                }
            });

        WorkLog::query()
            ->whereNotNull('reference')
            ->whereHas('user', fn ($query) => $query->publiclyListed()->whereNotNull('slug')->where('slug', '!=', ''))
            ->where(function ($query) {
                $query->whereHas('review')->orWhereHas('media');
            })
            ->with(['user:id,slug'])
            ->select(['id', 'user_id', 'slug', 'reference', 'job_category', 'job_subcategory', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($logs) use ($push): void {
                foreach ($logs as $log) {
                    $params = $log->publicRouteParams();
                    if (! $params) {
                        continue;
                    }

                    $push(
                        route('public.job', $params),
                        $log->updated_at?->toAtomString(),
                        'weekly',
                        '0.6',
                    );
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";
            if ($url['lastmod']) {
                $xml .= '    <lastmod>'.e($url['lastmod'])."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.e($url['changefreq'])."</changefreq>\n";
            $xml .= '    <priority>'.e($url['priority'])."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
