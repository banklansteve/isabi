<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\AcknowledgeDisciplinaryNoticeRequest;
use App\Http\Requests\Admin\Hr\RespondDisciplinaryNoticeRequest;
use App\Http\Requests\Admin\Hr\StoreDisciplinaryAppealRequest;
use App\Models\DisciplinaryAction;
use App\Support\Hr\DisciplinaryCaseService;
use App\Support\Hr\DisciplinaryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisciplinaryNoticeController extends Controller
{
    public function __construct(private readonly DisciplinaryCaseService $cases) {}

    public function index(Request $request): Response
    {
        $notices = DisciplinaryAction::query()
            ->with(['case'])
            ->whereHas('case', fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (DisciplinaryAction $action) => DisciplinaryPresenter::notice($action))
            ->values();

        return Inertia::render('Admin/Hr/Discipline/Notices', [
            'notices' => $notices,
        ]);
    }

    public function show(Request $request, DisciplinaryAction $disciplinaryAction): Response
    {
        $this->assertOwn($request, $disciplinaryAction);

        return Inertia::render('Admin/Hr/Discipline/NoticeShow', [
            'notice' => DisciplinaryPresenter::notice($disciplinaryAction->load('case')),
        ]);
    }

    public function acknowledge(AcknowledgeDisciplinaryNoticeRequest $request, DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $this->assertOwn($request, $disciplinaryAction);
        $this->cases->acknowledge($disciplinaryAction, $request->user());

        return back()->with('toast', ['type' => 'success', 'message' => 'Receipt recorded.']);
    }

    public function respond(RespondDisciplinaryNoticeRequest $request, DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $this->assertOwn($request, $disciplinaryAction);
        $this->cases->respond($disciplinaryAction, $request->user(), $request->validated('response'));

        return back()->with('toast', ['type' => 'success', 'message' => 'Your written response has been added to the record.']);
    }

    public function appeal(StoreDisciplinaryAppealRequest $request, DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $this->assertOwn($request, $disciplinaryAction);
        $this->cases->raiseAppeal($disciplinaryAction->case, $request->user(), $request->validated('grounds'));

        return back()->with('toast', ['type' => 'success', 'message' => 'Your appeal has been submitted for review.']);
    }

    public function letter(Request $request, DisciplinaryAction $disciplinaryAction)
    {
        $this->assertOwn($request, $disciplinaryAction);

        return app(HrDisciplineController::class)->letterDownload($disciplinaryAction);
    }

    private function assertOwn(Request $request, DisciplinaryAction $action): void
    {
        abort_unless($action->case()->value('user_id') === $request->user()->id, 403);
    }
}
