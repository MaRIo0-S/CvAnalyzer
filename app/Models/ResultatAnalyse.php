<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultatAnalyse extends Model
{
    protected $table = 'resultats_analyse';

    protected $fillable = [
        'cv_id',
        'score_matching',
        'mots_cles_matches',
        'nombre_matches',
        'date_analyse',
    ];

    protected function casts(): array
    {
        return [
            'mots_cles_matches' => 'array',
            'date_analyse' => 'datetime',
            'score_matching' => 'float',
        ];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }
}
