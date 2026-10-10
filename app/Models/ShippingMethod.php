<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'fee', 'free_from_amount', 'province_codes', 'estimated_days', 'is_active', 'sort_order'])]
class ShippingMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'free_from_amount' => 'integer',
            'province_codes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function appliesToProvince(string $provinceCode): bool
    {
        return empty($this->province_codes) || in_array($provinceCode, $this->province_codes, true);
    }

    public function isNationwide(): bool
    {
        return empty($this->province_codes);
    }

    public function feeFor(int $subtotal): int
    {
        if ($this->free_from_amount !== null && $subtotal >= $this->free_from_amount) {
            return 0;
        }

        return $this->fee;
    }
}
