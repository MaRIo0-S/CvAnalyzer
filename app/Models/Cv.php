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
        'importe_par_rh',
    ];

    public function numeroDossier(): int
    {
        return (int) $this->id;
    }

    protected function casts(): array
    {
        return [
            'date_depot' => 'datetime',
            'modifiable_jusqu' => 'datetime',
            'statut' => StatutCv::class,
            'taille_fichier' => 'float',
            'importe_par_rh' => 'boolean',
        ];
    }

    public function emailAffichageRh(): string
    {
        if ($this->importe_par_rh && ! filled($this->email_candidat)) {
            return 'Voir le CV pour récupérer l\'e-mail';
        }

        return $this->email_candidat ?: '—';
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

    /**
     * @return array{statut_affichage: string, statut_label: string}
     */
    public function statutPourAffichageRh(?string $decisionProvisoire, bool $lotProvisoire = false): array
    {
        if ($decisionProvisoire) {
            return [
                'statut_affichage' => self::statutAffichage($decisionProvisoire, $this->statut->value),
                'statut_label' => self::libelleAvecDecision($decisionProvisoire, $this->statut->label()),
            ];
        }

        if ($lotProvisoire) {
            return [
                'statut_affichage' => StatutCv::EnCoursAnalyse->value,
                'statut_label' => StatutCv::EnCoursAnalyse->label(),
            ];
        }

        return [
            'statut_affichage' => $this->statut->value,
            'statut_label' => $this->statut->label(),
        ];
    }

    public function pourRh(?string $decisionProvisoire = null, bool $lotProvisoire = false): array
    {
        $analyse = $this->donneesAnalyseAffichage($lotProvisoire);
        $statut = $this->statut->value;
        $affichage = $this->statutPourAffichageRh($decisionProvisoire, $lotProvisoire);

        return [
            'id' => $this->id,
            'numero_dossier' => $this->numeroDossier(),
            'poste_id' => $this->poste_id,
            'nom_candidat' => $this->nom_candidat ?: 'Candidat #'.$this->id,
            'email_candidat' => $this->email_candidat,
            'email_affichage' => $this->emailAffichageRh(),
            'importe_par_rh' => (bool) $this->importe_par_rh,
            'poste' => $this->poste?->titre,
            'statut' => $statut,
            'statut_affichage' => $affichage['statut_affichage'],
            'statut_label' => $affichage['statut_label'],
            'decision_provisoire' => $decisionProvisoire,
            'date_depot' => $this->date_depot?->format('d/m/Y H:i'),
            'date_depot_ts' => $this->date_depot?->timestamp ?? 0,
            'format_fichier' => $this->format_fichier,
            'score' => $analyse['score'],
            'nombre_matches' => $analyse['nombre_matches'],
            'mots_cles_matches' => ($this->statut === StatutCv::CvRecu && ! $lotProvisoire)
                ? []
                : ($this->resultatAnalyse?->mots_cles_matches ?? []),
            'date_analyse' => $analyse['date_analyse'],
            'date_analyse_ts' => $analyse['date_analyse_ts'],
            'download_url' => route('rh.cv.telecharger', $this),
            'modifiable_par_candidat' => $this->statut === StatutCv::CvRecu && $this->peutModifier(),
            'pret_premiere_analyse' => $this->statut === StatutCv::CvRecu && $this->pretPourAnalyse(),
        ];
    }

    public function donneesAnalyseAffichage(bool $lotProvisoire = false): array
    {
        if ($this->statut === StatutCv::CvRecu && ! $lotProvisoire) {
            return [
                'score' => null,
                'nombre_matches' => null,
                'date_analyse' => null,
                'date_analyse_ts' => 0,
            ];
        }

        $r = $this->resultatAnalyse;

        return [
            'score' => $r?->score_matching,
            'nombre_matches' => $r?->nombre_matches,
            'date_analyse' => $r?->date_analyse?->format('d/m/Y H:i'),
            'date_analyse_ts' => $r?->date_analyse?->timestamp ?? 0,
        ];
    }
}
