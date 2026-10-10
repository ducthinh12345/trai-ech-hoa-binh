<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Sinh ảnh minh hoạ SVG cho dữ liệu mẫu, để site trông hoàn chỉnh ngay khi cài.
 * Chủ trại thay bằng ảnh chụp thật trong trang quản trị.
 */
class DemoArt
{
    /** @var array<string, array{0: string, 1: string, 2: string}> nền nhạt, nền đậm, màu ếch */
    private const PALETTES = [
        'green' => ['#dcfce7', '#16a34a', '#65a30d'],
        'teal' => ['#ccfbf1', '#0f766e', '#4d7c0f'],
        'amber' => ['#fef3c7', '#b45309', '#65a30d'],
        'rose' => ['#ffe4e6', '#be123c', '#f9a8d4'],
        'sky' => ['#e0f2fe', '#0369a1', '#4ade80'],
        'lime' => ['#ecfccb', '#4d7c0f', '#15803d'],
        'stone' => ['#f5f5f4', '#57534e', '#a16207'],
    ];

    public static function product(string $path, string $label, string $palette = 'green', string $motif = 'frog', int $variant = 0): string
    {
        [$bg, $accent, $frog] = self::PALETTES[$palette] ?? self::PALETTES['green'];
        $art = self::motif($motif, $frog, $accent);
        $rot = [-6, 4, -2, 7][$variant % 4];
        $labelBar = $label === '' ? '' : '<rect x="60" y="690" width="680" height="64" rx="32" fill="'.$accent.'"/><text x="400" y="733" font-family="Be Vietnam Pro, Arial, sans-serif" font-size="30" font-weight="700" fill="#fff" text-anchor="middle">'.e($label).'</text>';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800">
<defs><radialGradient id="g" cx="50%" cy="40%" r="75%"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="{$bg}"/></radialGradient></defs>
<rect width="800" height="800" fill="url(#g)"/>
<circle cx="660" cy="140" r="90" fill="{$accent}" opacity=".08"/><circle cx="120" cy="680" r="140" fill="{$accent}" opacity=".07"/>
<ellipse cx="400" cy="610" rx="250" ry="34" fill="{$accent}" opacity=".12"/>
<g transform="rotate({$rot} 400 400)">{$art}</g>
{$labelBar}
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    public static function banner(string $path, string $palette = 'green', string $motif = 'frog'): string
    {
        [$bg, $accent, $frog] = self::PALETTES[$palette] ?? self::PALETTES['green'];
        $art = self::motif($motif, $frog, $accent);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 600" preserveAspectRatio="xMidYMid slice">
<defs><linearGradient id="b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#052e16"/><stop offset="1" stop-color="{$accent}"/></linearGradient></defs>
<rect width="1600" height="600" fill="url(#b)"/>
<g opacity=".12" fill="#fff"><circle cx="1350" cy="80" r="160"/><circle cx="1500" cy="520" r="120"/><circle cx="900" cy="600" r="200"/></g>
<g fill="{$bg}" opacity=".25"><ellipse cx="1150" cy="520" rx="120" ry="30"/><ellipse cx="1400" cy="470" rx="90" ry="24"/></g>
<g transform="translate(830 40) scale(1.05)">{$art}</g>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    public static function cover(string $path, string $title, string $palette = 'green', string $motif = 'frog'): string
    {
        [$bg, $accent, $frog] = self::PALETTES[$palette] ?? self::PALETTES['green'];
        $art = self::motif($motif, $frog, $accent);
        $title = e(mb_strimwidth($title, 0, 42, '…'));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 750" preserveAspectRatio="xMidYMid slice">
<rect width="1200" height="750" fill="{$bg}"/>
<path d="M0 520 C300 440 600 600 1200 480 V750 H0z" fill="{$accent}" opacity=".18"/>
<g transform="translate(560 60) scale(.9)">{$art}</g>
<text x="60" y="660" font-family="Be Vietnam Pro, Arial, sans-serif" font-size="44" font-weight="800" fill="{$accent}">{$title}</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    /**
     * Các hoạ tiết chính: ếch, nòng nọc, đùi ếch, bao cám, chả ếch. Toạ độ thiết kế trong khung 800x600.
     */
    private static function motif(string $motif, string $frog, string $accent): string
    {
        return match ($motif) {
            'tadpole' => <<<SVG
<g fill="{$frog}">
<g transform="translate(220 260)"><ellipse rx="70" ry="56"/><path d="M60 0 C160 -40 200 60 300 10 C210 80 150 10 60 20z"/><circle cx="-30" cy="-14" r="10" fill="#fff"/><circle cx="-32" cy="-14" r="5" fill="#111"/></g>
<g transform="translate(430 420) scale(.8) rotate(-18)"><ellipse rx="70" ry="56"/><path d="M60 0 C160 -40 200 60 300 10 C210 80 150 10 60 20z"/><circle cx="-30" cy="-14" r="10" fill="#fff"/><circle cx="-32" cy="-14" r="5" fill="#111"/></g>
<g transform="translate(420 180) scale(.6) rotate(12)"><ellipse rx="70" ry="56"/><path d="M60 0 C160 -40 200 60 300 10 C210 80 150 10 60 20z"/><circle cx="-30" cy="-14" r="10" fill="#fff"/><circle cx="-32" cy="-14" r="5" fill="#111"/></g>
</g>
<g fill="{$accent}" opacity=".25"><circle cx="160" cy="470" r="14"/><circle cx="600" cy="300" r="10"/><circle cx="640" cy="160" r="16"/></g>
SVG,
            'legs' => <<<'SVG'
<g transform="translate(400 330)">
<g fill="#fbcfe8" stroke="#db2777" stroke-width="6" stroke-linejoin="round">
<path d="M-40 -150 C-120 -120 -150 -20 -120 60 C-100 120 -150 170 -200 200 L-170 230 C-110 210 -60 160 -60 90 C-50 20 -10 -60 -10 -140z"/>
<path d="M40 -150 C120 -120 150 -20 120 60 C100 120 150 170 200 200 L170 230 C110 210 60 160 60 90 C50 20 10 -60 10 -140z"/>
</g>
<ellipse cx="0" cy="-160" rx="70" ry="40" fill="#f9a8d4" stroke="#db2777" stroke-width="6"/>
<g fill="#16a34a"><path d="M-180 -160 q20 -40 60 -30 q-20 30 -60 30z"/><path d="M170 -190 q30 -20 50 10 q-30 20 -50 -10z"/></g>
</g>
SVG,
            'bag' => <<<SVG
<g transform="translate(400 330)">
<path d="M-170 -210 h340 l30 420 h-400z" fill="#fef3c7" stroke="{$accent}" stroke-width="10" stroke-linejoin="round"/>
<path d="M-170 -210 q170 40 340 0" fill="none" stroke="{$accent}" stroke-width="10"/>
<rect x="-130" y="-120" width="260" height="170" rx="20" fill="{$accent}"/>
<g transform="translate(-60 -110) scale(1.9)" fill="#bbf7d0"><path d="M10 44c4-9 12-14 22-14s18 5 22 14c-5 6-13 9-22 9s-17-3-22-9z"/><circle cx="21" cy="25" r="8"/><circle cx="43" cy="25" r="8"/></g>
<g fill="{$accent}"><circle cx="-90" cy="120" r="10"/><circle cx="-50" cy="140" r="10"/><circle cx="-10" cy="120" r="10"/><circle cx="30" cy="140" r="10"/><circle cx="70" cy="120" r="10"/></g>
</g>
SVG,
            'cake' => <<<'SVG'
<g transform="translate(400 340)">
<ellipse cx="0" cy="120" rx="280" ry="60" fill="#fff" stroke="#e7e5e4" stroke-width="6"/>
<g fill="#d97706" stroke="#92400e" stroke-width="5"><ellipse cx="-110" cy="60" rx="110" ry="60"/><ellipse cx="90" cy="40" rx="120" ry="64"/><ellipse cx="0" cy="-30" rx="110" ry="58"/></g>
<g fill="#fbbf24" opacity=".6"><ellipse cx="-130" cy="44" rx="40" ry="14"/><ellipse cx="70" cy="22" rx="46" ry="16"/><ellipse cx="-20" cy="-46" rx="40" ry="14"/></g>
<g fill="#16a34a"><path d="M150 -60 q40 -60 90 -30 q-40 40 -90 30z"/><path d="M-220 0 q-20 -50 20 -70 q20 40 -20 70z"/></g>
<circle cx="200" cy="80" r="16" fill="#dc2626"/>
</g>
SVG,
            'farm' => <<<SVG
<g transform="translate(0 40)">
<path d="M0 360 C150 300 300 330 420 310 S700 280 800 320 V600 H0z" fill="{$frog}" opacity=".5"/>
<ellipse cx="400" cy="470" rx="380" ry="110" fill="{$accent}" opacity=".55"/>
<g fill="{$frog}"><ellipse cx="230" cy="450" rx="60" ry="18"/><ellipse cx="520" cy="490" rx="70" ry="20"/></g>
<g transform="translate(470 380) scale(1.8)" fill="{$frog}"><path d="M10 44c4-9 12-14 22-14s18 5 22 14c-5 6-13 9-22 9s-17-3-22-9z"/><circle cx="21" cy="25" r="8"/><circle cx="43" cy="25" r="8"/><circle cx="21" cy="25" r="4" fill="#fff"/><circle cx="43" cy="25" r="4" fill="#fff"/></g>
<circle cx="640" cy="120" r="60" fill="#fde047"/>
</g>
SVG,
            default => <<<SVG
<g transform="translate(400 330)">
<g fill="{$frog}">
<path d="M-150 120 C-220 140 -260 100 -250 60 C-230 90 -200 100 -160 90z"/>
<path d="M150 120 C220 140 260 100 250 60 C230 90 200 100 160 90z"/>
<ellipse cx="0" cy="40" rx="190" ry="140"/>
<circle cx="-95" cy="-100" r="70"/><circle cx="95" cy="-100" r="70"/>
</g>
<ellipse cx="0" cy="90" rx="120" ry="70" fill="#fff" opacity=".45"/>
<g fill="#000" opacity=".12"><circle cx="-60" cy="10" r="16"/><circle cx="70" cy="-10" r="12"/><circle cx="20" cy="40" r="10"/><circle cx="-120" cy="60" r="10"/></g>
<circle cx="-95" cy="-100" r="40" fill="#fff"/><circle cx="95" cy="-100" r="40" fill="#fff"/>
<circle cx="-85" cy="-95" r="21" fill="#111"/><circle cx="85" cy="-95" r="21" fill="#111"/>
<circle cx="-78" cy="-103" r="7" fill="#fff"/><circle cx="92" cy="-103" r="7" fill="#fff"/>
<path d="M-70 -10 Q0 40 70 -10" stroke="#111" stroke-width="8" fill="none" stroke-linecap="round" opacity=".55"/>
<g fill="{$frog}"><ellipse cx="-130" cy="160" rx="50" ry="22"/><ellipse cx="130" cy="160" rx="50" ry="22"/></g>
</g>
SVG,
        };
    }
}
