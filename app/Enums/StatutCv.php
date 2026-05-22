<?php

namespace App\Enums;

enum StatutCv: string
{
    case CvRecu = 'cv_recu';
    case EnCoursAnalyse = 'en_cours_analyse';
    case Valide = 'valide';
    case NonValide = 'non_valide';

    public function label(): string
    {
        return match ($this) {
            self::CvRecu => 'CV reçu',
            self::EnCoursAnalyse => 'En cours d\'analyse',
            self::Valide => 'Validé',
            self::NonValide => 'Non validé',
        };
    }
}
