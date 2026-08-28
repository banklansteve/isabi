<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\ArchiveDisciplinaryCaseRequest;
use App\Http\Requests\Admin\Hr\ReviewDisciplinaryAppealRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryActionRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryAppealRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryCaseRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryEvidenceRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryNoteRequest;
use App\Http\Requests\Admin\Hr\TransitionDisciplinaryCaseRequest;
use App\Http\Requests\Admin\Hr\UpdateDisciplinaryLetterTemplateRequest;
use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryAppeal;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryEvidence;
use App\Models\DisciplinaryLetterTemplate;
use App\Models\User;
use App\Support\Admin\AdminResponse;
use App\Support\Hr\DisciplinaryCaseService;
use App\Support\Hr\DisciplinaryLetter;
use App\Support\Hr\DisciplinaryPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrDisciplineController extends Controller
{
    public function __construct(private readonly DisciplinaryCaseService $cases) {}

    public function index(Request $request): Response
    {
        DisciplinaryLetter::ensureTemplates();

        return Inertia::render('Admin/Hr/Discipline/Index', $this->indexProps($request));
    }

    public function show(Request $request, DisciplinaryCase $disciplinaryCase): Response|JsonResponse
    {
        DisciplinaryLetter::ensureTemplates();

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($this->panel($disciplinaryCase, $request->user()));
        }

        return Inertia::render('Admin/Hr/Discipline/Index', [
            ...$this->indexProps($request),
            'opened_id' => $disciplinaryCase->id,
        ]);
    }

    public function store(StoreDisciplinaryCaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $staff = User::query()->findOrFail($data['user_id']);
        $case = $this->cases->create($request->user(), $staff, $data);

        if ($request->input('return') === 'profile') {
            return redirect()
                ->route('admin.hr.staff.show', ['user' => $staff, 'tab' => 'discipline', 'case' => $case->id])
                ->with('toast', ['type' => 'success', 'message' => "Case {$case->reference} opened."]);
        }

        return redirect()
            ->route('admin.hr.discipline.show', $case)
            ->with('toast', ['type' => 'success', 'message' => "Case {$case->reference} opened."]);
    }

    public function transition(TransitionDisciplinaryCaseRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $this->cases->transition($disciplinaryCase, $request->user(), $data['status'], $data['reason']);

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Case status updated.']);
    }

    public function archive(ArchiveDisciplinaryCaseRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $this->cases->archive($disciplinaryCase, $request->user(), $request->validated('reason'));

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Case archived. The record remains on file.']);
    }

    public function storeNote(StoreDisciplinaryNoteRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $this->cases->addNote(
            $disciplinaryCase,
            $request->user(),
            $request->validated('body'),
            $request->boolean('confidential'),
        );

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Note added to the case record.']);
    }

    public function storeEvidence(StoreDisciplinaryEvidenceRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $this->cases->attachEvidence(
            $disciplinaryCase,
            $request->user(),
            $request->file('file'),
            $request->validated('title'),
        );

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Supporting material attached.']);
    }

    public function downloadEvidence(Request $request, DisciplinaryEvidence $disciplinaryEvidence): StreamedResponse
    {
        abort_unless($request->user()->canDo('hr.discipline.view'), 403);

        $document = $disciplinaryEvidence->document;
        abort_unless($document, 404);

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name ?: $document->title);
    }

    public function storeAction(StoreDisciplinaryActionRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $action = $this->cases->issueAction($disciplinaryCase, $request->user(), $request->validated());
        $message = $action->isSerious()
            ? 'Formal action recorded. Confirm any Access and HR employment updates separately.'
            : 'Formal action recorded.';

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => $message]);
    }

    public function letter(Request $request, DisciplinaryAction $disciplinaryAction)
    {
        abort_unless($request->user()->canDo('hr.discipline.view'), 403);

        return $this->letterDownload($disciplinaryAction);
    }

    public function storeAppeal(StoreDisciplinaryAppealRequest $request, DisciplinaryCase $disciplinaryCase): JsonResponse|RedirectResponse
    {
        $this->cases->raiseAppeal($disciplinaryCase, $request->user(), $request->validated('grounds'));

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Appeal recorded and waiting for review.']);
    }

    public function reviewAppeal(ReviewDisciplinaryAppealRequest $request, DisciplinaryAppeal $disciplinaryAppeal): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $this->cases->reviewAppeal($disciplinaryAppeal, $request->user(), $data['outcome'], $data['reason']);

        return AdminResponse::mutation($request, ['type' => 'success', 'message' => 'Appeal outcome recorded.']);
    }

    public function templates(Request $request): Response
    {
        DisciplinaryLetter::ensureTemplates();

        return Inertia::render('Admin/Hr/Discipline/Templates', [
            'templates' => DisciplinaryLetterTemplate::query()
                ->with('updater')
                ->orderBy('name')
                ->get()
                ->map(fn (DisciplinaryLetterTemplate $template) => DisciplinaryPresenter::templateRow($template))
                ->values(),
            'placeholders' => [
                '{{staff_name}}',
                '{{case_reference}}',
                '{{incident_date}}',
                '{{incident_summary}}',
                '{{outcome}}',
                '{{justification}}',
                '{{issued_date}}',
                '{{owner_name}}',
                '{{suspension_period}}',
            ],
            'can' => [
                'manage' => $request->user()->canDo('hr.discipline.manage'),
            ],
        ]);
    }

    public function updateTemplate(UpdateDisciplinaryLetterTemplateRequest $request, DisciplinaryLetterTemplate $disciplinaryLetterTemplate): RedirectResponse
    {
        $this->cases->updateTemplate($disciplinaryLetterTemplate, $request->user(), $request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Letter template updated.']);
    }

    public function reports(Request $request): Response
    {
        $from = (string) $request->string('from');
        $to = (string) $request->string('to');

        $cases = $this->reportQuery($from, $to)
            ->with('appeals')
            ->get();

        return Inertia::render('Admin/Hr/Discipline/Reports', [
            'report' => DisciplinaryPresenter::aggregateReport($cases),
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function exportReports(Request $request): StreamedResponse
    {
        $from = (string) $request->string('from');
        $to = (string) $request->string('to');
        $report = DisciplinaryPresenter::aggregateReport(
            $this->reportQuery($from, $to)->with('appeals')->get()
        );

        $filename = 'disciplinary-summary-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Disciplinary cases — aggregate summary']);
            fputcsv($handle, ['This export does not name individuals.']);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total cases', $report['total']]);
            fputcsv($handle, ['Open cases', $report['open']]);
            fputcsv($handle, ['Resolved or closed', $report['resolved']]);
            fputcsv($handle, ['Average days to resolution', $report['average_days_to_resolution'] ?? '—']);
            fputcsv($handle, ['Appeals', $report['appeal_count']]);
            fputcsv($handle, ['Appeal rate %', $report['appeal_rate']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Category', 'Cases']);
            foreach ($report['by_category'] as $row) {
                fputcsv($handle, [$row['label'], $row['count']]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Month', 'Cases opened']);
            foreach ($report['volume'] as $row) {
                fputcsv($handle, [$row['label'], $row['count']]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Builder<DisciplinaryCase>
     */
    private function reportQuery(string $from, string $to)
    {
        return DisciplinaryCase::query()
            ->when($from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to));
    }

    public function letterDownload(DisciplinaryAction $action)
    {
        $action->load(['case.staff', 'case.owner', 'issuer']);

        $pdf = Pdf::loadView('pdf.disciplinary-letter', [
            'action' => $action,
            'case' => $action->case,
        ])->setPaper('a4');

        return $pdf->download($action->case->reference.'-notice.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(Request $request): array
    {
        $status = (string) $request->string('status');
        $severity = (string) $request->string('severity');
        $category = (string) $request->string('category');
        $search = trim((string) $request->string('q'));
        $from = (string) $request->string('from');
        $to = (string) $request->string('to');
        $archived = $request->boolean('archived');
        $openedId = $request->integer('case') ?: null;

        $cases = DisciplinaryCase::query()
            ->with(['staff', 'owner'])
            ->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
            ->when($archived, fn ($query) => $query->whereNotNull('archived_at'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('staff', function ($user) use ($search) {
                            $user->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("status in ('resolved','closed')")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (DisciplinaryCase $case) => DisciplinaryPresenter::caseRow($case))
            ->values();

        return [
            'cases' => $cases,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'severity' => $severity,
                'category' => $category,
                'from' => $from,
                'to' => $to,
                'archived' => $archived,
            ],
            'stats' => [
                'open' => DisciplinaryCase::query()->open()->count(),
                'decision_pending' => DisciplinaryCase::query()->where('status', DisciplinaryCase::STATUS_DECISION_PENDING)->whereNull('archived_at')->count(),
                'appealed' => DisciplinaryCase::query()->where('status', DisciplinaryCase::STATUS_APPEALED)->whereNull('archived_at')->count(),
            ],
            'staff' => $this->staffOptions(),
            'owners' => $this->staffOptions(),
            'options' => DisciplinaryPresenter::options(),
            'can' => [
                'manage' => $request->user()->canDo('hr.discipline.manage'),
            ],
            'opened_id' => $openedId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panel(DisciplinaryCase $case, User $actor): array
    {
        $case->load([
            'staff',
            'owner',
            'opener',
            'notes.author',
            'evidence.document',
            'evidence.uploader',
            'actions.issuer',
            'appeals.raiser',
            'appeals.reviewer',
            'events.actor',
        ]);

        return [
            'record' => DisciplinaryPresenter::caseDetail($case, $actor),
            'templates' => DisciplinaryLetterTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (DisciplinaryLetterTemplate $template) => DisciplinaryPresenter::templateRow($template))
                ->values(),
            'options' => DisciplinaryPresenter::options(),
            'placeholders' => array_keys(DisciplinaryLetter::placeholders($case)),
            'can' => [
                'manage' => $actor->canDo('hr.discipline.manage'),
                'review_appeal' => $actor->isSuperAdmin(),
                'view_confidential' => $actor->isSuperAdmin(),
                'hr_profile' => $actor->canDo('hr.view'),
                'access' => $actor->canDo('admin.staff.manage'),
            ],
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function staffOptions(): array
    {
        return User::query()
            ->staff()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
            ])
            ->values()
            ->all();
    }
}
