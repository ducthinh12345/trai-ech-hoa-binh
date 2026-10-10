<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_renders_seo_tags_and_product_schema(): void
    {
        $category = Category::factory()->create(['name' => 'Ếch giống']);
        $product = Product::factory()->for($category)->create([
            'name' => 'Ếch giống Thái Lan', 'price' => 180000, 'seo_description' => 'Ếch giống khoẻ, giao toàn quốc.',
        ]);

        $response = $this->get($product->url())->assertOk();

        $response->assertSee('<title>Ếch giống Thái Lan | ', false);
        $response->assertSee('<meta name="description" content="Ếch giống khoẻ, giao toàn quốc.">', false);
        $response->assertSee('<link rel="canonical" href="'.$product->url().'">', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"price":180000', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"availability":"https://schema.org/InStock"', false);
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->get($product->url())->assertNotFound();
    }

    public function test_filtered_listing_is_noindex_and_search_finds_product(): void
    {
        Product::factory()->create(['name' => 'Đùi ếch làm sạch']);
        Product::factory()->create(['name' => 'Cám viên']);

        $this->get(route('products.index', ['q' => 'làm sạch']))
            ->assertOk()
            ->assertSee('Đùi ếch làm sạch')
            ->assertDontSee('Cám viên</a>', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_sitemap_lists_published_products_only(): void
    {
        $visible = Product::factory()->create();
        $hidden = Product::factory()->create(['is_active' => false]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee($visible->url(), false)
            ->assertDontSee($hidden->url(), false);
    }

    public function test_old_slug_redirects_301(): void
    {
        $product = Product::factory()->create();
        Redirect::create(['from_path' => '/san-pham/ten-cu', 'to_url' => $product->url()]);

        $this->get('/san-pham/ten-cu')->assertRedirect($product->url())->assertStatus(301);
        $this->assertSame(1, Redirect::first()->hits);
    }

    public function test_order_page_requires_phone_lookup_for_strangers(): void
    {
        $order = Order::create([
            'customer_name' => 'A', 'customer_phone' => '0912345678', 'province_code' => 'HN', 'province_name' => 'Hà Nội',
            'ward' => 'X', 'street' => 'Y', 'subtotal' => 1000, 'total' => 1000, 'payment_method' => PaymentMethod::Cod,
        ]);

        $this->get(route('orders.show', $order))->assertNotFound();

        $this->post(route('orders.track.submit'), ['code' => strtolower($order->code), 'phone' => '0999999999'])->assertSessionHasErrors('code');
        $this->post(route('orders.track.submit'), ['code' => strtolower($order->code), 'phone' => '0912 345 678'])->assertRedirect(route('orders.show', $order));

        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->code);
    }

    public function test_changing_slug_creates_301_from_old_url(): void
    {
        $product = Product::factory()->create(['slug' => 'ech-cu']);

        $product->update(['slug' => 'ech-moi']);

        $this->get('/san-pham/ech-cu')->assertStatus(301)->assertRedirect(route('products.show', 'ech-moi'));
    }

    public function test_redirect_works_for_url_without_matching_route(): void
    {
        Redirect::create(['from_path' => 'gioi-thieu.html', 'to_url' => '/trang/gioi-thieu']);

        $this->get('/gioi-thieu.html')->assertStatus(301)->assertRedirect('/trang/gioi-thieu');
    }
}
