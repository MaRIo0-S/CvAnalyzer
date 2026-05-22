<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        $app = config('app.name', 'CV Analyzer');

        return new Envelope(
            subject: "{$app} — Code de confirmation d'inscription",
        );
    }

    public function content(): Content
    {
        $nom = e($this->name);
        $code = e($this->code);

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1e293b;max-width:560px;">';
        $html .= "<p style=\"margin:0 0 16px;\">Bonjour <strong>{$nom}</strong>,</p>";
        $html .= '<p style="margin:0 0 16px;">Voici votre code pour confirmer la création de votre compte candidat :</p>';
        $html .= "<p style=\"margin:0 0 20px;font-size:22px;font-weight:700;letter-spacing:0.25em;color:#4f46e5;\">{$code}</p>";
        $html .= '<p style="margin:0;font-size:13px;color:#64748b;">Ce code est valable 15 minutes. Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.</p>';
        $html .= '</div>';

        return new Content(htmlString: $html);
    }
}
