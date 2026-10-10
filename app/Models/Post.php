<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'post_category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'cover_image',
    'is_published', 'is_featured', 'published_at', 'seo_title', 'seo_description',
])]
class Post extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('posts.show', $this);
    }

    public function coverImageUrl(): string
    {
        return $this->cover_image ? asset('storage/'.$this->cover_image) : asset('images/placeholder-post.svg');
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        return $this->seo_description ?: (string) str($this->excerpt ?: strip_tags((string) $this->content))->limit(155);
    }
}
