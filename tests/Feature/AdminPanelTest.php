<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([SettingSeeder::class, CatalogSeeder::class, ContentSeeder::class, DemoOrderSeeder::class]);
    }

    public static function adminUrls(): array
    {
        return array_map(fn ($u) => [$u], [
            '/admin', '/admin/orders', '/admin/products', '/admin/products/create', '/admin/categories', '/admin/coupons',
            '/admin/shipping-methods', '/admin/reviews', '/admin/posts', '/admin/posts/create', '/admin/post-categories',
            '/admin/pages', '/admin/banners', '/admin/faqs', '/admin/testimonials', '/admin/users', '/admin/contact-messages',
            '/admin/newsletter-subscribers', '/admin/redirects', '/admin/settings',
        ]);
    }

    #[DataProvider('adminUrls')]
    public function test_admin_can_open_every_admin_page(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
    }

    public function test_admin_can_open_product_edit_and_order_view(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(ProductResource::getUrl('edit', ['record' => Product::first()]))->assertOk()->assertSee(Product::first()->name);
        $order = Order::first();
        $this->actingAs($admin)->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk()->assertSee($order->code);
        $this->actingAs($admin)->get(route('admin.orders.print', ['ids' => $order->id]))->assertOk()->assertSee($order->customer_phone);
    }

    public function test_customer_cannot_enter_admin_and_staff_cannot_open_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.orders.print', ['ids' => 1]))->assertForbidden();

        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get('/admin/orders')->assertOk();
        $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
        $this->actingAs($staff)->get('/admin/redirects')->assertForbidden();
    }

    public function test_view_order_actions_move_status_and_mark_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::query()->where('status', OrderStatus::Pending)->first()
            ?? tap(Order::first())->update(['status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Unpaid]);
        $order->update(['payment_method' => 'bank_transfer', 'payment_status' => PaymentStatus::Unpaid]);

        $this->actingAs($admin);
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('mark_paid')
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->assertActionHidden('ship')->assertActionVisible('process');

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->callAction('process');
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->callAction('ship', data: ['tracking_code' => 'GHN123']);

        $order->refresh();
        $this->assertSame(OrderStatus::Shipping, $order->status);
        $this->assertSame('GHN123', $order->tracking_code);
    }

    public function test_settings_page_saves_values_to_correct_groups(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Settings::class)
            ->assertSet('data.general.store_name', 'Trại Ếch Hòa Bình')
            ->assertSet('data.home.stat_years', '10+')
            ->set('data.general.store_phone', '0987 654 321')
            ->set('data.home.about_title', 'Trại ếch số 1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0987 654 321', setting('store_phone'));
        $this->assertSame('Trại ếch số 1', setting('about_title'));
        $this->assertSame('home', Setting::where('key', 'about_title')->value('group'));
    }
}
