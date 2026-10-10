<?php

use App\Models\Setting;

if (! function_exists('money')) {
    /**
     * Định dạng VND kiểu Việt Nam: 1.250.000đ. Dùng ở mọi nơi hiển thị giá để thống nhất.
     */
    function money(int|float|null $amount, string $suffix = 'đ'): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.').$suffix;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
