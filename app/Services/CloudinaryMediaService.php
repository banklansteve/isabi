<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CloudinaryMediaService
{
    private ?Cloudinary $client = null;

    public function enabled(): bool
    {
        return filled(config('cloudinary.url'))
            || (
                filled(config('cloudinary.cloud_name'))
                && filled(config('cloudinary.api_key'))
                && filled(config('cloudinary.api_secret'))
            );
    }

    public function client(): Cloudinary
    {
        if ($this->client instanceof Cloudinary) {
            return $this->client;
        }

        // Prefer CLOUDINARY_URL — single source of truth from the Cloudinary dashboard.
        $url = trim((string) config('cloudinary.url'));
        if ($url !== '') {
            $this->client = new Cloudinary($url);

            return $this->client;
        }

        $cloudName = trim((string) config('cloudinary.cloud_name'));
        $apiKey = trim((string) config('cloudinary.api_key'));
        $apiSecret = trim((string) config('cloudinary.api_secret'));

        if ($cloudName !== '' && $apiKey !== '' && $apiSecret !== '') {
            $this->client = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
                'url' => [
                    'secure' => (bool) config('cloudinary.secure', true),
                ],
            ]);

            return $this->client;
        }

        throw new RuntimeException(
            'Cloudinary is not configured. Set CLOUDINARY_URL or CLOUDINARY_CLOUD_NAME / API_KEY / API_SECRET.'
        );
    }

    /**
     * Profile photos live under isabi/profiles/{userId}
     *
     * @return array{public_id: string, url: string, bytes: int, resource_type: string, format: ?string, mime_type: ?string}
     */
    public function uploadProfilePhoto(UploadedFile $file, int $userId): array
    {
        return $this->upload($file, $this->folder(['profiles', (string) $userId]), [
            'resource_type' => 'image',
        ]);
    }

    /**
     * Job media lives under isabi/work-logs/{userId}/{workLogId}
     *
     * @return array{public_id: string, url: string, bytes: int, resource_type: string, format: ?string, mime_type: ?string}
     */
    public function uploadWorkLogMedia(UploadedFile $file, int $userId, int $workLogId): array
    {
        $mime = (string) $file->getMimeType();
        $resourceType = str_starts_with($mime, 'video/') ? 'video' : 'image';

        return $this->upload($file, $this->folder(['work-logs', (string) $userId, (string) $workLogId]), [
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Client review photos live under isabi/reviews/{userId}/{workLogUid}
     *
     * @return array{public_id: string, url: string, bytes: int, resource_type: string, format: ?string, mime_type: ?string}
     */
    public function uploadReviewPhoto(UploadedFile $file, int $userId, string $workLogUid): array
    {
        return $this->upload($file, $this->folder(['reviews', (string) $userId, $workLogUid]), [
            'resource_type' => 'image',
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{public_id: string, url: string, bytes: int, resource_type: string, format: ?string, mime_type: ?string}
     */
    public function upload(UploadedFile $file, string $folder, array $options = []): array
    {
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('Could not read the uploaded file. Please try again.');
        }

        try {
            $result = $this->client()->uploadApi()->upload($path, array_merge([
                'folder' => $folder,
                'use_filename' => true,
                'unique_filename' => true,
                'resource_type' => 'auto',
            ], $options));
        } catch (Throwable $e) {
            Log::error('Cloudinary upload failed.', [
                'folder' => $folder,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            $hint = str_contains(strtolower($e->getMessage()), 'invalid signature')
                ? ' Cloudinary credentials look incorrect — check CLOUDINARY_URL in your .env.'
                : '';

            throw new RuntimeException('Could not upload media. Please try again.'.$hint, 0, $e);
        }

        // SDK may return ArrayAccess / ApiResponse rather than a plain array.
        $publicId = (string) data_get($result, 'public_id', '');
        $url = (string) (data_get($result, 'secure_url') ?: data_get($result, 'url') ?: '');

        if ($publicId === '' || $url === '') {
            throw new RuntimeException('Cloudinary upload returned an incomplete response.');
        }

        return [
            'public_id' => $publicId,
            'url' => $url,
            'bytes' => (int) (data_get($result, 'bytes') ?: $file->getSize() ?: 0),
            'resource_type' => (string) (data_get($result, 'resource_type') ?: 'image'),
            'format' => data_get($result, 'format') ? (string) data_get($result, 'format') : null,
            'mime_type' => $file->getMimeType(),
        ];
    }

    public function delete(?string $publicId, string $resourceType = 'image'): void
    {
        if (! filled($publicId)) {
            return;
        }

        try {
            $this->client()->uploadApi()->destroy($publicId, [
                'resource_type' => in_array($resourceType, ['image', 'video', 'raw'], true)
                    ? $resourceType
                    : 'image',
                'invalidate' => true,
            ]);
        } catch (Throwable $e) {
            Log::warning('Cloudinary delete failed.', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<string>  $segments
     */
    public function folder(array $segments): string
    {
        $root = trim((string) config('cloudinary.folders.root', 'isabi'), '/');

        $parts = array_values(array_filter(
            array_map(
                static fn ($segment) => trim((string) $segment, '/'),
                [$root, ...$segments],
            ),
            static fn (string $segment) => $segment !== '',
        ));

        return implode('/', $parts);
    }
}
