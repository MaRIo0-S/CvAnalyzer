<?php

namespace App\Mail;

use App\Models\Cv;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CandidatAlerteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $destinataireNom,
        public string $type,
        public string $titre,
        public string $detail,
        public ?string $lien = null,
    ) {}

    public static function envoyerProfil(User $user): bool
    {
        return self::envoyer(
            email: $user->email,
            nom: $user->name,
            userId: $user->id,
            type: 'profil',
            titre: 'Votre profil a été mis à jour',
            detail: 'Vos informations de compte (nom ou e-mail) ont été modifiées. Si vous n\'êtes pas à l\'origine de ce changement, contactez-nous rapidement.',
            lien: route('account.edit', absolute: true),
        );
    }

    public static function envoyerMotDePasse(User $user): bool
    {
        return self::envoyer(
            email: $user->email,
            nom: $user->name,
            userId: $user->id,
            type: 'mot_de_passe',
            titre: 'Votre mot de passe a été modifié',
            detail: 'Le mot de passe de votre compte candidat vient d\'être changé. Si vous n\'êtes pas à l\'origine de cette action, sécurisez votre compte immédiatement.',
            lien: route('account.password.edit', absolute: true),
        );
    }

    public static function envoyerDossier(Cv $cv): bool
    {
        if (! filled($cv->email_candidat)) {
            return false;
        }

        $cv->loadMissing(['poste', 'entreprise']);
        $poste = $cv->poste?->titre ?? '—';
        $entreprise = $cv->entreprise?->nom ?? '—';

        return self::envoyer(
            email: $cv->email_candidat,
            nom: $cv->nom_candidat ?: 'Madame, Monsieur',
            userId: $cv->user_id,
            cvId: $cv->id,
            type: 'dossier',
            titre: 'Votre dossier de candidature a été modifié',
            detail: "Votre candidature pour le poste « {$poste} » chez {$entreprise} (dossier n°{$cv->id}) a été mise à jour pendant la période de modification (24 h).",
            lien: $cv->user_id
                ? route('candidat.statut', absolute: true)
                : null,
        );
    }

    private static function envoyer(
        string $email,
        string $nom,
        string $type,
        string $titre,
        string $detail,
        ?string $lien = null,
        ?int $userId = null,
        ?int $cvId = null,
    ): bool {
        try {
            if ($userId && $cvId) {
                Notification::create([
                    'cv_id' => $cvId,
                    'user_id' => $userId,
                    'message' => $titre,
                    'statut_au_moment' => $type,
                ]);
            }

            Mail::to($email)->send(new self($nom, $type, $titre, $detail, $lien));

            return true;
        } catch (\Throwable $e) {
            Log::error('E-mail alerte candidat non envoyé', [
                'type' => $type,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function envelope(): Envelope
    {
        $app = config('app.name', 'CV Analyzer');

        return new Envelope(
            subject: "{$app} — {$this->titre}",
        );
    }

    public function content(): Content
    {
        $nom = e($this->destinataireNom);
        $titre = e($this->titre);
        $detail = e($this->detail);
        $lien = $this->lien ? e($this->lien) : null;

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1e293b;max-width:560px;">';
        $html .= "<p style=\"margin:0 0 16px;\">Bonjour <strong>{$nom}</strong>,</p>";
        $html .= "<p style=\"margin:0 0 16px;\"><strong>{$titre}</strong></p>";
        $html .= "<p style=\"margin:0 0 16px;\">{$detail}</p>";
        if ($lien) {
            $html .= "<p style=\"margin:0 0 20px;\"><a href=\"{$lien}\" style=\"color:#4f46e5;\">Ouvrir mon espace candidat</a></p>";
        }
        $html .= '<p style="margin:0;font-size:13px;color:#64748b;">Cet e-mail est envoyé automatiquement. Merci de ne pas répondre directement à ce message.</p>';
        $html .= '</div>';

        return new Content(htmlString: $html);
    }
}
