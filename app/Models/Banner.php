<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'subtitle', 'image', 'link', 'button_text', 'position', 'sort_order', 'is_active', 'starts_at', 'ends_at'])]
class Banner extends Model
{
    use HasFactory;

    public const POSITION_HERO = 'hero';

    public const POSITION_PROMO = 'promo';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeLive(Builder $query, string $position): Builder
    {
        return $query->where('is_active', true)
            ->where('position', $position)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image);
    }
}
