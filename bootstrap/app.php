<?php

use App\Models\Redirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /* Cổng thanh toán gọi IPN server-to-server, không có CSRF token. */
        $middleware->preventRequestForgery(except: ['payment/momo/ipn']);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Redirect 301 cho URL cũ (đổi slug, link từ site cũ). Đặt ở đây thay vì middleware để bắt được cả
         * URL không khớp route nào lẫn 404 do route model binding; request bình thường không tốn truy vấn nào.
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->isMethod('GET')) {
                return null;
            }

            $redirect = Redirect::findForPath($request->path());
            if (! $redirect) {
                return null;
            }

            $redirect->increment('hits');

            return redirect($redirect->to_url, $redirect->status_code);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
