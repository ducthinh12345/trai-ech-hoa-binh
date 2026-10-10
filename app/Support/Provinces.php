<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * 34 tỉnh/thành theo mô hình hành chính 2 cấp (hiệu lực từ 1/7/2025). Dữ liệu tĩnh trong resources/data
 * để không phụ thuộc API ngoài khi khách đang checkout.
 */
class Provinces
{
    /** @var Collection<int, array{code: string, name: string, region: string}>|null */
    private static ?Collection $cache = null;

    /**
     * @return Collection<int, array{code: string, name: string, region: string}>
     */
    public static function all(): Collection
    {
        return self::$cache ??= collect(json_decode(file_get_contents(resource_path('data/provinces.json')), true));
    }

    /**
     * @return array<string, string> code => name
     */
    public static function options(): array
    {
        return self::all()->pluck('name', 'code')->all();
    }

    public static function name(?string $code): ?string
    {
        return $code ? self::all()->firstWhere('code', $code)['name'] ?? null : null;
    }

    public static function exists(?string $code): bool
    {
        return $code !== null && self::all()->contains('code', $code);
    }
}
