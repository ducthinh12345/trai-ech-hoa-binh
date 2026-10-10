<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Cài đặt cửa hàng dạng key-value, cache toàn bộ một lần vì được đọc ở mọi request (header, footer, SEO).
 */
#[Fillable(['group', 'key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return array<string, mixed>
     */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return static::query()->pluck('value', 'key')
                ->map(fn ($value) => is_array($value) && array_key_exists('__scalar', $value) ? $value['__scalar'] : $value)
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allValues()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * Giá trị scalar được bọc trong {"__scalar": ...} để cột JSON lưu được chuỗi/số/bool thuần.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => is_array($value) ? $value : ['__scalar' => $value]],
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values, string $group): void
    {
        foreach ($values as $key => $value) {
            static::set($key, $value, $group);
        }
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
