<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryRecordRequest;
use App\Http\Requests\Admin\Hr\UpdateDisciplinaryRecordRequest;
use App\Models\DisciplinaryRecord;
use App\Models\StaffDocument;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Hr\HrPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrDisciplineController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->string('status');
        $type = (string) $request->string('type');
        $search = trim((string) $request->string('q'));

        $records = DisciplinaryRecord::query()
            ->with(['user', 'issuedBy', 'document'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('summary', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("status = 'closed'")
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (DisciplinaryRecord $record) => HrPresenter::disciplineRow($record))
            ->values();

        return Inertia::render('Admin/Hr/Discipline', [
            'records' => $records,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'type' => $type,
            ],
            'stats' => [
                'open' => DisciplinaryRecord::query()->where('status', DisciplinaryRecord::STATUS_OPEN)->count(),
                'monitoring' => DisciplinaryRecord::query()->where('status', DisciplinaryRecord::STATUS_MONITORING)->count(),
                'closed' => DisciplinaryRecord::query()->where('status', DisciplinaryRecord::STATUS_CLOSED)->count(),
            ],
            'staff' => User::query()
                ->staff()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name ?: $user->email,
                ])
                ->values(),
            'types' => DisciplinaryRecord::typeOptions(),
            'statuses' => DisciplinaryRecord::statusOptions(),
            'can' => [
                'manage' => $request->user()->canDo('hr.manage'),
            ],
        ]);
    }

    public function store(StoreDisciplinaryRecordRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();
        $this->assertDocumentBelongsToStaff($data['staff_document_id'] ?? null, $user);

        $record = DisciplinaryRecord::query()->create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'status' => $data['status'] ?? DisciplinaryRecord::STATUS_OPEN,
            'occurred_on' => $data['occurred_on'],
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'issued_by' => $request->user()->id,
            'follow_up_on' => $data['follow_up_on'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'staff_document_id' => $data['staff_document_id'] ?? null,
        ]);

        ActivityLogger::log(
            action: 'hr.discipline_recorded',
            summary: "{$request->user()->name} recorded a {$record->typeLabel()} for {$user->name}.",
            properties: [
                'staff_id' => $user->id,
                'disciplinary_record_id' => $record->id,
                'type' => $record->type,
            ],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Disciplinary record added.']);
    }

    public function update(UpdateDisciplinaryRecordRequest $request, DisciplinaryRecord $disciplinaryRecord): RedirectResponse
    {
        $data = $request->validated();
        $this->assertDocumentBelongsToStaff($data['staff_document_id'] ?? null, $disciplinaryRecord->user);

        $disciplinaryRecord->update([
            'type' => $data['type'],
            'status' => $data['status'],
            'occurred_on' => $data['occurred_on'],
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'follow_up_on' => $data['follow_up_on'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'staff_document_id' => $data['staff_document_id'] ?? null,
        ]);

        ActivityLogger::log(
            action: 'hr.discipline_updated',
            summary: "{$request->user()->name} updated a disciplinary record for {$disciplinaryRecord->user->name}.",
            properties: [
                'staff_id' => $disciplinaryRecord->user_id,
                'disciplinary_record_id' => $disciplinaryRecord->id,
                'status' => $disciplinaryRecord->status,
            ],
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Disciplinary record updated.']);
    }

    private function assertDocumentBelongsToStaff(?int $documentId, ?User $user): void
    {
        if (! $documentId || ! $user) {
            return;
        }

        $belongs = StaffDocument::query()
            ->where('id', $documentId)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($belongs, 422, 'That document does not belong to this staff member.');
    }
}
