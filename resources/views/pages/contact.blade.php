<x-layouts.shop :seo="$seo">
    <div class="container-x py-8 lg:py-12">
        <div class="max-w-2xl">
            <p class="section-eyebrow">Liên hệ</p>
            <h1 class="section-title mt-1.5">Trại luôn sẵn sàng hỗ trợ bạn</h1>
            <p class="mt-3 text-ink/70">Đặt hàng sỉ, tư vấn kỹ thuật, tham quan trại — gọi điện là nhanh nhất, hoặc để lại lời nhắn bên dưới.</p>
        </div>
        <div class="mt-10 grid gap-8 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-2">
                @foreach ([
                    ['icon' => 'phone', 'label' => 'Hotline / Zalo', 'value' => setting('store_phone'), 'href' => 'tel:'.preg_replace('/\D/', '', setting('store_phone', ''))],
                    ['icon' => 'envelope', 'label' => 'Email', 'value' => setting('store_email'), 'href' => 'mailto:'.setting('store_email')],
                    ['icon' => 'map-pin', 'label' => 'Địa chỉ trại', 'value' => setting('store_address'), 'href' => null],
                    ['icon' => 'clock', 'label' => 'Giờ làm việc', 'value' => setting('store_working_hours'), 'href' => null],
                ] as $c)
                    @if ($c['value'])
                        <div class="card flex items-start gap-4 p-5">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-dynamic-component :component="'heroicon-o-'.$c['icon']" class="size-6" /></span>
                            <div><p class="text-sm text-gray-500">{{ $c['label'] }}</p>
                                @if ($c['href'])<a href="{{ $c['href'] }}" class="font-bold text-brand-900 hover:text-brand-600">{{ $c['value'] }}</a>@else<p class="font-bold text-brand-900">{{ $c['value'] }}</p>@endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            @staticdemo
            <div class="card flex flex-col items-center justify-center gap-4 p-8 text-center lg:col-span-3">
                <p class="text-lg font-bold">Liên hệ nhanh nhất qua điện thoại hoặc Zalo</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="tel:{{ preg_replace('/\D/', '', setting('store_phone', '')) }}" class="btn-primary btn-lg"><x-heroicon-s-phone class="size-5" /> {{ setting('store_phone') }}</a>
                    <a href="https://zalo.me/{{ preg_replace('/\D/', '', (string) setting('store_zalo', setting('store_phone'))) }}" target="_blank" rel="noopener" class="btn-outline btn-lg">Nhắn Zalo</a>
                </div>
            </div>
            @else
            <form action="{{ route('contact.send') }}" method="POST" class="card grid gap-4 p-6 sm:grid-cols-2 sm:p-8 lg:col-span-3">
                @csrf
                <div class="hidden"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div><label for="c-name" class="label">Họ tên *</label><input id="c-name" name="name" value="{{ old('name') }}" required class="input">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="c-phone" class="label">Số điện thoại *</label><input id="c-phone" name="phone" type="tel" value="{{ old('phone') }}" required class="input">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="c-email" class="label">Email</label><input id="c-email" name="email" type="email" value="{{ old('email') }}" class="input"></div>
                <div><label for="c-subject" class="label">Chủ đề</label>
                    <select id="c-subject" name="subject" class="input">
                        @foreach (['Đặt hàng sỉ', 'Tư vấn kỹ thuật nuôi', 'Tham quan trại', 'Khiếu nại / đổi trả', 'Khác'] as $s)<option @selected(old('subject') === $s)>{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2"><label for="c-msg" class="label">Nội dung *</label><textarea id="c-msg" name="message" rows="5" required class="input">{{ old('message') }}</textarea>@error('message')<p class="field-error">{{ $message }}</p>@enderror</div>
                <button class="btn-primary btn-lg sm:col-span-2">Gửi liên hệ</button>
            </form>
            @endstaticdemo
        </div>

        @if ($map = setting('store_map_embed'))
            <div class="card mt-10 overflow-hidden"><iframe src="{{ $map }}" class="h-96 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ trại"></iframe></div>
        @endif

        @if ($faqs->isNotEmpty())
            <section class="mx-auto mt-16 max-w-3xl">
                <h2 class="section-title text-center">Câu hỏi thường gặp</h2>
                <div class="mt-8 space-y-3">
                    @foreach ($faqs as $faq)
                        <details class="card group p-5 [&_summary::-webkit-details-marker]:hidden">
                            <summary class="flex cursor-pointer items-center justify-between gap-4 font-bold text-brand-950">{{ $faq->question }}<x-heroicon-m-chevron-down class="size-5 shrink-0 text-brand-500 transition group-open:rotate-180" /></summary>
                            <div class="prose-content mt-3">{!! $faq->answer !!}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.shop>
