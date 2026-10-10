<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Tự sinh slug tiếng Việt không dấu từ cột nguồn khi để trống, và đảm bảo không trùng.
 * Đổi slug thì tự ghi redirect 301 từ URL cũ để không mất thứ hạng Google và link khách đã chia sẻ.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $source = $model->getAttribute($model->slugSourceColumn());

            if (blank($model->slug) && filled($source)) {
                $model->slug = Str::slug($source);
            }

            if (filled($model->slug)) {
                $model->slug = static::uniqueSlug(Str::slug($model->slug), $model->getKey());
            }
        });

        /* Trong sự kiện "updated", getOriginal() vẫn giữ giá trị cũ (chưa sync). */
        static::updated(function (Model $model): void {
            $oldSlug = $model->getOriginal('slug');

            if (! $model->wasChanged('slug') || blank($oldSlug) || ! method_exists($model, 'url')) {
                return;
            }

            $newUrl = $model->url();
            $newPath = (string) parse_url($newUrl, PHP_URL_PATH);
            $oldPath = preg_replace('#/'.preg_quote($model->slug, '#').'$#', '/'.$oldSlug, $newPath);

            Redirect::recordSlugChange($oldPath, $newUrl);
        });
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    protected static function uniqueSlug(string $base, mixed $ignoreId = null): string
    {
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->withoutGlobalScopes()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
