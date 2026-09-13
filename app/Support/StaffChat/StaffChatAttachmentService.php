<?php

namespace App\Support\StaffChat;

use App\Services\CloudinaryMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StaffChatAttachmentService
{
    public function __construct(private readonly CloudinaryMediaService $cloudinary) {}

    /**
     * @return array{disk: string, path: string, url: string, name: string, mime: string, size: int}
     */
    public function store(UploadedFile $file, int $conversationId): array
    {
        $name = $file->getClientOriginalName();
        $mime = (string) $file->getMimeType();
        $size = (int) $file->getSize();

        if ($this->cloudinary->enabled()) {
            try {
                $uploaded = $this->cloudinary->upload($file, $this->cloudinaryFolder($conversationId), [
                    'resource_type' => str_starts_with($mime, 'image/') ? 'image' : 'auto',
                ]);

                return [
                    'disk' => 'cloudinary',
                    'path' => (string) ($uploaded['public_id'] ?? ''),
                    'url' => (string) ($uploaded['url'] ?? ''),
                    'name' => $name,
                    'mime' => $mime,
                    'size' => $size,
                ];
            } catch (RuntimeException) {
                // Fall through to local storage.
            }
        }

        $path = $file->store('staff-chat/'.$conversationId, 'public');

        if (! $path) {
            throw new RuntimeException('Could not store the attachment. Please try again.');
        }

        return [
            'disk' => 'public',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'name' => $name,
            'mime' => $mime,
            'size' => $size,
        ];
    }

    private function cloudinaryFolder(int $conversationId): string
    {
        $root = trim((string) config('cloudinary.folders.root', 'Kraftrack'), '/');

        return $root.'/staff-chat/'.$conversationId;
    }
}
