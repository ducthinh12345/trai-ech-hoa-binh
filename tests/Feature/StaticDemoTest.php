<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StaticDemoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bật chế độ demo như AppServiceProvider làm khi STATIC_DEMO=true (provider đã boot trước khi test đổi config).
     */
    private function enableStaticDemo(): void
    {
        config(['app.static_demo' => true, 'app.url' => 'https://chu-repo.github.io/trai-ech-hoa-binh']);
        URL::forceRootUrl('https://chu-repo.github.io/trai-ech-hoa-binh');
        URL::forceScheme('https');
    }

    public function test_product_page_has_no_server_features_and_uses_pages_base_url(): void
    {
        $product = Product::factory()->create(['name' => 'Ếch giống demo']);
        ProductVariant::factory()->for($product)->create(['name' => 'Túi 500 con', 'price' => 850000, 'is_default' => true]);
        $this->enableStaticDemo();

        $html = $this->get('http://localhost/san-pham/'.$product->slug)->assertOk()->getContent();

        $this->assertStringContainsString('Bảng giá theo quy cách', $html);
        $this->assertStringContainsString('Gọi đặt hàng', $html);
        $this->assertStringNotContainsString('wire:click', $html);
        $this->assertStringNotContainsString('method="POST"', $html);
        $this->assertStringNotContainsString('role="search"', $html);
        $this->assertStringContainsString('href="https://chu-repo.github.io/trai-ech-hoa-binh/san-pham"', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $html);
    }

    public function test_listing_shows_all_products_on_one_page(): void
    {
        Product::factory()->count(15)->create();
        $this->enableStaticDemo();

        $html = $this->get('http://localhost/san-pham')->assertOk()->getContent();

        $this->assertSame(15, substr_count($html, 'aria-label="Xem '));
        $this->assertStringNotContainsString('?page=2', $html);
        $this->assertStringNotContainsString('id="sort"', $html);
    }

    public function test_normal_site_is_unaffected(): void
    {
        $product = Product::factory()->create();

        $this->get('/san-pham/'.$product->slug)->assertOk()->assertSee('wire:click', false)->assertDontSee('Bản xem thử');
    }
}
