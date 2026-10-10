<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        /* Cache 1 giờ: sitemap bị bot gọi thường xuyên, dữ liệu không cần tức thời. */
        $xml = Cache::remember('sitemap.xml', 3600, function (): string {
            $urls = collect([
                ['loc' => url('/'), 'lastmod' => now(), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('products.index'), 'lastmod' => now(), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('posts.index'), 'lastmod' => now(), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['loc' => route('contact'), 'lastmod' => null, 'priority' => '0.5', 'changefreq' => 'monthly'],
            ]);

            Category::query()->active()->get()->each(fn ($c) => $urls->push(['loc' => $c->url(), 'lastmod' => $c->updated_at, 'priority' => '0.8', 'changefreq' => 'weekly']));
            Product::query()->published()->get(['id', 'slug', 'updated_at', 'images'])->each(fn ($p) => $urls->push([
                'loc' => $p->url(), 'lastmod' => $p->updated_at, 'priority' => '0.8', 'changefreq' => 'weekly', 'images' => $p->imageUrls(),
            ]));
            PostCategory::query()->get()->each(fn ($c) => $urls->push(['loc' => $c->url(), 'lastmod' => $c->updated_at, 'priority' => '0.5', 'changefreq' => 'weekly']));
            Post::query()->published()->get(['id', 'slug', 'updated_at'])->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at, 'priority' => '0.6', 'changefreq' => 'monthly']));
            Page::query()->published()->get()->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at, 'priority' => '0.4', 'changefreq' => 'yearly']));

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction() && ! setting('seo_noindex_site', false)
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /gio-hang', 'Disallow: /thanh-toan', 'Disallow: /tai-khoan', 'Disallow: /don-hang/', 'Disallow: /*?*sort=', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
