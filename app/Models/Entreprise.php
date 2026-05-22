<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'description_updated_by',
        'description_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'description_updated_at' => 'datetime',
        ];
    }

    public function descriptionUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'description_updated_by');
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class);
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class);
    }

    public function sousAdmins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::SousAdmin);
    }

    public function scopeAvecRh(Builder $query): Builder
    {
        return $query->whereHas('sousAdmins');
    }
}
