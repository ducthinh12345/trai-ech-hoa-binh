<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $seo = Seo::make('Kiến thức nuôi ếch & món ngon', 'Kỹ thuật nuôi ếch thương phẩm, chọn ếch giống, công thức chế biến thịt ếch và tin tức từ trại.')
            ->withBreadcrumbs([['name' => 'Trang chủ', 'url' => url('/')], ['name' => 'Tin tức', 'url' => route('posts.index')]]);

        return view('posts.index', [
            'seo' => $seo,
            'posts' => Post::query()->published()->with('category')->paginate(self::perPage(9)),
            'category' => null,
            'categories' => PostCategory::query()->orderBy('sort_order')->withCount(['posts' => fn ($q) => $q->published()])->get(),
        ]);
    }

    public function category(PostCategory $postCategory): View
    {
        $seo = Seo::make($postCategory->seo_title ?: $postCategory->name, $postCategory->seo_description ?: (string) $postCategory->description)
            ->withBreadcrumbs([
                ['name' => 'Trang chủ', 'url' => url('/')],
                ['name' => 'Tin tức', 'url' => route('posts.index')],
                ['name' => $postCategory->name, 'url' => $postCategory->url()],
            ]);

        return view('posts.index', [
            'seo' => $seo,
            'posts' => $postCategory->posts()->published()->with('category')->paginate(self::perPage(9)),
            'category' => $postCategory,
            'categories' => PostCategory::query()->orderBy('sort_order')->withCount(['posts' => fn ($q) => $q->published()])->get(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at->isPast()), 404);

        $post->load(['category', 'author']);
        $post->increment('views_count');

        $crumbs = [['name' => 'Trang chủ', 'url' => url('/')], ['name' => 'Tin tức', 'url' => route('posts.index')]];
        if ($post->category) {
            $crumbs[] = ['name' => $post->category->name, 'url' => $post->category->url()];
        }
        $crumbs[] = ['name' => $post->title, 'url' => $post->url()];

        $seo = Seo::make($post->seoTitle(), $post->seoDescription())
            ->withBreadcrumbs($crumbs)
            ->withSchema([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'description' => $post->seoDescription(),
                'image' => [$post->coverImageUrl()],
                'datePublished' => ($post->published_at ?? $post->created_at)->toIso8601String(),
                'dateModified' => $post->updated_at->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? setting('store_name', config('app.name'))],
                'publisher' => ['@type' => 'Organization', 'name' => setting('store_name', config('app.name')), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.svg')]],
                'mainEntityOfPage' => $post->url(),
            ]);
        $seo->image = $post->coverImageUrl();
        $seo->type = 'article';

        $related = Post::query()->published()->whereKeyNot($post->id)
            ->when($post->post_category_id, fn ($q) => $q->where('post_category_id', $post->post_category_id))
            ->limit(3)->get();

        return view('posts.show', compact('seo', 'post', 'related'));
    }

    /**
     * Bản demo tĩnh không phân trang được bằng query string nên hiện tất cả trên một trang.
     */
    private static function perPage(int $default): int
    {
        return config('app.static_demo') ? 1000 : $default;
    }
}
