<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportCannedReply;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Ops/KnowledgeBase', [
            'gaps' => $this->faqGaps(),
            'topics' => $this->topicVolume(),
            'templates' => $this->cannedTemplates(),
            'can_edit_templates' => (bool) request()->user()?->canDo('admin.support.manage'),
        ]);
    }

    /**
     * Recurring support topics that have no canned response yet — the FAQ gaps.
     *
     * @return list<array<string, mixed>>
     */
    private function faqGaps(): array
    {
        if (! Schema::hasTable('support_tickets')) {
            return [];
        }

        $since = now()->subDays(90);

        $ticketTopics = SupportTicket::query()
            ->whereNotNull('topic_key')
            ->where('topic_key', '!=', '')
            ->where('created_at', '>=', $since)
            ->selectRaw('topic_key, COUNT(*) as total')
            ->groupBy('topic_key')
            ->pluck('total', 'topic_key');

        $covered = Schema::hasTable('support_canned_replies')
            ? SupportCannedReply::query()
                ->whereNotNull('topic_key')
                ->where('topic_key', '!=', '')
                ->pluck('topic_key')
                ->map(fn ($key) => (string) $key)
                ->unique()
                ->flip()
            : collect();

        return $ticketTopics
            ->map(fn ($total, $topic) => [
                'topic' => (string) $topic,
                'label' => Str::headline((string) $topic),
                'total' => (int) $total,
                'covered' => $covered->has((string) $topic),
            ])
            ->filter(fn (array $row) => ! $row['covered'] && $row['total'] >= 3)
            ->sortByDesc('total')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * All recurring topics over the last 90 days (coverage view).
     *
     * @return list<array<string, mixed>>
     */
    private function topicVolume(): array
    {
        if (! Schema::hasTable('support_tickets')) {
            return [];
        }

        $since = now()->subDays(90);

        return SupportTicket::query()
            ->whereNotNull('topic_key')
            ->where('topic_key', '!=', '')
            ->where('created_at', '>=', $since)
            ->selectRaw('topic_key, COUNT(*) as total')
            ->groupBy('topic_key')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'topic' => (string) $row->topic_key,
                'label' => Str::headline((string) $row->topic_key),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cannedTemplates(): array
    {
        if (! Schema::hasTable('support_canned_replies')) {
            return [];
        }

        return SupportCannedReply::query()
            ->orderByDesc('is_system')
            ->orderBy('title')
            ->limit(200)
            ->get()
            ->map(fn (SupportCannedReply $reply) => [
                'id' => $reply->id,
                'title' => $reply->title,
                'body' => Str::limit((string) $reply->body, 160),
                'topic_key' => $reply->topic_key,
                'is_system' => (bool) $reply->is_system,
            ])
            ->values()
            ->all();
    }
}
