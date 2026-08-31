<?php

namespace Tests\Feature\Admin;

use App\Models\StaffConversation;
use App\Models\StaffMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_asap_channel_is_shared_by_ops_and_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create();

        $this->actingAs($ops)
            ->get(route('admin.asap.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Asap/Index')
                ->has('conversations', 1)
                ->where('conversations.0.title', 'ASAP')
                ->where('conversations.0.type', 'asap'));

        $this->actingAs($super)
            ->get(route('admin.asap.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Asap/Index')
                ->has('conversations', 1)
                ->where('conversations.0.title', 'ASAP'));

        $this->assertDatabaseCount('staff_conversations', 1);
    }

    public function test_ops_can_direct_message_super_admin_and_send_files(): void
    {
        Storage::fake('public');

        $super = User::factory()->superAdmin()->create(['name' => 'Super Admin']);
        $ops = User::factory()->operationsAdmin()->create(['name' => 'Ops One']);

        $this->actingAs($ops)
            ->post(route('admin.asap.direct'), ['user_id' => $super->id])
            ->assertRedirect();

        $conversation = StaffConversation::query()->where('type', StaffConversation::TYPE_DIRECT)->firstOrFail();

        $this->actingAs($ops)
            ->post(route('admin.asap.store', $conversation), [
                'body' => 'Hello boss',
                'attachment' => UploadedFile::fake()->create('note.pdf', 120, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('title', $super->name);

        $this->assertDatabaseHas('staff_messages', [
            'staff_conversation_id' => $conversation->id,
            'user_id' => $ops->id,
            'body' => 'Hello boss',
        ]);

        $message = StaffMessage::query()->first();
        $this->assertNotNull($message?->attachment_url);
        $this->assertSame('note.pdf', $message->attachment_name);
    }

    public function test_unread_marks_and_clears_when_opened(): void
    {
        $opsA = User::factory()->operationsAdmin()->create();
        $opsB = User::factory()->operationsAdmin()->create();

        $this->actingAs($opsA)->get(route('admin.asap.index'))->assertOk();

        $asap = StaffConversation::query()->where('type', StaffConversation::TYPE_ASAP)->firstOrFail();

        $this->actingAs($opsA)
            ->post(route('admin.asap.store', $asap), ['body' => 'Heads up'], ['Accept' => 'application/json'])
            ->assertOk();

        $this->actingAs($opsB)
            ->get(route('admin.asap.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('conversations.0.unread', true)
                ->where('unread_count', 1));

        $this->actingAs($opsB)
            ->get(route('admin.asap.show', $asap))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('conversation.unread', false)
                ->where('unread_count', 0));
    }

    public function test_asap_notifications_aggregate_without_message_body_and_link_to_chat(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create(['name' => 'Ada George']);

        $this->actingAs($ops)->get(route('admin.asap.index'))->assertOk();
        $asap = StaffConversation::query()->where('type', StaffConversation::TYPE_ASAP)->firstOrFail();

        $this->actingAs($ops)
            ->postJson(route('admin.asap.store', $asap), ['body' => 'Secret ops detail'])
            ->assertOk();

        $this->actingAs($ops)
            ->postJson(route('admin.asap.store', $asap), ['body' => 'Another secret'])
            ->assertOk();

        $this->assertDatabaseCount('announcements', 1);
        $this->assertDatabaseCount('announcement_deliveries', 1);
        $this->assertDatabaseHas('announcements', [
            'title' => 'ASAP Message(2)',
        ]);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $notifications = app(\App\Support\Admin\AnnouncementService::class)->inboxFor($super);
        $chat = $notifications->first(fn ($delivery) => data_get($delivery->announcement?->segment, 'kind') === 'staff_chat_asap');

        $this->assertNotNull($chat);
        $presented = app(\App\Support\Admin\AnnouncementService::class)->presentDelivery($chat, $super);

        $this->assertSame('ASAP Message(2)', $presented['title']);
        $this->assertStringNotContainsString('Secret', $presented['body']);
        $this->assertSame($asap->adminShowUrl(), $presented['href']);
        $this->assertDatabaseCount('announcements', 1);
    }

    public function test_direct_notifications_aggregate_across_ops_senders(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ada = User::factory()->operationsAdmin()->create([
            'name' => 'Ada George',
            'first_name' => 'Ada',
            'last_name' => 'George',
        ]);
        $michael = User::factory()->operationsAdmin()->create([
            'name' => 'Michael Brown',
            'first_name' => 'Michael',
            'last_name' => 'Brown',
        ]);

        $this->actingAs($ada)
            ->post(route('admin.asap.direct'), ['user_id' => $super->id])
            ->assertRedirect();
        $adaChat = StaffConversation::query()->where('type', StaffConversation::TYPE_DIRECT)->latest('id')->firstOrFail();
        $this->actingAs($ada)
            ->postJson(route('admin.asap.store', $adaChat), ['body' => 'Hi from Ada'])
            ->assertOk();

        $presented = $this->latestDirectNotification($super);
        $this->assertSame('Message from Ada George(1)', $presented['title']);
        $this->assertSame($adaChat->adminShowUrl(), $presented['href']);

        $this->actingAs($michael)
            ->post(route('admin.asap.direct'), ['user_id' => $super->id])
            ->assertRedirect();
        $michaelChat = StaffConversation::query()
            ->where('type', StaffConversation::TYPE_DIRECT)
            ->whereKeyNot($adaChat->id)
            ->latest('id')
            ->firstOrFail();
        $this->actingAs($michael)
            ->postJson(route('admin.asap.store', $michaelChat), ['body' => 'Hi from Michael'])
            ->assertOk();

        $presented = $this->latestDirectNotification($super);
        $this->assertSame('Ops Messages(2)', $presented['title']);
        $this->assertSame(route('admin.asap.index'), $presented['href']);
        $this->assertDatabaseCount('announcements', 1);
    }

    public function test_read_notification_is_removed_from_inbox(): void
    {
        $super = User::factory()->superAdmin()->create();
        $ops = User::factory()->operationsAdmin()->create(['name' => 'Ada George']);

        $this->actingAs($ops)->get(route('admin.asap.index'))->assertOk();
        $asap = StaffConversation::query()->where('type', StaffConversation::TYPE_ASAP)->firstOrFail();

        $this->actingAs($ops)
            ->postJson(route('admin.asap.store', $asap), ['body' => 'Ping'])
            ->assertOk();

        $delivery = app(\App\Support\Admin\AnnouncementService::class)
            ->inboxFor($super)
            ->first();

        $this->assertNotNull($delivery);

        $this->actingAs($super)
            ->postJson(route('notifications.read', $delivery), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('notifications.unread_count', 0)
            ->assertJsonPath('notifications.items', []);

        $this->assertSame(0, app(\App\Support\Admin\AnnouncementService::class)->inboxFor($super)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function latestDirectNotification(User $user): array
    {
        $delivery = app(\App\Support\Admin\AnnouncementService::class)
            ->inboxFor($user)
            ->first(fn ($row) => data_get($row->announcement?->segment, 'kind') === 'staff_chat_direct');

        $this->assertNotNull($delivery);

        return app(\App\Support\Admin\AnnouncementService::class)->presentDelivery($delivery, $user);
    }
}
