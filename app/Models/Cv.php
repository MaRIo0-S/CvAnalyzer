<?php

namespace App\Models;

use App\Enums\StatutCv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cv extends Model
{
    protected $table = 'cvs';

    protected $fillable = [
        'poste_id',
        'entreprise_id',
        'user_id',
        'nom_candidat',
        'email_candidat',
        'fichier_url',
        'taille_fichier',
        'date_depot',
        'format_fichier',
        'texte_extrait',
        'statut',
        'modifiable_jusqu',
    ];

    protected function casts(): array
    {
        return [
            'date_depot' => 'datetime',
            'modifiable_jusqu' => 'datetime',
            'statut' => StatutCv::class,
            'taille_fichier' => 'float',
        ];
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resultatAnalyse(): HasOne
    {
        return $this->hasOne(ResultatAnalyse::class, 'cv_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function peutModifier(): bool
    {
        return $this->modifiable_jusqu !== null && now()->lt($this->modifiable_jusqu);
    }

    public function pretPourAnalyse(): bool
    {
        return $this->modifiable_jusqu === null || $this->modifiable_jusqu <= now();
    }

    public function scopePretPourAnalyse($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('modifiable_jusqu')
                ->orWhere('modifiable_jusqu', '<=', now());
        });
    }

    public static function libelleAvecDecision(?string $decisionProvisoire, string $libelleDefaut): string
    {
        return match ($decisionProvisoire) {
            'valide' => 'Validé (à confirmer)',
            'non_valide' => 'Refusé (à confirmer)',
            default => $libelleDefaut,
        };
    }

    public static function statutAffichage(?string $decisionProvisoire, string $statutDb): string
    {
        return match ($decisionProvisoire) {
            'valide' => 'valide',
            'non_valide' => 'non_valide',
            default => $statutDb,
        };
    }

    public function pourRh(?string $decisionProvisoire = null, bool $avecEntreprise = false): array
    {
        $analyse = $this->donneesAnalyseAffichage();
        $statut = $this->statut->value;

        $row = [
            'id' => $this->id,
            'poste_id' => $this->poste_id,
            'nom_candidat' => $this->nom_candidat ?: 'Candidat #'.$this->id,
            'email_candidat' => $this->email_candidat,
            'poste' => $this->poste?->titre,
            'statut' => $statut,
            'statut_affichage' => self::statutAffichage($decisionProvisoire, $statut),
            'statut_label' => self::libelleAvecDecision($decisionProvisoire, $this->statut->label()),
            'decision_provisoire' => $decisionProvisoire,
            'date_depot' => $this->date_depot?->format('d/m/Y H:i'),
            'date_depot_ts' => $this->date_depot?->timestamp ?? 0,
            'format_fichier' => $this->format_fichier,
            'score' => $analyse['score'],
            'nombre_matches' => $analyse['nombre_matches'],
            'mots_cles_matches' => $this->statut === StatutCv::CvRecu
                ? []
                : ($this->resultatAnalyse?->mots_cles_matches ?? []),
            'date_analyse' => $analyse['date_analyse'],
            'date_analyse_ts' => $analyse['date_analyse_ts'],
            'download_url' => route('rh.cv.telecharger', $this),
            'peut_analyser' => $this->pretPourAnalyse(),
        ];

        if ($avecEntreprise) {
            $row['entreprise'] = $this->entreprise?->nom;
        }

        return $row;
    }

    public function donneesAnalyseAffichage(): array
    {
        if ($this->statut === StatutCv::CvRecu) {
            return [
                'score' => null,
                'nombre_matches' => null,
                'date_analyse' => null,
                'date_analyse_ts' => 0,
                'a_analyse' => false,
            ];
        }

        $r = $this->resultatAnalyse;

        return [
            'score' => $r?->score_matching,
            'nombre_matches' => $r?->nombre_matches,
            'date_analyse' => $r?->date_analyse?->format('d/m/Y H:i'),
            'date_analyse_ts' => $r?->date_analyse?->timestamp ?? 0,
            'a_analyse' => $r !== null,
        ];
    }
}
