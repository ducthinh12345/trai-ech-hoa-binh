<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'recipient_name', 'phone', 'province_code', 'province_name', 'ward', 'street', 'is_default'])]
class Address extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([$this->street, $this->ward, $this->province_name]));
    }
}
