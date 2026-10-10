<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'gateway', 'transaction_ref', 'gateway_transaction_id', 'amount', 'status',
    'response_code', 'message', 'request_payload', 'response_payload',
])]
class PaymentTransaction extends Model
{
    public const STATUS_INITIATED = 'initiated';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
