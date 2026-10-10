<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'phone', 'email', 'subject', 'message', 'is_read', 'ip_address'])]
class ContactMessage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
