<?php

namespace App\Support;

/**
 * Dữ liệu SEO của một trang, layout đọc để in title, meta, Open Graph, canonical và JSON-LD.
 */
final class Seo
{
    /**
     * @param  array<int, array<string, mixed>>  $schemas  Các khối JSON-LD (Product, BreadcrumbList, Article...)
     * @param  array<int, array{name: string, url: string|null}>  $breadcrumbs
     */
    public function __construct(
        public string $title = '',
        public string $description = '',
        public ?string $canonical = null,
        public ?string $image = null,
        public string $type = 'website',
        public bool $noindex = false,
        public array $schemas = [],
        public array $breadcrumbs = [],
    ) {}

    public static function make(string $title = '', string $description = ''): self
    {
        return new self($title, $description);
    }

    public function fullTitle(): string
    {
        $store = (string) setting('store_name', config('app.name'));

        if ($this->title === '') {
            return trim($store.' – '.setting('store_tagline', ''), ' –');
        }

        return str($this->title)->limit(60, '')->toString().' | '.$store;
    }

    public function metaDescription(): string
    {
        $text = $this->description !== '' ? $this->description : (string) setting('seo_default_description', '');

        return str(strip_tags($text))->squish()->limit(160)->toString();
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?? url()->current();
    }

    public function imageUrl(): string
    {
        return $this->image ?? asset('images/og-default.jpg');
    }

    /**
     * @param  array<int, array{name: string, url: string|null}>  $items
     */
    public function withBreadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;

        return $this;
    }

    public function withSchema(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /**
     * Gom tất cả JSON-LD của trang, kèm BreadcrumbList khi có breadcrumb.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allSchemas(): array
    {
        $schemas = $this->schemas;

        if ($this->breadcrumbs !== []) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($this->breadcrumbs)->values()->map(fn (array $crumb, int $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'] ?? null,
                ]))->all(),
            ];
        }

        return $schemas;
    }
}
