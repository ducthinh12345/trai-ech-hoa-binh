<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'images',
    'price', 'compare_at_price', 'unit', 'min_order_quantity', 'track_stock', 'stock_quantity',
    'low_stock_threshold', 'specifications', 'is_active', 'is_featured', 'sort_order',
    'seo_title', 'seo_description', 'published_at',
])]
class Product extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'specifications' => 'array',
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'min_order_quantity' => 'integer',
            'track_stock' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'rating_average' => 'float',
            'rating_count' => 'integer',
            'views_count' => 'integer',
            'sold_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Sản phẩm hiển thị ngoài shop: đang bật và đã tới ngày đăng (hoặc không hẹn ngày).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('track_stock', false)->orWhere('stock_quantity', '>', 0));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('short_description', 'like', $like)
            ->orWhere('sku', 'like', $like));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('products.show', $this);
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)->isNotEmpty()
            : $this->activeVariants()->exists();
    }

    public function defaultVariant(): ?ProductVariant
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        $active = $variants->where('is_active', true);

        return $active->firstWhere('is_default', true) ?? $active->first();
    }

    /**
     * Giá hiển thị ở danh sách: giá biến thể mặc định nếu có, không thì giá gốc.
     */
    public function displayPrice(): int
    {
        return $this->defaultVariant()?->price ?? $this->price;
    }

    public function displayCompareAtPrice(): ?int
    {
        return $this->hasVariants() ? $this->defaultVariant()?->compare_at_price : $this->compare_at_price;
    }

    public function discountPercent(): ?int
    {
        $compare = $this->displayCompareAtPrice();
        $price = $this->displayPrice();

        if (! $compare || $compare <= $price) {
            return null;
        }

        return (int) round(($compare - $price) / $compare * 100);
    }

    public function isInStock(): bool
    {
        if (! $this->track_stock) {
            return true;
        }

        if ($this->hasVariants()) {
            return $this->variants->where('is_active', true)->contains(fn (ProductVariant $v) => $v->stock_quantity > 0);
        }

        return $this->stock_quantity > 0;
    }

    /**
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return array_map(fn (string $path) => asset('storage/'.$path), $this->images ?? []);
    }

    public function thumbnailUrl(): string
    {
        $images = $this->images ?? [];

        return $images ? asset('storage/'.$images[0]) : asset('images/placeholder-product.svg');
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->name;
    }

    public function seoDescription(): string
    {
        return $this->seo_description ?: (string) str($this->short_description ?: strip_tags((string) $this->description))->limit(155);
    }

    /**
     * Tính lại điểm trung bình từ review đã duyệt; gọi khi duyệt/xoá review.
     */
    public function recalculateRating(): void
    {
        $stats = $this->approvedReviews()->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();

        $this->forceFill([
            'rating_count' => (int) $stats->total,
            'rating_average' => round((float) $stats->average, 2),
        ])->saveQuietly();
    }
}
