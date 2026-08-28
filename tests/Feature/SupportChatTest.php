<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\StaffRole;
use App\Models\SupportCannedReply;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Support\SupportChat\SupportPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_artisan_starts_one_conversation_from_a_starter(): void
    {
        $this->travelTo(Carbon::parse('2026-08-23 22:00:00', 'Africa/Lagos'));
        $artisan = User::factory()->regularUser()->create();

        $this->actingAs($artisan)
            ->post(route('help.chat.send'), [
                'body' => 'I have a question about credits or billing.',
                'topic_key' => 'billing',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('state', 'offline')
            ->assertJsonMissingPath('tags')
            ->assertJsonMissingPath('notes');

        $this->assertDatabaseCount('support_tickets', 1);
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $artisan->id,
            'topic_key' => 'billing',
            'status' => SupportTicket::STATUS_NEW,
        ]);
    }

    public function test_rapid_messages_stay_on_the_same_conversation(): void
    {
        $artisan = User::factory()->regularUser()->create();

        $this->actingAs($artisan)->post(route('help.chat.send'), ['body' => 'First']);
        $this->actingAs($artisan)->post(route('help.chat.send'), ['body' => 'Second']);
        $this->actingAs($artisan)->post(route('help.chat.send'), ['body' => 'Third']);

        $this->assertDatabaseCount('support_tickets', 1);
        $this->assertDatabaseCount('support_ticket_messages', 3);
    }

    public function test_customer_sync_never_includes_internal_notes(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => 'Help',
        ]);
        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $artisan->id,
            'is_staff' => false,
            'kind' => SupportTicketMessage::KIND_MESSAGE,
            'body' => 'Visible to me',
        ]);
        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $staff->id,
            'is_staff' => true,
            'kind' => SupportTicketMessage::KIND_NOTE,
            'body' => 'SECRET_INTERNAL_NOTE',
        ]);

        $this->actingAs($artisan)
            ->getJson(route('help.chat.sync'))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Visible to me'])
            ->assertJsonMissing(['body' => 'SECRET_INTERNAL_NOTE']);
    }

    public function test_resolved_message_reopens_the_same_thread(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_RESOLVED,
            'subject' => 'Old',
            'resolved_at' => now()->subDay(),
        ]);

        $this->actingAs($artisan)
            ->post(route('help.chat.send'), ['body' => 'Still stuck']);

        $this->assertDatabaseCount('support_tickets', 1);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => SupportTicket::STATUS_NEW,
            'resolved_at' => null,
        ]);
    }

    public function test_online_staff_receive_round_robin_assignment(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();
        app(SupportPresence::class)->heartbeat($staff);

        $this->actingAs($artisan)
            ->postJson(route('help.chat.send'), ['body' => 'Need a human'])
            ->assertOk()
            ->assertJsonPath('state', 'connected');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $artisan->id,
            'assigned_to_user_id' => $staff->id,
        ]);
    }

    public function test_staff_can_leave_a_note_and_resolve(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => 'Help',
        ]);

        $this->actingAs($staff)
            ->postJson(route('admin.support.notes.store', $ticket), ['body' => 'Called the artisan.'])
            ->assertOk()
            ->assertJsonFragment(['body' => 'Called the artisan.']);

        $this->actingAs($staff)
            ->postJson(route('admin.support.reply', $ticket), ['body' => 'We are on it.'])
            ->assertOk();

        $this->actingAs($staff)
            ->postJson(route('admin.support.resolve', $ticket))
            ->assertOk()
            ->assertJsonPath('status', 'resolved');
    }

    public function test_csat_is_recorded_after_resolve(): void
    {
        $artisan = User::factory()->regularUser()->create();
        SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $this->actingAs($artisan)
            ->postJson(route('help.chat.csat'), ['score' => 5, 'comment' => 'Fast help'])
            ->assertOk()
            ->assertJsonPath('csat.prompt', false)
            ->assertJsonPath('csat.score', 5);
    }

    public function test_attachment_uploads_to_public_disk(): void
    {
        Storage::fake('public');
        $artisan = User::factory()->regularUser()->create();
        $file = UploadedFile::fake()->image('issue.jpg', 80, 80);

        $this->actingAs($artisan)
            ->post(route('help.chat.send'), [
                'body' => 'Screenshot',
                'attachment' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('messages.0.attachment.image', true);
    }

    public function test_staff_inbox_and_reports_render(): void
    {
        $staff = $this->supportStaff();

        $this->actingAs($staff)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Support/Index')->has('tickets'));

        $this->actingAs($staff)
            ->get(route('admin.support.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Support/Reports')->has('report'));
    }

    public function test_canned_replies_can_be_saved(): void
    {
        $staff = $this->supportStaff();

        $this->actingAs($staff)
            ->post(route('admin.support.canned.store'), [
                'title' => 'Billing intro',
                'body' => 'Credits never expire.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_canned_replies', [
            'user_id' => $staff->id,
            'title' => 'Billing intro',
        ]);
        $this->assertInstanceOf(SupportCannedReply::class, SupportCannedReply::query()->first());
    }

    public function test_message_sending_is_rate_limited(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $this->actingAs($artisan);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('help.chat.send'), ['body' => 'Ping '.$i])->assertOk();
        }

        $this->postJson(route('help.chat.send'), ['body' => 'Too many'])->assertStatus(429);
    }

    public function test_audit_logs_chat_start_and_close_but_not_every_reply(): void
    {
        $artisan = User::factory()->regularUser()->create();
        $staff = $this->supportStaff();
        $ticket = SupportTicket::query()->create([
            'user_id' => $artisan->id,
            'status' => SupportTicket::STATUS_NEW,
            'subject' => 'Help',
        ]);

        $this->actingAs($staff)
            ->postJson(route('admin.support.reply', $ticket), ['body' => 'We are on it.'])
            ->assertOk();

        $this->actingAs($staff)
            ->postJson(route('admin.support.reply', $ticket), ['body' => 'Still here.'])
            ->assertOk();

        $this->actingAs($staff)
            ->postJson(route('admin.support.notes.store', $ticket), ['body' => 'Called them.'])
            ->assertOk();

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $staff->id,
            'action' => 'support.claimed',
        ]);
        $this->assertDatabaseMissing('admin_audit_logs', ['action' => 'support.replied']);
        $this->assertDatabaseMissing('admin_audit_logs', ['action' => 'support.noted']);
        $this->assertSame(1, AdminAuditLog::query()->where('action', 'support.claimed')->count());

        $this->actingAs($staff)
            ->postJson(route('admin.support.resolve', $ticket))
            ->assertOk();

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $staff->id,
            'action' => 'support.resolved',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function supportStaff(array $permissions = ['admin.support.manage']): User
    {
        $role = StaffRole::query()->create([
            'slug' => 'customer_support',
            'name' => 'Customer support',
            'permissions' => $permissions,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $user = User::factory()->operationsAdmin()->create();
        $user->staffRoles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user->fresh(['staffRoles']);
    }
}
