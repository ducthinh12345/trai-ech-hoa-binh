<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin\OrderPrintController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/* URL tiếng Việt không dấu: tốt cho SEO nội địa và dễ đọc khi khách chia sẻ link. */

Route::get('/', HomeController::class)->name('home');

Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
Route::get('/san-pham/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/danh-muc/{category}', [ProductController::class, 'category'])->name('categories.show');
Route::post('/san-pham/{product}/danh-gia', [ReviewController::class, 'store'])->middleware('throttle:5,10')->name('reviews.store');

Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::get('/thanh-toan', [CartController::class, 'checkout'])->name('checkout');

Route::get('/tra-cuu-don-hang', [OrderController::class, 'trackForm'])->name('orders.track');
Route::post('/tra-cuu-don-hang', [OrderController::class, 'track'])->middleware('throttle:10,1')->name('orders.track.submit');
Route::get('/don-hang/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/don-hang/{order}/thanh-toan', [OrderController::class, 'pay'])->name('orders.pay');
Route::post('/don-hang/{order}/huy', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('payment.vnpay.ipn');
Route::get('/payment/momo/return', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::post('/payment/momo/ipn', [PaymentController::class, 'momoIpn'])->name('payment.momo.ipn');

Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/chuyen-muc/{postCategory}', [PostController::class, 'category'])->name('posts.category');
Route::get('/tin-tuc/{post}', [PostController::class, 'show'])->name('posts.show');

Route::get('/lien-he', [PageController::class, 'contact'])->name('contact');
Route::post('/lien-he', [PageController::class, 'sendContact'])->middleware('throttle:5,10')->name('contact.send');
Route::post('/dang-ky-nhan-tin', [PageController::class, 'subscribe'])->middleware('throttle:5,10')->name('newsletter.subscribe');
Route::get('/trang/{page}', [PageController::class, 'show'])->name('pages.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,10')->name('register.submit');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('tai-khoan')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/thong-tin', [AccountController::class, 'profile'])->name('profile');
    Route::put('/thong-tin', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/mat-khau', [AccountController::class, 'updatePassword'])->name('password.update');
    Route::get('/dia-chi', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/dia-chi', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/dia-chi/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::get('/yeu-thich', [AccountController::class, 'wishlist'])->name('wishlist');
    Route::post('/yeu-thich/{product}', [AccountController::class, 'toggleWishlist'])->name('wishlist.toggle');
});

Route::get('/admin/in-don-hang', OrderPrintController::class)->middleware('auth')->name('admin.orders.print');
