<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['from_path', 'to_url', 'status_code', 'hits', 'is_active'])]
class Redirect extends Model
{
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Redirect $redirect) => $redirect->from_path = '/'.ltrim(trim($redirect->from_path), '/'));
    }

    public static function findForPath(string $path): ?self
    {
        return static::query()->where('is_active', true)->where('from_path', '/'.ltrim($path, '/'))->first();
    }

    /**
     * Ghi redirect khi slug đổi, để link cũ đã được Google index vẫn dẫn về đúng trang.
     */
    public static function recordSlugChange(string $oldPath, string $newUrl): void
    {
        if ($oldPath === parse_url($newUrl, PHP_URL_PATH)) {
            return;
        }

        static::updateOrCreate(['from_path' => $oldPath], ['to_url' => $newUrl, 'status_code' => 301, 'is_active' => true]);
    }
}
