<x-mail::message>
# Cảm ơn {{ $order->customer_name }}!

Trại đã nhận đơn hàng **{{ $order->code }}** của bạn và sẽ gọi xác nhận qua số {{ $order->customer_phone }} trong ít phút.

<x-mail::table>
| Sản phẩm | SL | Thành tiền |
|:--|:--:|--:|
@foreach ($order->items as $item)
| {{ $item->displayName() }} | {{ $item->quantity }} | {{ money($item->line_total) }} |
@endforeach
| Phí vận chuyển | | {{ $order->shipping_fee ? money($order->shipping_fee) : 'Miễn phí' }} |
@if ($order->discount_amount)
| Giảm giá ({{ $order->coupon_code }}) | | −{{ money($order->discount_amount) }} |
@endif
| **Tổng cộng** | | **{{ money($order->total) }}** |
</x-mail::table>

**Giao đến:** {{ $order->fullAddress() }}
**Thanh toán:** {{ $order->payment_method->getLabel() }}

<x-mail::button :url="route('orders.track')" color="success">
Theo dõi đơn hàng
</x-mail::button>

Cần hỗ trợ? Gọi {{ setting('store_phone') }}.

{{ setting('store_name', config('app.name')) }}
</x-mail::message>
