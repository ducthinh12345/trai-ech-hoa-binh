<?php

/*
 * Khoá cổng thanh toán. Ưu tiên giá trị trong bảng settings (admin nhập), rỗng thì dùng .env.
 * URL sandbox là mặc định để môi trường dev không vô tình tạo giao dịch thật.
 */
return [
    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'expire_minutes' => 15,
    ],
    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'request_type' => env('MOMO_REQUEST_TYPE', 'captureWallet'),
    ],
];
