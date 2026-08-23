<?php

namespace App\Support;

/**
 * Builds sized Cloudinary delivery URLs.
 *
 * Uploads are stored at their original resolution, which is far too heavy to
 * drop straight into a grid — a profile with twenty jobs would pull tens of
 * megabytes. Cloudinary resizes on delivery, so we ask for exactly what each
 * surface renders and keep the untouched original for downloads.
 */
class MediaUrl
{
    private const HOST = 'res.cloudinary.com';

    public static function isCloudinary(?string $url): bool
    {
        return filled($url) && str_contains($url, self::HOST);
    }

    /**
     * Prepend a transformation component to the delivery URL. Cloudinary
     * chains components, so an already-transformed URL stays valid.
     */
    public static function transform(?string $url, string $params): ?string
    {
        if (! self::isCloudinary($url)) {
            return $url ?: null;
        }

        return preg_replace('#/upload/#', "/upload/{$params}/", $url, 1);
    }

    /**
     * A resized image capped to $width, letting Cloudinary pick the best
     * format and quality for the requesting browser.
     */
    public static function image(?string $url, int $width): ?string
    {
        return self::transform($url, "f_auto,q_auto,w_{$width},c_limit");
    }

    /**
     * A still frame from the start of a video, used as a poster so grids
     * don't have to fetch video metadata to show something.
     */
    public static function videoPoster(?string $url, int $width): ?string
    {
        if (! self::isCloudinary($url)) {
            return null;
        }

        $poster = self::transform($url, "so_0,f_jpg,q_auto,w_{$width},c_limit");

        return preg_replace('#\.[a-z0-9]+(\?.*)?$#i', '.jpg', (string) $poster);
    }
}
