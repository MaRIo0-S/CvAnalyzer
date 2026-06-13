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

    /** Super administrateur plateforme créateur (colonne admin_id sur le gérant). */
    public function platformSuperAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** @deprecated Utiliser platformSuperAdmin() */
    public function admin(): BelongsTo
    {
        return $this->platformSuperAdmin();
    }

    /** Gérant d'entreprise responsable (colonne super_admin_id sur le RH). */
    public function gerant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    /** @deprecated Utiliser gerant() */
    public function superAdmin(): BelongsTo
    {
        return $this->gerant();
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /** Gérants créés par ce super administrateur plateforme */
    public function gerants(): HasMany
    {
        return $this->hasMany(User::class, 'admin_id')
            ->where('role', Role::Admin);
    }

    /** RH gérés par un gérant d'entreprise */
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

    public function isGerant(): bool
    {
        return $this->role === Role::Admin;
    }

    /** @deprecated Utiliser isGerant() */
    public function isAdmin(): bool
    {
        return $this->isGerant();
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
