<?php

use App\Models\StaffConversation;
use App\Models\StaffConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{uid}', function (User $user, string $uid) {
    return $user->uid === $uid;
});

Broadcast::channel('support.inbox', function (User $user) {
    return $user->isStaff()
        && ($user->isSuperAdmin() || $user->canDo('admin.support.manage'));
});

Broadcast::channel('staff.chat', function (User $user) {
    return $user->isStaff();
});

Broadcast::channel('staff.chat.{uid}', function (User $user, string $uid) {
    if (! $user->isStaff()) {
        return false;
    }

    $conversation = StaffConversation::query()->where('uid', $uid)->first();

    if (! $conversation) {
        return false;
    }

    return StaffConversationParticipant::query()
        ->where('staff_conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->exists();
});
