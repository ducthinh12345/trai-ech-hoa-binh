<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        $seo = Seo::make($page->seo_title ?: $page->title, $page->seo_description ?: strip_tags((string) $page->content))
            ->withBreadcrumbs([['name' => 'Trang chủ', 'url' => url('/')], ['name' => $page->title, 'url' => $page->url()]]);

        return view('pages.show', compact('seo', 'page'));
    }

    public function contact(): View
    {
        $faqs = Faq::query()->active()->get();

        $seo = Seo::make('Liên hệ', 'Liên hệ trại ếch để đặt hàng sỉ, tư vấn kỹ thuật nuôi ếch hoặc tham quan trại.')
            ->withBreadcrumbs([['name' => 'Trang chủ', 'url' => url('/')], ['name' => 'Liên hệ', 'url' => route('contact')]]);

        if ($faqs->isNotEmpty()) {
            $seo->withSchema([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn (Faq $faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq->answer)],
                ])->values()->all(),
            ]);
        }

        return view('pages.contact', compact('seo', 'faqs'));
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9\s.\-]{8,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
            'website' => ['prohibited'],
        ], [], ['name' => 'họ tên', 'phone' => 'số điện thoại', 'message' => 'nội dung']);

        unset($data['website']);
        ContactMessage::create($data + ['ip_address' => $request->ip()]);

        return back()->with('success', 'Đã gửi liên hệ. Trại sẽ gọi lại cho bạn trong giờ làm việc.');
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])], ['is_active' => true]);

        return back()->with('success', 'Đăng ký nhận tin thành công!');
    }
}
