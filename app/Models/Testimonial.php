<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['customer_name', 'customer_title', 'content', 'avatar', 'rating', 'sort_order', 'is_active'])]
class Testimonial extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }
}
