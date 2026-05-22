<?php

namespace App\Mail;

use App\Models\Cv;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReanalyseCvMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Cv $cv) {}

    public function envelope(): Envelope
    {
        $app = config('app.name', 'CV Analyzer');
        $poste = $this->cv->poste?->titre ?? 'votre candidature';

        return new Envelope(
            subject: "{$app} — Réanalyse de votre candidature · {$poste}",
        );
    }

    public function content(): Content
    {
        $this->cv->loadMissing(['poste', 'entreprise']);
        $nom = e($this->cv->nom_candidat ?: 'Madame, Monsieur');
        $poste = e($this->cv->poste?->titre ?? '—');
        $lien = route('candidat.statut', absolute: true);

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1e293b;max-width:560px;">';
        $html .= "<p style=\"margin:0 0 16px;\">Bonjour <strong>{$nom}</strong>,</p>";
        $html .= "<p style=\"margin:0 0 16px;\">Votre dossier pour le poste <strong>{$poste}</strong> va être <strong>réanalysé</strong> avec de nouveaux critères. Vous serez informé en cas de changement de statut.</p>";
        $html .= "<p style=\"margin:0;\"><a href=\"{$lien}\" style=\"color:#4f46e5;\">Suivre ma candidature</a></p>";
        $html .= '</div>';

        return new Content(htmlString: $html);
    }
}
