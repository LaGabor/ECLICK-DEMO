<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    public function isAnswered(): bool
    {
        if ($this->replied_at !== null) {
            return true;
        }

        $reply = $this->admin_reply;

        return is_string($reply) && trim($reply) !== '';
    }

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'admin_reply',
        'replied_at',
        'replied_by',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
