<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'name', 'email', 'telephone', 'password', 'role', 'est_actif',
    'etat_cascade_snapshot', 'admin_id', 'super_admin_id', 'entreprise_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'est_actif' => 'boolean',
            'etat_cascade_snapshot' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /** Gérants créés par l’admin plateforme */
    public function superAdmins(): HasMany
    {
        return $this->hasMany(User::class, 'admin_id')
            ->where('role', Role::SuperAdmin);
    }

    /** RH gérés par un super-admin */
    public function rhEquipe(): HasMany
    {
        return $this->hasMany(User::class, 'super_admin_id')
            ->where('role', Role::SousAdmin);
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class);
    }

    public function motsCles(): HasMany
    {
        return $this->hasMany(MotCle::class);
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isSousAdmin(): bool
    {
        return $this->role === Role::SousAdmin;
    }

    public function isCandidat(): bool
    {
        return $this->role === Role::Candidat;
    }
}
