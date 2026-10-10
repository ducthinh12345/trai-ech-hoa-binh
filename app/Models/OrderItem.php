<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'product_id', 'product_variant_id', 'product_name', 'variant_name', 'sku',
    'image', 'unit', 'unit_price', 'quantity', 'line_total',
])]
class OrderItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function displayName(): string
    {
        return $this->variant_name ? "{$this->product_name} – {$this->variant_name}" : $this->product_name;
    }

    public function imageUrl(): string
    {
        return $this->image ? asset('storage/'.$this->image) : asset('images/placeholder-product.svg');
    }
}
