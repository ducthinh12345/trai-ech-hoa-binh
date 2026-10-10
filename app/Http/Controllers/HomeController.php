<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\Seo;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $productQuery = fn () => Product::query()->published()->with('variants');

        $seo = Seo::make('', (string) setting('seo_default_description'))
            ->withSchema([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => setting('store_name', config('app.name')),
                'url' => url('/'),
                'logo' => asset('images/logo.svg'),
                'telephone' => setting('store_phone'),
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('store_address'), 'addressCountry' => 'VN'],
                'sameAs' => array_values(array_filter([setting('social_facebook'), setting('social_youtube'), setting('social_tiktok')])),
            ])
            ->withSchema([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'url' => url('/'),
                'name' => setting('store_name', config('app.name')),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('products.index').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ]);

        return view('home', [
            'seo' => $seo,
            'heroBanners' => Banner::query()->live(Banner::POSITION_HERO)->get(),
            'promoBanners' => Banner::query()->live(Banner::POSITION_PROMO)->limit(2)->get(),
            'categories' => Category::query()->active()->root()->orderBy('sort_order')
                ->withCount(['products' => fn ($q) => $q->published()])
                ->with(['children' => fn ($q) => $q->active()->withCount(['products' => fn ($p) => $p->published()])])
                ->get()
                ->each(fn (Category $c) => $c->products_count += $c->children->sum('products_count')),
            'featuredProducts' => $productQuery()->featured()->orderBy('sort_order')->limit(8)->get(),
            'bestSellers' => $productQuery()->orderByDesc('sold_count')->limit(8)->get(),
            'newProducts' => $productQuery()->latest()->limit(4)->get(),
            'posts' => Post::query()->published()->with('category')->limit(3)->get(),
            'testimonials' => Testimonial::query()->active()->limit(6)->get(),
        ]);
    }
}
