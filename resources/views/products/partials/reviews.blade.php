<div class="grid gap-10 lg:grid-cols-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl bg-brand-50 p-6 text-center">
            <p class="text-5xl font-extrabold text-brand-800">{{ number_format($product->rating_average, 1) }}</p>
            <x-stars :rating="$product->rating_average" size="size-5" class="mt-2" />
            <p class="mt-1 text-sm text-gray-500">{{ $product->rating_count }} đánh giá</p>
        </div>
        <form action="{{ route('reviews.store', $product) }}" method="POST" class="mt-6 space-y-4" x-data="{ rating: {{ old('rating', 5) }} }">
            @csrf
            <h3 class="font-bold">Viết đánh giá của bạn</h3>
            <div>
                <span class="label">Chấm điểm</span>
                <div class="flex gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" aria-label="{{ $i }} sao">
                            <svg class="size-8 transition" :class="rating >= {{ $i }} ? 'text-accent-400' : 'text-gray-200'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/></svg>
                        </button>
                    @endfor
                </div>
                <input type="hidden" name="rating" :value="rating">
            </div>
            <div class="hidden"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div><input type="text" name="reviewer_name" value="{{ old('reviewer_name', auth()->user()?->name) }}" placeholder="Họ tên *" required class="input">@error('reviewer_name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><textarea name="content" rows="4" placeholder="Chia sẻ trải nghiệm của bạn (tối thiểu 10 ký tự) *" required class="input">{{ old('content') }}</textarea>@error('content')<p class="field-error">{{ $message }}</p>@enderror</div>
            <button class="btn-primary w-full">Gửi đánh giá</button>
        </form>
    </div>
    <div class="lg:col-span-3">
        @forelse ($reviews as $review)
            <article class="border-b border-brand-900/5 py-5 first:pt-0">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-full bg-brand-100 font-bold text-brand-800">{{ mb_strtoupper(mb_substr($review->reviewer_name, 0, 1)) }}</span>
                    <div>
                        <p class="flex items-center gap-2 font-semibold">{{ $review->reviewer_name }} @if ($review->order_id)<span class="badge bg-brand-50 text-brand-700"><x-heroicon-s-check-badge class="size-3.5" />Đã mua hàng</span>@endif</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500"><x-stars :rating="$review->rating" size="size-3.5" />{{ $review->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <p class="mt-3 text-[15px] leading-7 text-ink/80">{{ $review->content }}</p>
                @if ($review->admin_reply)
                    <div class="mt-3 rounded-xl bg-brand-50 p-4 text-sm"><p class="font-semibold text-brand-800">Phản hồi từ trại:</p><p class="mt-1 text-ink/80">{{ $review->admin_reply }}</p></div>
                @endif
            </article>
        @empty
            <p class="py-10 text-center text-gray-500">Chưa có đánh giá. Hãy là người đầu tiên!</p>
        @endforelse
    </div>
</div>
