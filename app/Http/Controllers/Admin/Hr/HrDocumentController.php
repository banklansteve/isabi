<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hr\StoreStaffDocumentRequest;
use App\Http\Requests\Admin\Hr\UpdateStaffDocumentRequest;
use App\Models\StaffDocument;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrDocumentController extends Controller
{
    public function store(StoreStaffDocumentRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validated();
        $file = $request->file('file');

        $path = $file->store("hr/documents/{$user->id}", 'local');

        $user->staffDocuments()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'expiry_date' => $data['expiry_date'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        ActivityLogger::log(
            action: 'hr.document_uploaded',
            summary: "{$request->user()->name} uploaded a document for {$user->name}.",
            properties: ['staff_id' => $user->id, 'type' => $data['type']],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'documents'])
            ->with('toast', ['type' => 'success', 'message' => 'Document uploaded.']);
    }

    public function update(UpdateStaffDocumentRequest $request, StaffDocument $staffDocument): RedirectResponse
    {
        $data = $request->validated();
        $user = $staffDocument->user;

        $staffDocument->title = $data['title'];
        $staffDocument->type = $data['type'];
        $staffDocument->expiry_date = $data['expiry_date'] ?? null;
        $staffDocument->expiry_reminded_at = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            Storage::disk($staffDocument->disk)->delete($staffDocument->path);
            $path = $file->store("hr/documents/{$user->id}", 'local');
            $staffDocument->path = $path;
            $staffDocument->original_name = $file->getClientOriginalName();
            $staffDocument->mime = $file->getClientMimeType();
            $staffDocument->size = $file->getSize();
        }

        $staffDocument->save();

        ActivityLogger::log(
            action: 'hr.document_updated',
            summary: "{$request->user()->name} updated a document for {$user->name}.",
            properties: ['staff_id' => $user->id, 'document_id' => $staffDocument->id],
        );

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'documents'])
            ->with('toast', ['type' => 'success', 'message' => 'Document updated.']);
    }

    public function download(Request $request, StaffDocument $staffDocument): StreamedResponse
    {
        abort_unless($request->user()->canDo('hr.view'), 403);

        $disk = Storage::disk($staffDocument->disk);
        abort_unless($disk->exists($staffDocument->path), 404);

        return $disk->download(
            $staffDocument->path,
            $staffDocument->original_name ?: $staffDocument->title,
        );
    }

    public function destroy(Request $request, StaffDocument $staffDocument): RedirectResponse
    {
        abort_unless($request->user()->canDo('hr.manage'), 403);

        $user = $staffDocument->user;
        Storage::disk($staffDocument->disk)->delete($staffDocument->path);
        $staffDocument->delete();

        return redirect()
            ->route('admin.hr.staff.show', ['user' => $user, 'tab' => 'documents'])
            ->with('toast', ['type' => 'success', 'message' => 'Document removed.']);
    }
}
