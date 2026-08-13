<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Support\Config;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'current_store_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /** Nama role platform-level; dipakai seeder, middleware, dan command. */
    public const SUPERADMIN_ROLE = 'superadmin';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'current_store_id');
    }

    /**
     * Superadmin adalah role PLATFORM (lintas toko), bukan role di dalam toko.
     *
     * Relasi roles() milik spatie tidak bisa menjawab ini: dengan fitur teams
     * aktif, relasi itu selalu menambah `wherePivot('team_id', team aktif)`
     * (vendor/spatie/laravel-permission/src/Traits/HasRoles.php). Begitu
     * SetCurrentStore menyetel team id ke sebuah toko, role superadmin yang
     * disimpan dengan team_id null jadi tak terlihat. Jadi kita tanya pivot
     * langsung tanpa filter team.
     */
    public function isSuperadmin(): bool
    {
        return once(fn () => $this->morphToMany(
            Config::roleModel(),
            'model',
            Config::modelHasRolesTable(),
            Config::morphKey(),
            'role_id',
        )->where('name', self::SUPERADMIN_ROLE)->exists());
    }
}
