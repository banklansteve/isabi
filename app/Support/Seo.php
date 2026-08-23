<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Collects per-request SEO metadata and renders it into the Blade shell.
 *
 * This deliberately lives server-side rather than in Inertia's <Head>. Social
 * crawlers don't execute JavaScript at all, and Googlebot only does so on a
 * second pass, so anything that decides whether a page is indexed — title,
 * description, canonical, robots, structured data — has to be in the first
 * HTML response regardless of whether the SSR process happens to be running.
 */
class Seo
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    private ?string $image = null;

    private string $type = 'website';

    private bool $noindex = false;

    /** @var list<array<string, mixed>> */
    private array $schemas = [];

    public function title(?string $title): static
    {
        $this->title = $this->clean($title);

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $this->truncate($this->clean($description), 158);

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function image(?string $url): static
    {
        if (filled($url)) {
            $this->image = $url;
        }

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function noindex(bool $noindex = true): static
    {
        $this->noindex = $noindex;

        return $this;
    }

    /** @param array<string, mixed> $schema */
    public function schema(array $schema): static
    {
        $this->schemas[] = $schema;

        return $this;
    }

    public function resolvedTitle(): string
    {
        $name = config('app.name', 'Isabi');

        if (blank($this->title)) {
            return $name.' — proof of work for skilled trades';
        }

        // Pages that already carry the brand shouldn't get it twice.
        return str_contains($this->title, $name)
            ? $this->title
            : $this->title.' · '.$name;
    }

    public function resolvedDescription(): string
    {
        return $this->description
            ?: 'Isabi gives Nigerian artisans a public page built from real finished jobs and reviews written by the clients themselves.';
    }

    public function resolvedCanonical(): string
    {
        return $this->canonical ?: url()->current();
    }

    public function resolvedImage(): ?string
    {
        return $this->image;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->resolvedTitle(),
            'description' => $this->resolvedDescription(),
            'canonical' => $this->resolvedCanonical(),
            'image' => $this->resolvedImage(),
            'noindex' => $this->noindex,
        ];
    }

    /**
     * The full <head> block. Escaped here rather than in Blade so callers can
     * pass raw model values without thinking about it.
     */
    public function render(): string
    {
        $tags = [];

        $title = e($this->resolvedTitle());
        $description = e($this->resolvedDescription());
        $canonical = e($this->resolvedCanonical());
        $image = $this->image ? e($this->image) : '';
        $siteName = e(config('app.name', 'Isabi'));

        $tags[] = '<meta name="description" content="'.$description.'">';
        $tags[] = '<link rel="canonical" href="'.$canonical.'">';

        $tags[] = $this->noindex
            ? '<meta name="robots" content="noindex, nofollow">'
            : '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">';

        $tags[] = '<meta property="og:type" content="'.e($this->type).'">';
        $tags[] = '<meta property="og:site_name" content="'.$siteName.'">';
        $tags[] = '<meta property="og:title" content="'.$title.'">';
        $tags[] = '<meta property="og:description" content="'.$description.'">';
        $tags[] = '<meta property="og:url" content="'.$canonical.'">';
        $tags[] = '<meta property="og:locale" content="en_NG">';

        if ($this->image) {
            $tags[] = '<meta property="og:image" content="'.$image.'">';
            $tags[] = '<meta name="twitter:image" content="'.$image.'">';
        }

        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="'.$title.'">';
        $tags[] = '<meta name="twitter:description" content="'.$description.'">';

        foreach ($this->schemas as $schema) {
            $json = json_encode(
                $schema,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
            );

            if ($json !== false) {
                $tags[] = '<script type="application/ld+json">'.$json.'</script>';
            }
        }

        return implode("\n        ", $tags);
    }

    private function clean(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
    }

    private function truncate(?string $value, int $limit): ?string
    {
        return blank($value) ? null : Str::limit($value, $limit, '…');
    }
}
