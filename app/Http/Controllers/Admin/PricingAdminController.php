<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePricingRequest;
use App\Models\PricingVersion;
use App\Support\Admin\AdminAudit;
use App\Support\Staff\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = (string) $request->query('tab', 'live');

        $history = PricingVersion::query()
            ->with(['createdBy:id,name,email'])
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (PricingVersion $version) => [
                'id' => $version->id,
                'version' => $version->version,
                'summary' => $version->summary,
                'created_at' => $version->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'author' => $version->createdBy?->name,
            ]);

        return Inertia::render('Admin/Pricing/Index', [
            'pricing' => config('pricing'),
            'history' => $history,
            'filters' => ['tab' => $tab],
        ]);
    }

    public function update(UpdatePricingRequest $request, AppSettingsService $settings): RedirectResponse
    {
        $data = $request->validated();

        $before = config('pricing');

        $map = [
            'pricing.free.monthly_review_links' => ['value' => $data['free']['monthly_review_links'], 'config' => 'pricing.free.monthly_review_links', 'type' => 'integer', 'group' => 'pricing', 'label' => 'Free monthly review links'],
            'pricing.annual.price' => ['value' => $data['annual']['price'], 'config' => 'pricing.annual.price', 'type' => 'integer', 'group' => 'pricing', 'label' => 'Annual price'],
            'pricing.referral.credits_reward' => ['value' => $data['referral']['credits_reward'], 'config' => 'pricing.referral.credits_reward', 'type' => 'integer', 'group' => 'pricing', 'label' => 'Referral reward'],
            'pricing.credits.actions.review_link' => ['value' => $data['credits']['actions']['review_link'], 'config' => 'pricing.credits.actions.review_link', 'type' => 'integer', 'group' => 'pricing', 'label' => 'Review link cost'],
            'pricing.credits.actions.qr_download' => ['value' => $data['credits']['actions']['qr_download'], 'config' => 'pricing.credits.actions.qr_download', 'type' => 'integer', 'group' => 'pricing', 'label' => 'QR download cost'],
            'pricing.credits.actions.vanity_slug' => ['value' => $data['credits']['actions']['vanity_slug'], 'config' => 'pricing.credits.actions.vanity_slug', 'type' => 'integer', 'group' => 'pricing', 'label' => 'Vanity slug cost'],
            'pricing.credits.packs' => ['value' => $data['credits']['packs'], 'config' => 'pricing.credits.packs', 'type' => 'json', 'group' => 'pricing', 'label' => 'Credit packs'],
        ];

        foreach ($map as $key => $row) {
            $settings->put($key, $row['value'], $request->user(), [
                'type' => $row['type'],
                'group' => $row['group'],
                'label' => $row['label'],
                'config' => $row['config'],
            ]);
        }

        $settings->forgetCache();
        $settings->applyOverrides();

        $after = config('pricing');
        $version = (int) PricingVersion::query()->max('version') + 1;

        PricingVersion::query()->create([
            'version' => $version,
            'payload' => $after,
            'summary' => $this->summarize($before, $after),
            'created_by_user_id' => $request->user()->id,
        ]);

        AdminAudit::record(
            'pricing.updated',
            "{$request->user()->name} published pricing version {$version}.",
            null,
            $before,
            $after,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Pricing published',
            'message' => 'Version '.$version.' is now live and written to the audit log.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function summarize(array $before, array $after): string
    {
        $changes = [];

        if (($before['annual']['price'] ?? null) !== ($after['annual']['price'] ?? null)) {
            $changes[] = 'annual '.$before['annual']['price'].' → '.$after['annual']['price'];
        }

        if (($before['free']['monthly_review_links'] ?? null) !== ($after['free']['monthly_review_links'] ?? null)) {
            $changes[] = 'free links '.$before['free']['monthly_review_links'].' → '.$after['free']['monthly_review_links'];
        }

        if (($before['referral']['credits_reward'] ?? null) !== ($after['referral']['credits_reward'] ?? null)) {
            $changes[] = 'referral reward '.$before['referral']['credits_reward'].' → '.$after['referral']['credits_reward'];
        }

        return $changes !== [] ? implode(', ', $changes) : 'Pricing updated';
    }
}
