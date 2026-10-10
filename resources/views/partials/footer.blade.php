@php
    $footerPages = \Illuminate\Support\Facades\Cache::remember('footer.pages', 600, fn () => \App\Models\Page::query()->published()->where('show_in_footer', true)->orderBy('sort_order')->get(['title', 'slug'])->map(fn ($p) => ['title' => $p->title, 'url' => $p->url()])->all());
    $footerCategories = \Illuminate\Support\Facades\Cache::remember('footer.categories', 600, fn () => \App\Models\Category::query()->active()->root()->orderBy('sort_order')->limit(6)->get(['name', 'slug'])->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all());
@endphp
<footer class="relative mt-20 overflow-hidden bg-brand-950 text-brand-100">
    <svg class="pointer-events-none absolute -top-px left-0 w-full text-cream" viewBox="0 0 1440 48" fill="currentColor" preserveAspectRatio="none" aria-hidden="true"><path d="M0 0h1440v16c-180 24-360 32-540 24S540 8 360 12 120 32 0 28z"/></svg>

    <div class="container-x relative pt-20 pb-10">
        <div class="mb-12 grid items-center gap-6 rounded-3xl bg-brand-900/60 p-6 ring-1 ring-white/10 sm:p-8 lg:grid-cols-2">
            <div>
                <h2 class="text-xl font-extrabold text-white sm:text-2xl">Nhận bảng giá ếch giống & ưu đãi mỗi tuần</h2>
                <p class="mt-1 text-sm text-brand-200">Giá ếch biến động theo mùa — đăng ký để nhận giá tốt nhất từ trại.</p>
            </div>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex gap-2">
                @csrf
                <label for="newsletter-email" class="sr-only">Email</label>
                <input id="newsletter-email" type="email" name="email" required placeholder="Email của bạn" class="input flex-1 rounded-full bg-white/95">
                <button class="btn-accent shrink-0">Đăng ký</button>
            </form>
        </div>

        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="size-12" width="48" height="48" loading="lazy">
                    <span class="text-xl font-extrabold text-white">{{ setting('store_name', config('app.name')) }}</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-6 text-brand-200">{{ setting('store_description', 'Trại ếch giống và ếch thương phẩm. Cung cấp ếch giống khoẻ, ếch thịt tươi sống và thịt ếch làm sạch giao tận nơi.') }}</p>
                <div class="mt-5 flex gap-2">
                    @foreach (['social_facebook' => 'Facebook', 'social_youtube' => 'YouTube', 'social_tiktok' => 'TikTok'] as $key => $label)
                        @if ($url = setting($key))
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white/10 text-xs font-bold text-white transition hover:bg-accent-400 hover:text-brand-950" aria-label="{{ $label }}">{{ mb_substr($label, 0, 2) }}</a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2">
                <h3 class="text-sm font-bold tracking-wider text-white uppercase">Sản phẩm</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($footerCategories as $cat)
                        <li><a href="{{ $cat['url'] }}" class="text-brand-200 transition hover:text-accent-300">{{ $cat['name'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="lg:col-span-2">
                <h3 class="text-sm font-bold tracking-wider text-white uppercase">Hỗ trợ</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('orders.track') }}" class="text-brand-200 hover:text-accent-300">Tra cứu đơn hàng</a></li>
                    <li><a href="{{ route('posts.index') }}" class="text-brand-200 hover:text-accent-300">Kiến thức nuôi ếch</a></li>
                    <li><a href="{{ route('contact') }}" class="text-brand-200 hover:text-accent-300">Liên hệ & FAQ</a></li>
                    @foreach ($footerPages as $page)
                        <li><a href="{{ $page['url'] }}" class="text-brand-200 hover:text-accent-300">{{ $page['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="lg:col-span-4">
                <h3 class="text-sm font-bold tracking-wider text-white uppercase">Liên hệ trại</h3>
                <ul class="mt-4 space-y-3.5 text-sm text-brand-200">
                    @if ($address = setting('store_address'))
                        <li class="flex gap-3"><x-heroicon-o-map-pin class="size-5 shrink-0 text-accent-400" /><span>{{ $address }}</span></li>
                    @endif
                    <li class="flex gap-3"><x-heroicon-o-phone class="size-5 shrink-0 text-accent-400" /><a href="tel:{{ preg_replace('/\D/', '', setting('store_phone', '')) }}" class="font-semibold text-white hover:text-accent-300">{{ setting('store_phone') }}</a></li>
                    @if ($email = setting('store_email'))
                        <li class="flex gap-3"><x-heroicon-o-envelope class="size-5 shrink-0 text-accent-400" /><a href="mailto:{{ $email }}" class="hover:text-accent-300">{{ $email }}</a></li>
                    @endif
                    @if ($hours = setting('store_working_hours'))
                        <li class="flex gap-3"><x-heroicon-o-clock class="size-5 shrink-0 text-accent-400" /><span>{{ $hours }}</span></li>
                    @endif
                </ul>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @foreach (['COD', 'VietQR', 'VNPay', 'MoMo'] as $pm)
                        <span class="rounded-md bg-white px-2.5 py-1 text-[11px] font-extrabold text-brand-900">{{ $pm }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-brand-300 sm:flex-row">
            <p>© {{ date('Y') }} {{ setting('store_name', config('app.name')) }}. {{ setting('business_license', '') }}</p>
            <p>Ếch sạch – nuôi đúng quy trình – giao đúng hẹn.</p>
        </div>
    </div>
</footer>
