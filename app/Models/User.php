<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Keep `role` and the legacy `is_admin` flag consistent no matter which one
     * a caller sets (factories/seeders still set is_admin; the users screen sets
     * role). `role` is the source of truth when present.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->role !== null) {
                $user->is_admin = $user->role === UserRole::ADMIN;
            } elseif ($user->is_admin) {
                $user->role = UserRole::ADMIN;
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::OPERATOR;
    }

    /** Any assigned role may enter the Hub; null = no access. */
    public function canAccessHub(): bool
    {
        return $this->role !== null;
    }

    /** Admin or manager — may add accounts, create links, export (supply tier). */
    public function canSupply(): bool
    {
        return $this->role?->canSupply() === true;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
