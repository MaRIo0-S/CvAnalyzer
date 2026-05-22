<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poste extends Model
{
    protected $fillable = [
        'entreprise_id',
        'user_id',
        'titre',
        'description',
        'date_creation',
        'est_ouvert',
    ];

    protected function casts(): array
    {
        return [
            'date_creation' => 'datetime',
            'est_ouvert' => 'boolean',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(Cv::class);
    }

    public function motsCles(): BelongsToMany
    {
        return $this->belongsToMany(MotCle::class, 'poste_mot_cle');
    }
}
