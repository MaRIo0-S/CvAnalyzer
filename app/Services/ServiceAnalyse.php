<?php

namespace App\Services;

use App\Enums\StatutCv;
use App\Mail\StatutCandidatureMail;
use App\Models\Cv;
use App\Models\ResultatAnalyse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class ServiceAnalyse
{
    public function verifierFormat(string $extension): bool
    {
        return in_array(strtolower($extension), config('cv.allowed_extensions'), true);
    }

    public function verifierTaille(float $tailleMo): bool
    {
        return $tailleMo <= (float) config('cv.max_file_size_mb');
    }

    public function extraireTexte(Cv $cv): string
    {
        if (! filled($cv->fichier_url) || ! Storage::disk('public')->exists($cv->fichier_url)) {
            return '';
        }

        $path = Storage::disk('public')->path($cv->fichier_url);
        $format = strtolower($cv->format_fichier);

        if ($format === 'pdf') {
            return $this->extrairePdf($path);
        }
        if ($format === 'docx') {
            return $this->extraireDocx($path);
        }
        if ($format === 'doc') {
            return $this->extraireDocBasique($path);
        }

        return '';
    }

    public function calculerScore(string $texte, array $mots): array
    {
        $texteNormalise = $this->normaliserTexte($texte);
        $matches = [];

        foreach ($mots as $motBrut) {
            $motNormalise = $this->normaliserTexte(trim($motBrut));
            if ($motNormalise === '') {
                continue;
            }
            if (str_contains($texteNormalise, $motNormalise)) {
                $matches[] = trim($motBrut);
            }
        }

        $nombre = count($matches);
        $totalMots = count(array_filter($mots, fn ($m) => trim($m) !== ''));
        $score = $totalMots > 0 ? round(($nombre / $totalMots) * 100, 2) : 0;

        return [
            'score' => $score,
            'matches' => $matches,
            'nombre' => $nombre,
        ];
    }

    public function analyser(Cv $cv, array $mots, bool $notifier = true): ResultatAnalyse
    {
        $ancienStatut = $cv->statut;

        if ($notifier && $ancienStatut !== StatutCv::EnCoursAnalyse) {
            $cv->update([
                'statut' => StatutCv::EnCoursAnalyse,
                'modifiable_jusqu' => now(),
            ]);
            StatutCandidatureMail::envoyerSiChange($cv, $ancienStatut, StatutCv::EnCoursAnalyse);
        }

        $texte = $this->persisterTexteExtrait($cv);

        $resultat = $this->calculerScore($texte, $mots);

        $analyse = ResultatAnalyse::updateOrCreate(
            ['cv_id' => $cv->id],
            [
                'score_matching' => $resultat['score'],
                'mots_cles_matches' => $resultat['matches'],
                'nombre_matches' => $resultat['nombre'],
                'date_analyse' => now(),
            ]
        );

        return $analyse;
    }

    public function analyserCollection($cvs, array $mots, bool $notifier = true): void
    {
        foreach ($cvs as $cv) {
            $this->analyser($cv, $mots, $notifier);
        }
    }

    private function normaliserTexte(string $texte): string
    {
        return Str::ascii(mb_strtolower($texte));
    }

    private function extrairePdf(string $path): string
    {
        try {
            $parser = new PdfParser;

            return $parser->parseFile($path)->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function extraireDocx(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $texte = strip_tags(str_replace('<', ' <', $xml));

        return html_entity_decode(preg_replace('/\s+/', ' ', $texte) ?? '');
    }

    private function extraireDocBasique(string $path): string
    {
        $contenu = @file_get_contents($path);
        if ($contenu === false) {
            return '';
        }

        $texte = preg_replace('/[^\x20-\x7E\x0A\xC0-\xFF]/', ' ', $contenu);

        return trim(preg_replace('/\s+/', ' ', $texte ?? ''));
    }

    public function persisterTexteExtrait(Cv $cv): string
    {
        $texte = trim($this->extraireTexte($cv));

        if ($texte === '' && filled(trim((string) $cv->texte_extrait))) {
            return trim($cv->texte_extrait);
        }

        if ($texte !== '') {
            $cv->update(['texte_extrait' => $texte]);
        }

        return $texte;
    }
}
