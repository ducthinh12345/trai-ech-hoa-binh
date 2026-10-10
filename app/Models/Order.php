<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'code', 'user_id', 'customer_name', 'customer_phone', 'customer_email',
    'province_code', 'province_name', 'ward', 'street', 'customer_note',
    'shipping_method_id', 'shipping_method_name', 'subtotal', 'discount_amount', 'coupon_code',
    'shipping_fee', 'total', 'payment_method', 'payment_status', 'status',
    'paid_at', 'confirmed_at', 'shipped_at', 'completed_at', 'cancelled_at', 'cancel_reason',
    'tracking_code', 'admin_note', 'ip_address',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->code ??= static::generateCode();
        });
    }

    /**
     * Mã đơn dạng TE-YYMMDD-XXXX: dễ đọc qua điện thoại, phần đuôi ngẫu nhiên tránh đoán số đơn.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'TE-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('id');
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Paid);
    }

    /**
     * Doanh thu ghi nhận: đơn không bị huỷ. (Đơn COD chưa thu tiền vẫn tính là doanh thu dự kiến.)
     */
    public function scopeRevenue(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([$this->street, $this->ward, $this->province_name]));
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Confirmed], true) && ! $this->isPaid();
    }

    /**
     * Đơn online chưa trả tiền có thể thanh toán lại (khách thoát giữa chừng).
     */
    public function canRetryPayment(): bool
    {
        return $this->payment_method->isOnlineGateway()
            && ! $this->isPaid()
            && ! $this->isCancelled();
    }
}
