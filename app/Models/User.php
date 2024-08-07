<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kra8\Snowflake\HasSnowflakePrimary;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia, HasRoles, HasSnowflakePrimary;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!auth()->user()->is_active) {
            
            auth()->logout();

            redirect()->to($panel->getLoginUrl());

            Notification::make()
                ->title('Akun Anda Dinonaktifkan')
                ->body('Silahkan hubungi admin untuk mengaktifkan kembali akun Anda.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar')
            ->width(50)
            ->height(50);
        
        $this
            ->addMediaConversion('thumb')
            ->width(500)
            ->height(400);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->hasMedia('profile')) {
            return $this->getFirstMediaUrl('profile', 'avatar');
        }

        $name = str($this->name)
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join(' ');

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=FFFFFF&background=111827&font-size=0.33';
    }

    public function canImpersonate()
    {
        return $this->can('User:loginAs');
    }

    public function canBeImpersonated()
    {
        if (!$this->is_active) {
            return false;
        }

        return true;
    }
}
