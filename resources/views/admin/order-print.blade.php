<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>In đơn hàng</title>
    <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Be Vietnam Pro', Arial, sans-serif; font-size: 12px; color: #111; margin: 0; }
        .sheet { page-break-after: always; padding: 4px; }
        .sheet:last-child { page-break-after: auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #14532d; padding-bottom: 8px; }
        .brand { font-size: 16px; font-weight: 800; color: #14532d; }
        .code { font-size: 18px; font-weight: 800; letter-spacing: .5px; text-align: right; }
        .box { border: 1px dashed #999; border-radius: 6px; padding: 8px 10px; margin-top: 10px; }
        .to { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 5px 4px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f0fdf4; font-size: 11px; }
        .r { text-align: right; }
        .total { font-size: 15px; font-weight: 800; }
        .cod { margin-top: 10px; padding: 8px; background: #fef3c7; border-radius: 6px; font-size: 14px; font-weight: 800; text-align: center; }
        .noprint { position: fixed; top: 10px; right: 10px; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body>
<button class="noprint" onclick="window.print()" style="padding:10px 18px;font-size:14px;background:#15803d;color:#fff;border:0;border-radius:8px;cursor:pointer">🖨 In ngay</button>
@foreach ($orders as $order)
    <div class="sheet">
        <div class="head">
            <div>
                <div class="brand">{{ setting('store_name') }}</div>
                <div>{{ setting('store_address') }}</div>
                <div>Hotline: {{ setting('store_phone') }}</div>
            </div>
            <div>
                <div class="code">{{ $order->code }}</div>
                <div style="text-align:right">{{ $order->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
        <div class="box to">
            <div><strong>Người nhận:</strong> {{ $order->customer_name }} — <strong>{{ $order->customer_phone }}</strong></div>
            <div><strong>Địa chỉ:</strong> {{ $order->fullAddress() }}</div>
            <div><strong>Vận chuyển:</strong> {{ $order->shipping_method_name }} @if ($order->tracking_code) · Mã VĐ: {{ $order->tracking_code }} @endif</div>
            @if ($order->customer_note)<div><strong>Ghi chú:</strong> {{ $order->customer_note }}</div>@endif
        </div>
        <table>
            <thead><tr><th>Sản phẩm</th><th class="r">Đơn giá</th><th class="r">SL</th><th class="r">Thành tiền</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->displayName() }}</td><td class="r">{{ money($item->unit_price) }}</td><td class="r">{{ $item->quantity }}</td><td class="r">{{ money($item->line_total) }}</td></tr>
            @endforeach
            <tr><td colspan="3" class="r">Tạm tính</td><td class="r">{{ money($order->subtotal) }}</td></tr>
            @if ($order->discount_amount)<tr><td colspan="3" class="r">Giảm giá ({{ $order->coupon_code }})</td><td class="r">−{{ money($order->discount_amount) }}</td></tr>@endif
            <tr><td colspan="3" class="r">Phí vận chuyển</td><td class="r">{{ money($order->shipping_fee) }}</td></tr>
            <tr><td colspan="3" class="r total">TỔNG CỘNG</td><td class="r total">{{ money($order->total) }}</td></tr>
            </tbody>
        </table>
        <div class="cod">
            @if ($order->isPaid()) ĐÃ THANH TOÁN ({{ $order->payment_method->shortLabel() }}) — KHÔNG THU TIỀN
            @else THU HỘ (COD): {{ money($order->total) }} @endif
        </div>
        <p style="text-align:center;margin-top:10px;font-size:11px">Cảm ơn quý khách! Ếch chết khi nhận vui lòng quay video và báo hotline trong 2 giờ để được bù hàng.</p>
    </div>
@endforeach
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
