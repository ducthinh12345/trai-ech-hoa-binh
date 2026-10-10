<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Xuất các trang xem được thành HTML tĩnh cho GitHub Pages.
 *
 * Lấy trang qua HTTP từ server đang chạy với STATIC_DEMO=true (thay vì gọi kernel trong cùng tiến trình)
 * để mỗi trang được dựng độc lập như khi khách truy cập thật. Mỗi URL lưu thành thư mục/index.html
 * để đường dẫn đẹp (/san-pham/ten) chạy được trên host tĩnh.
 */
class ExportStaticSite extends Command
{
    protected $signature = 'site:export
        {--source=http://127.0.0.1:8000 : Server đang chạy bản STATIC_DEMO để lấy HTML}
        {--out=dist : Thư mục xuất}';

    protected $description = 'Xuất website thành HTML tĩnh (bản demo cho GitHub Pages)';

    public function handle(): int
    {
        if (! config('app.static_demo')) {
            $this->error('Cần STATIC_DEMO=true để URL sinh theo APP_URL và ẩn tính năng cần server.');

            return self::FAILURE;
        }

        $source = rtrim((string) $this->option('source'), '/');
        $out = base_path((string) $this->option('out'));
        $basePath = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');

        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);

        $paths = $this->paths();
        $assets = [];

        foreach ($paths as $path) {
            $html = $this->fetch($source.$path, 200);
            $assets = [...$assets, ...$this->localAssets($html, $basePath)];
            $this->write($out, $path, $this->rewriteRootRelative($html, $basePath));
        }

        /* Trang 404 riêng: GitHub Pages tự dùng 404.html cho mọi URL không tồn tại. */
        File::put($out.'/404.html', $this->rewriteRootRelative($this->fetch($source.'/__khong-ton-tai__', 404), $basePath));
        File::put($out.'/sitemap.xml', $this->fetch($source.'/sitemap.xml', 200));

        $this->copyAssets($source, $out, array_unique($assets), $basePath);
        File::put($out.'/.nojekyll', '');

        $this->info(sprintf('Đã xuất %d trang và %d file tài nguyên vào %s', count($paths) + 1, count(array_unique($assets)), $out));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function paths(): array
    {
        $paths = ['/', '/san-pham', '/tin-tuc', '/lien-he'];

        foreach (Category::query()->active()->get() as $category) {
            $paths[] = '/danh-muc/'.$category->slug;
        }
        foreach (Product::query()->published()->get(['id', 'slug']) as $product) {
            $paths[] = '/san-pham/'.$product->slug;
        }
        foreach (PostCategory::query()->get(['slug']) as $postCategory) {
            $paths[] = '/tin-tuc/chuyen-muc/'.$postCategory->slug;
        }
        foreach (Post::query()->published()->get(['id', 'slug']) as $post) {
            $paths[] = '/tin-tuc/'.$post->slug;
        }
        foreach (Page::query()->published()->get(['slug']) as $page) {
            $paths[] = '/trang/'.$page->slug;
        }

        return $paths;
    }

    private function fetch(string $url, int $expectedStatus): string
    {
        $response = Http::timeout(30)->withoutRedirecting()->get($url);

        if ($response->status() !== $expectedStatus) {
            throw new RuntimeException("{$url} trả {$response->status()}, mong đợi {$expectedStatus}.");
        }

        return $response->body();
    }

    private function write(string $out, string $path, string $html): void
    {
        $target = $path === '/' ? $out.'/index.html' : $out.$path.'/index.html';
        File::ensureDirectoryExists(dirname($target));
        File::put($target, $html);
    }

    /**
     * Link gốc tương đối trong nội dung admin nhập (vd. banner trỏ "/danh-muc/ech-giong") phải có tiền tố
     * đường dẫn con của Pages. URL do Laravel sinh đã tuyệt đối theo APP_URL nên không bị ảnh hưởng.
     */
    private function rewriteRootRelative(string $html, string $basePath): string
    {
        if ($basePath === '') {
            return $html;
        }

        $html = (string) preg_replace('#\b(href|src|action)="/(?!/)#', '$1="'.$basePath.'/', $html);

        /* Livewire nối root URL vào đường dẫn đã mang sẵn tiền tố đường dẫn con (data-update-uri): gộp phần lặp. */
        $html = str_replace($basePath.$basePath.'/', $basePath.'/', $html);

        /* Font khai báo bằng url(/build/...) trong <style> nội tuyến và file CSS. */
        return (string) preg_replace('#url\((["\']?)/(?!/)#', 'url($1'.$basePath.'/', $html);
    }

    /**
     * Tài nguyên cùng site được trang tham chiếu (build, ảnh upload, livewire.js...), trả về đường dẫn
     * tính từ gốc site, chưa có tiền tố basePath.
     *
     * @return array<int, string>
     */
    private function localAssets(string $html, string $basePath): array
    {
        $root = rtrim((string) config('app.url'), '/');
        preg_match_all('#(?:href|src|content)="'.preg_quote($root, '#').'(/[^"?\#]+\.(?:css|js|woff2?|svg|png|jpe?g|webp|gif|ico|xml))(?:\?[^"]*)?"#i', $html, $matches);

        return $matches[1];
    }

    /**
     * @param  array<int, string>  $assets
     */
    private function copyAssets(string $source, string $out, array $assets, string $basePath): void
    {
        foreach ($assets as $asset) {
            $local = public_path(ltrim($asset, '/'));
            $target = $out.$asset;
            File::ensureDirectoryExists(dirname($target));

            /* File trong public/ (kể cả symlink storage) chép thẳng; route động như livewire.js thì tải về. */
            if (is_file($local)) {
                File::copy($local, $target);

                continue;
            }

            File::put($target, $this->fetch($source.$asset, 200));
        }

        /* Chép cả thư mục build (font, chunk JS) rồi sửa url(/build/...) trong CSS theo đường dẫn con. */
        File::copyDirectory(public_path('build'), $out.'/build');
        foreach (File::glob($out.'/build/assets/*.css') as $css) {
            File::put($css, $this->rewriteRootRelative(File::get($css), $basePath));
        }
    }
}
