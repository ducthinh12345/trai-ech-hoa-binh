<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** @var array<string, string> */
    public const SORTS = [
        'popular' => 'Phổ biến nhất',
        'newest' => 'Mới nhất',
        'price_asc' => 'Giá thấp → cao',
        'price_desc' => 'Giá cao → thấp',
        'rating' => 'Đánh giá cao',
    ];

    public function index(Request $request): View
    {
        $products = $this->filteredQuery($request)->paginate(self::perPage(12))->withQueryString();
        $term = trim((string) $request->query('q'));

        $seo = Seo::make($term !== '' ? "Tìm kiếm: {$term}" : 'Tất cả sản phẩm', 'Mua ếch giống, ếch thịt, ếch sống và thịt ếch làm sạch chất lượng, giao tận nơi toàn quốc.')
            ->withBreadcrumbs([
                ['name' => 'Trang chủ', 'url' => url('/')],
                ['name' => 'Sản phẩm', 'url' => route('products.index')],
            ]);

        /* Trang lọc/tìm kiếm có tham số không index để tránh nội dung trùng lặp; canonical về trang gốc. */
        if ($request->hasAny(['q', 'sort', 'min', 'max', 'in_stock']) || $request->integer('page') > 1) {
            $seo->noindex = $request->filled('q') || $request->hasAny(['sort', 'min', 'max', 'in_stock']);
            $seo->canonical = route('products.index');
        }

        return view('products.index', [
            'seo' => $seo,
            'products' => $products,
            'category' => null,
            'categories' => $this->sidebarCategories(),
            'sorts' => self::SORTS,
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $categoryIds = $category->children()->where('is_active', true)->pluck('id')->push($category->id);
        $products = $this->filteredQuery($request)->whereIn('category_id', $categoryIds)->paginate(self::perPage(12))->withQueryString();

        $crumbs = [['name' => 'Trang chủ', 'url' => url('/')], ['name' => 'Sản phẩm', 'url' => route('products.index')]];
        if ($category->parent) {
            $crumbs[] = ['name' => $category->parent->name, 'url' => $category->parent->url()];
        }
        $crumbs[] = ['name' => $category->name, 'url' => $category->url()];

        $seo = Seo::make($category->seo_title ?: $category->name, $category->seo_description ?: (string) $category->description)
            ->withBreadcrumbs($crumbs);
        $seo->image = $category->imageUrl();
        $seo->canonical = $category->url();
        $seo->noindex = $request->hasAny(['sort', 'min', 'max', 'in_stock']);

        return view('products.index', [
            'seo' => $seo,
            'products' => $products,
            'category' => $category,
            'categories' => $this->sidebarCategories(),
            'sorts' => self::SORTS,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active && (! $product->published_at || $product->published_at->isPast()), 404);

        $product->load(['category.parent', 'variants' => fn ($q) => $q->where('is_active', true)]);
        $product->increment('views_count');

        $reviews = $product->approvedReviews()->limit(20)->get();
        $related = Product::query()->published()->with('variants')
            ->where('category_id', $product->category_id)->whereKeyNot($product->id)
            ->orderByDesc('sold_count')->limit(4)->get();

        $crumbs = [['name' => 'Trang chủ', 'url' => url('/')]];
        if ($product->category) {
            $crumbs[] = ['name' => $product->category->name, 'url' => $product->category->url()];
        }
        $crumbs[] = ['name' => $product->name, 'url' => $product->url()];

        $seo = Seo::make($product->seoTitle(), $product->seoDescription())
            ->withBreadcrumbs($crumbs)
            ->withSchema($this->productSchema($product, $reviews));
        $seo->image = $product->thumbnailUrl();
        $seo->type = 'product';
        $seo->canonical = $product->url();

        return view('products.show', compact('seo', 'product', 'reviews', 'related'));
    }

    /**
     * Schema Product với Offer/AggregateOffer để Google hiển thị giá, tình trạng kho và sao đánh giá.
     */
    private function productSchema(Product $product, $reviews): array
    {
        $availability = $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $variants = $product->variants;

        $offers = $variants->isNotEmpty()
            ? [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'VND',
                'lowPrice' => $variants->min('price'),
                'highPrice' => $variants->max('price'),
                'offerCount' => $variants->count(),
                'availability' => $availability,
            ]
            : [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => $product->price,
                'availability' => $availability,
                'url' => $product->url(),
                'itemCondition' => 'https://schema.org/NewCondition',
            ];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->seoDescription(),
            'image' => $product->imageUrls() ?: [$product->thumbnailUrl()],
            'sku' => $product->sku,
            'brand' => ['@type' => 'Brand', 'name' => setting('store_name', config('app.name'))],
            'offers' => $offers,
        ];

        if ($product->rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->rating_average,
                'reviewCount' => $product->rating_count,
            ];
            $schema['review'] = $reviews->take(5)->map(fn ($review) => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review->reviewer_name],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review->rating],
                'reviewBody' => $review->content,
                'datePublished' => $review->created_at->toDateString(),
            ])->values()->all();
        }

        return array_filter($schema);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Product::query()->published()->with('variants')->search($request->query('q'));

        if ($request->filled('min')) {
            $query->where('price', '>=', $request->integer('min'));
        }
        if ($request->filled('max')) {
            $query->where('price', '<=', $request->integer('max'));
        }
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        return match ($request->query('sort', 'popular')) {
            'newest' => $query->latest(),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sold_count')->orderBy('sort_order'),
        };
    }

    private function sidebarCategories()
    {
        return Category::query()->active()->root()->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['products' => fn ($q) => $q->published()])
            ->get()
            ->each(fn (Category $c) => $c->products_count += $c->children()->active()->withCount(['products' => fn ($q) => $q->published()])->get()->sum('products_count'));
    }

    /**
     * Bản demo tĩnh không phân trang được bằng query string nên hiện tất cả trên một trang.
     */
    private static function perPage(int $default): int
    {
        return config('app.static_demo') ? 1000 : $default;
    }
}
