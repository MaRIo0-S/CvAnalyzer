<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MotCle extends Model
{
    protected $table = 'mots_cles';

    protected $fillable = [
        'user_id',
        'valeur',
        'date_creation',
    ];

    protected function casts(): array
    {
        return [
            'date_creation' => 'datetime',
        ];
    }

    public function rh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function postes(): BelongsToMany
    {
        return $this->belongsToMany(Poste::class, 'poste_mot_cle');
    }
}
