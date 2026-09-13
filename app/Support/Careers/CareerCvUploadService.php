<?php

namespace App\Support\Careers;

use App\Services\CloudinaryMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CareerCvUploadService
{
    public function __construct(private readonly CloudinaryMediaService $cloudinary) {}

    /**
     * @return array{disk: string, path: string, url: string, name: string}
     */
    public function store(UploadedFile $file, int $vacancyId): array
    {
        $name = $file->getClientOriginalName();
        $mime = (string) $file->getMimeType();

        if ($this->cloudinary->enabled()) {
            try {
                $uploaded = $this->cloudinary->upload($file, $this->cloudinaryFolder($vacancyId), [
                    'resource_type' => 'raw',
                ]);

                return [
                    'disk' => 'cloudinary',
                    'path' => (string) ($uploaded['public_id'] ?? ''),
                    'url' => (string) ($uploaded['url'] ?? ''),
                    'name' => $name,
                ];
            } catch (RuntimeException) {
                // Fall through to local storage.
            }
        }

        $path = $file->store('careers/cvs/'.$vacancyId, 'public');

        if (! $path) {
            throw new RuntimeException('Could not store the CV. Please try again.');
        }

        return [
            'disk' => 'public',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'name' => $name,
        ];
    }

    private function cloudinaryFolder(int $vacancyId): string
    {
        $root = trim((string) config('cloudinary.folders.root', 'Kraftrack'), '/');

        return $root.'/careers/cvs/'.$vacancyId;
    }
}
