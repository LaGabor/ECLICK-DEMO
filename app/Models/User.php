<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Support\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'bank_account', 'terms_accepted_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, HasRoles, HasUuids, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole(UserRole::Admin),
            'account' => $this->hasRole(UserRole::User),
            default => false,
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function refundExportsCreated(): HasMany
    {
        return $this->hasMany(RefundExport::class, 'created_by');
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }
}
