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
}
