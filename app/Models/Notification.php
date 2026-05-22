<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'cv_id',
        'message',
        'date_envoi',
        'statut_au_moment',
        'lu',
    ];

    protected function casts(): array
    {
        return [
            'date_envoi' => 'datetime',
            'lu' => 'boolean',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }
}
