<?php

namespace App\Mail;

use App\Enums\StatutCv;
use App\Models\Cv;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StatutCandidatureMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cv $cv,
        public StatutCv $statut,
    ) {}

    public static function envoyer(Cv $cv, StatutCv $statut): bool
    {
        if (! filled($cv->email_candidat)) {
            return false;
        }

        $cv->loadMissing(['poste', 'entreprise']);

        try {
            if ($cv->user_id) {
                Notification::create([
                    'cv_id' => $cv->id,
                    'user_id' => $cv->user_id,
                    'message' => self::messageCourt($statut),
                    'statut_au_moment' => $statut->value,
                ]);
            }

            Mail::to($cv->email_candidat)->send(new self($cv, $statut));

            return true;
        } catch (\Throwable $e) {
            Log::error('E-mail statut candidature non envoyé', [
                'cv_id' => $cv->id,
                'statut' => $statut->value,
                'email' => $cv->email_candidat,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function envoyerSiChange(Cv $cv, StatutCv $ancien, StatutCv $nouveau): bool
    {
        if ($ancien === $nouveau) {
            return false;
        }

        return self::envoyer($cv, $nouveau);
    }

    public function envelope(): Envelope
    {
        $app = config('app.name', 'CV Analyzer');
        $poste = $this->cv->poste?->titre ?? 'votre candidature';

        $sujet = match ($this->statut) {
            StatutCv::CvRecu => "{$app} — Candidature reçue · {$poste}",
            StatutCv::EnCoursAnalyse => "{$app} — Dossier en cours d'examen · {$poste}",
            StatutCv::Valide => "{$app} — Candidature retenue · {$poste}",
            StatutCv::NonValide => "{$app} — Suite à votre candidature · {$poste}",
        };

        return new Envelope(subject: $sujet);
    }

    public function content(): Content
    {
        $nom = e($this->cv->nom_candidat ?: 'Madame, Monsieur');
        $poste = e($this->cv->poste?->titre ?? '—');
        $entreprise = e($this->cv->entreprise?->nom ?? '—');
        $statut = e($this->statut->label());
        $dossier = e((string) $this->cv->id);
        $date = e($this->cv->date_depot?->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i'));
        $lien = route('candidat.statut', absolute: true);
        $detail = e(self::messageDetail($this->statut));

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1e293b;max-width:560px;">';
        $html .= "<p style=\"margin:0 0 16px;\">Bonjour <strong>{$nom}</strong>,</p>";
        $html .= '<p style="margin:0 0 16px;">Nous vous informons d\'une mise à jour concernant votre candidature.</p>';
        $html .= '<table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">';
        $html .= "<tr><td style=\"padding:8px 0;color:#64748b;\">Entreprise</td><td style=\"padding:8px 0;\"><strong>{$entreprise}</strong></td></tr>";
        $html .= "<tr><td style=\"padding:8px 0;color:#64748b;\">Poste</td><td style=\"padding:8px 0;\"><strong>{$poste}</strong></td></tr>";
        $html .= "<tr><td style=\"padding:8px 0;color:#64748b;\">N° dossier</td><td style=\"padding:8px 0;\">{$dossier}</td></tr>";
        $html .= "<tr><td style=\"padding:8px 0;color:#64748b;\">Dépôt</td><td style=\"padding:8px 0;\">{$date}</td></tr>";
        $html .= "<tr><td style=\"padding:8px 0;color:#64748b;\">Statut actuel</td><td style=\"padding:8px 0;\"><strong>{$statut}</strong></td></tr>";
        $html .= '</table>';
        $html .= "<p style=\"margin:0 0 16px;\">{$detail}</p>";
        $html .= "<p style=\"margin:0 0 20px;\"><a href=\"{$lien}\" style=\"color:#4f46e5;\">Ouvrir ma page candidature</a> (suivi du statut en ligne)</p>";
        $html .= '<p style="margin:0;font-size:13px;color:#64748b;">Message envoyé automatiquement.</p>';
        $html .= '</div>';

        return new Content(htmlString: $html);
    }

    private static function messageDetail(StatutCv $statut): string
    {
        return match ($statut) {
            StatutCv::CvRecu => 'Votre CV a bien été enregistré. Vous pouvez encore le modifier pendant 24 h depuis votre espace candidat, avant le début de l\'analyse par le recruteur.',
            StatutCv::EnCoursAnalyse => 'Votre dossier est maintenant examiné par l\'équipe recrutement. Vous serez informé par e-mail dès qu\'une décision sera prise.',
            StatutCv::Valide => 'Félicitations, votre profil a été retenu. Le recruteur pourra vous contacter pour la suite du processus.',
            StatutCv::NonValide => 'Après étude de votre dossier, votre candidature n\'a pas été retenue pour ce poste. Nous vous remercions pour l\'intérêt porté à notre entreprise.',
        };
    }

    public static function messageCourt(StatutCv $statut): string
    {
        return match ($statut) {
            StatutCv::EnCoursAnalyse => 'Votre dossier est en cours d\'examen par le recruteur.',
            StatutCv::Valide => 'Votre candidature a été retenue.',
            StatutCv::NonValide => 'Votre candidature n\'a pas été retenue.',
            StatutCv::CvRecu => 'Votre CV a bien été reçu et enregistré.',
        };
    }
}
