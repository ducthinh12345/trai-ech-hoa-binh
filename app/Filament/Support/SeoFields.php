<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Khối SEO dùng chung cho sản phẩm, danh mục, bài viết, trang. Có khung xem trước kết quả Google
 * để người không rành SEO vẫn thấy ngay tiêu đề/mô tả có bị cắt hay không.
 */
class SeoFields
{
    /**
     * @param  string  $titleField  Cột dùng làm tiêu đề mặc định khi để trống seo_title
     * @param  string  $descriptionField  Cột dùng làm mô tả mặc định khi để trống seo_description
     * @param  string  $urlPrefix  Đường dẫn trước slug, ví dụ "san-pham"
     */
    public static function section(string $titleField = 'name', string $descriptionField = 'short_description', string $urlPrefix = ''): Section
    {
        return Section::make('Hiển thị trên Google')
            ->description('Không bắt buộc. Để trống thì hệ thống tự lấy tên và mô tả ngắn.')
            ->icon('heroicon-o-magnifying-glass')
            ->collapsible()
            ->schema([
                Text::make(fn (Get $get) => self::preview($get, $titleField, $descriptionField, $urlPrefix)),
                TextInput::make('seo_title')
                    ->label('Tiêu đề SEO')
                    ->maxLength(70)
                    ->live(onBlur: true)
                    ->helperText('Nên dài 50–60 ký tự, có từ khoá chính, ví dụ: "Ếch giống Thái Lan giá tại trại".'),
                Textarea::make('seo_description')
                    ->label('Mô tả SEO')
                    ->rows(3)
                    ->maxLength(320)
                    ->live(onBlur: true)
                    ->helperText('Nên dài 120–160 ký tự, mô tả lợi ích và kêu gọi hành động.'),
            ]);
    }

    private static function preview(Get $get, string $titleField, string $descriptionField, string $urlPrefix): HtmlString
    {
        $store = (string) setting('store_name', config('app.name'));
        $title = $get('seo_title') ?: $get($titleField) ?: 'Tiêu đề trang';
        $description = $get('seo_description') ?: strip_tags((string) $get($descriptionField)) ?: 'Mô tả sẽ hiện ở đây…';
        $slug = $get('slug') ?: Str::slug((string) $get($titleField));
        $url = rtrim(config('app.url'), '/').($urlPrefix ? '/'.$urlPrefix : '').'/'.$slug;

        $fullTitle = Str::limit($title, 60, '…').' | '.$store;
        $titleLength = mb_strlen($title);
        $descLength = mb_strlen($description);
        $titleHint = $titleLength > 60 ? '⚠ Tiêu đề hơi dài, Google có thể cắt bớt' : '✓ Độ dài tiêu đề tốt';
        $descHint = $descLength < 70 ? '⚠ Mô tả hơi ngắn' : ($descLength > 160 ? '⚠ Mô tả dài, sẽ bị cắt' : '✓ Độ dài mô tả tốt');

        return new HtmlString(sprintf(
            '<div style="border:1px solid rgb(0 0 0 / .08);border-radius:12px;padding:14px 16px;background:#fff;font-family:Arial,sans-serif">'
            .'<div style="font-size:12px;color:#202124">%s</div>'
            .'<div style="font-size:18px;color:#1a0dab;line-height:1.3;margin:2px 0">%s</div>'
            .'<div style="font-size:13px;color:#4d5156;line-height:1.5">%s</div></div>'
            .'<div style="font-size:12px;color:#6b7280;margin-top:6px">%s · %s</div>',
            e($url), e($fullTitle), e(Str::limit($description, 160, '…')), $titleHint, $descHint,
        ));
    }
}
