<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id', 'name', 'slug', 'description', 'image', 'icon', 'sort_order',
    'is_active', 'show_in_menu', 'seo_title', 'seo_description',
])]
class Category extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->active()->where('show_in_menu', true)->orderBy('sort_order');
    }

    /**
     * Danh mục đang xem có nằm trong nhánh này không (chính nó hoặc một danh mục con trực tiếp).
     * Dùng để quyết định mở sẵn danh sách con và tô sáng danh mục cha.
     */
    public function isActiveBranch(?Category $current): bool
    {
        return $current !== null && ($current->id === $this->id || $current->parent_id === $this->id);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('categories.show', $this);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }
}
