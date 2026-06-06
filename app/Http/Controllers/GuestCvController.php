<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Mail\StatutCandidatureMail;
use App\Support\CandidatureSession;
use App\Support\DepotOffreSession;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\Poste;
use App\Services\ServiceAnalyse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GuestCvController extends Controller
{
    public function __construct(private ServiceAnalyse $serviceAnalyse) {}

    public function create(Request $request)
    {
        if ($request->user()?->role === Role::SousAdmin) {
            abort(403);
        }

        $cvModifiable = $this->cvModifiablePourSession($request);

        if ($cvModifiable) {
            $posteId = $request->integer('poste_id') ?: (int) $cvModifiable['poste_id'];
            if ($request->integer('poste_id') && ! DepotOffreSession::estAutorise($request, $posteId)) {
                return redirect()
                    ->route('offres.index')
                    ->with('info', 'Consultez d\'abord l\'offre choisie, puis revenez modifier votre candidature.');
            }

            $poste = Poste::where('est_ouvert', true)->findOrFail($posteId);
            $cvModifiable['poste_id'] = $poste->id;
            $cvModifiable['entreprise_id'] = $poste->entreprise_id;
            $cvModifiable['poste'] = $poste->titre;
            $cvModifiable['entreprise'] = $poste->entreprise?->nom;

            return Inertia::render('Guest/Deposer', [
                'pageMode' => 'modify',
                'prefill' => null,
                'champsVerrouilles' => false,
                'entreprises' => Entreprise::avecRh()->orderBy('nom')->get(['id', 'nom', 'description']),
                'postes' => Poste::where('est_ouvert', true)
                    ->whereHas('entreprise', fn ($q) => $q->avecRh())
                    ->orderBy('titre')
                    ->get(['id', 'titre', 'description', 'entreprise_id']),
                'graceHours' => config('cv.grace_period_hours'),
                'cvModifiable' => $cvModifiable,
                'isLoggedIn' => (bool) $request->user(),
                'userDefaults' => $request->user() ? [
                    'nom_candidat' => $request->user()->name,
                    'email_candidat' => $request->user()->email,
                ] : null,
            ]);
        }

        $posteId = $request->integer('poste_id') ?: DepotOffreSession::posteId($request);

        if (! DepotOffreSession::estAutorise($request, $posteId)) {
            return redirect()
                ->route('offres.index')
                ->with('info', 'Choisissez une offre, consultez-la, puis déposez votre CV depuis cette page.');
        }

        $poste = Poste::where('est_ouvert', true)->findOrFail($posteId);
        $prefill = [
            'poste_id' => $poste->id,
            'entreprise_id' => $poste->entreprise_id,
            'poste_titre' => $poste->titre,
            'entreprise_nom' => $poste->entreprise?->nom,
        ];

        return Inertia::render('Guest/Deposer', [
            'pageMode' => 'deposit',
            'prefill' => $prefill,
            'champsVerrouilles' => true,
            'entreprises' => Entreprise::avecRh()->orderBy('nom')->get(['id', 'nom', 'description']),
            'postes' => Poste::where('est_ouvert', true)
                ->whereHas('entreprise', fn ($q) => $q->avecRh())
                ->orderBy('titre')
                ->get(['id', 'titre', 'description', 'entreprise_id']),
            'graceHours' => config('cv.grace_period_hours'),
            'cvModifiable' => $this->cvModifiablePourSession($request),
            'isLoggedIn' => (bool) $request->user(),
            'userDefaults' => $request->user() ? [
                'nom_candidat' => $request->user()->name,
                'email_candidat' => $request->user()->email,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $dernierCv = $user->cvs()->orderBy('date_depot', 'desc')->first();
            if ($dernierCv && $dernierCv->peutModifier()) {
                return back()->withErrors([
                    'depot' => 'Vous avez déjà un CV en cours : vous pouvez le modifier pendant 24 h après le dépôt. Ensuite vous pourrez en déposer un nouveau.',
                ]);
            }
        }

        if (! $user && $this->cvModifiablePourSession($request)) {
            return back()->withErrors([
                'depot' => 'Vous avez déjà une candidature en cours. Modifiez-la dans la section ci-dessus.',
            ]);
        }

        $validated = $request->validate([
            'nom_candidat' => ['required', 'string', 'max:255'],
            'email_candidat' => ['required', 'email', 'max:255'],
            'poste_id' => ['required', 'exists:postes,id'],
            'entreprise_id' => [
                'required',
                Rule::exists('entreprises', 'id')->where(
                    fn ($q) => $q->whereIn(
                        'id',
                        Entreprise::avecRh()->select('id')
                    )
                ),
            ],
            'fichier' => ['required', 'file', 'max:5120'],
        ]);

        if ($user) {
            $validated['email_candidat'] = $user->email;
            $validated['nom_candidat'] = $user->name;
        }

        $poste = Poste::findOrFail($validated['poste_id']);
        if ((int) $poste->entreprise_id !== (int) $validated['entreprise_id']) {
            return back()->withErrors(['entreprise_id' => 'Ce poste n\'appartient pas à cette entreprise.']);
        }

        if (! $poste->est_ouvert) {
            return back()->withErrors(['poste_id' => 'Ce poste n\'accepte plus de candidatures.']);
        }

        $file = $request->file('fichier');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! $this->serviceAnalyse->verifierFormat($extension)) {
            return back()->withErrors(['fichier' => 'Format non accepté. Utilisez PDF, DOC ou DOCX.']);
        }

        $tailleMo = $file->getSize() / 1024 / 1024;
        if (! $this->serviceAnalyse->verifierTaille($tailleMo)) {
            return back()->withErrors(['fichier' => 'Fichier trop volumineux (max 5 Mo).']);
        }

        $graceHours = (int) config('cv.grace_period_hours', 24);

        $cv = Cv::create([
            'poste_id' => $validated['poste_id'],
            'entreprise_id' => $validated['entreprise_id'],
            'user_id' => $user?->id,
            'nom_candidat' => $validated['nom_candidat'],
            'email_candidat' => $validated['email_candidat'],
            'fichier_url' => $file->store('cvs', 'public'),
            'taille_fichier' => round($tailleMo, 2),
            'format_fichier' => $extension,
            'statut' => StatutCv::CvRecu,
            'date_depot' => now(),
            'modifiable_jusqu' => now()->addHours($graceHours),
        ]);

        $this->serviceAnalyse->persisterTexteExtrait($cv->fresh());

        if (! $user) {
            $request->session()->put('guest_cv_id', $cv->id);
            $request->session()->put('guest_depot_count', (int) $request->session()->get('guest_depot_count', 0) + 1);
        }

        $message = "CV déposé (dossier n°{$cv->id}). {$graceHours} h pour modifier votre dossier.";

        $mailEnvoye = StatutCandidatureMail::envoyer($cv->fresh(), StatutCv::CvRecu);
        if ($mailEnvoye) {
            $message .= ' Un e-mail de confirmation a été envoyé.';
        } elseif (filled($cv->email_candidat) && config('mail.default', 'log') === 'log') {
            $message .= ' (E-mail non expédié : configurez SMTP dans .env — MAIL_MAILER=smtp, etc.)';
        } elseif (! $user) {
            $message .= ' Sans compte : pas de suivi en ligne ; conservez votre n° de dossier.';
        }

        return redirect()->route('guest.deposer')->with('success', $message);
    }

    public function update(Request $request, Cv $cv)
    {
        if ($cv->statut !== StatutCv::CvRecu) {
            return back()->withErrors(['depot' => 'Ce dossier ne peut plus être modifié (analyse ou décision déjà en cours).']);
        }

        if (! $cv->peutModifier()) {
            return back()->withErrors(['depot' => 'La période de modification (24 h) est terminée.']);
        }

        if (! $this->peutAccederAuCv($request, $cv)) {
            abort(403);
        }

        $validated = $request->validate([
            'nom_candidat' => ['required', 'string', 'max:255'],
            'email_candidat' => ['required', 'email', 'max:255'],
            'poste_id' => ['required', 'exists:postes,id'],
            'entreprise_id' => [
                'required',
                Rule::exists('entreprises', 'id')->where(
                    fn ($q) => $q->whereIn(
                        'id',
                        Entreprise::avecRh()->select('id')
                    )
                ),
            ],
            'fichier' => ['nullable', 'file', 'max:5120'],
        ]);

        $user = $request->user();
        if ($user) {
            $validated['email_candidat'] = $user->email;
            $validated['nom_candidat'] = $user->name;
        }

        $poste = Poste::findOrFail($validated['poste_id']);
        if ((int) $poste->entreprise_id !== (int) $validated['entreprise_id']) {
            return back()->withErrors(['entreprise_id' => 'Ce poste n\'appartient pas à cette entreprise.']);
        }

        $data = [
            'nom_candidat' => $validated['nom_candidat'],
            'email_candidat' => $validated['email_candidat'],
            'poste_id' => $validated['poste_id'],
            'entreprise_id' => $validated['entreprise_id'],
        ];

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! $this->serviceAnalyse->verifierFormat($extension)) {
                return back()->withErrors(['fichier' => 'Format non accepté.']);
            }

            $tailleMo = $file->getSize() / 1024 / 1024;
            if (! $this->serviceAnalyse->verifierTaille($tailleMo)) {
                return back()->withErrors(['fichier' => 'Fichier trop volumineux (max 5 Mo).']);
            }

            Storage::disk('public')->delete($cv->fichier_url);
            $data['fichier_url'] = $file->store('cvs', 'public');
            $data['taille_fichier'] = round($tailleMo, 2);
            $data['format_fichier'] = $extension;
            $cv->resultatAnalyse()?->delete();
        }

        $cv->update($data);

        if ($request->hasFile('fichier')) {
            $this->serviceAnalyse->persisterTexteExtrait($cv->fresh());
        }

        return back()->with('success', 'Candidature mise à jour. Modifiable jusqu\'au '.$cv->modifiable_jusqu->format('d/m/Y H:i').'.');
    }

    private function peutAccederAuCv(Request $request, Cv $cv): bool
    {
        $user = $request->user();

        if ($user && $cv->user_id === $user->id) {
            return true;
        }

        return (int) $request->session()->get('guest_cv_id') === $cv->id;
    }

    private function cvModifiablePourSession(Request $request): ?array
    {
        $base = CandidatureSession::enCours($request);
        if (! $base) {
            return null;
        }

        $cv = Cv::find($base['id']);
        if (! $cv || ! $this->peutAccederAuCv($request, $cv)) {
            return null;
        }

        return [
            ...$base,
            'nom_candidat' => $cv->nom_candidat,
            'email_candidat' => $cv->email_candidat,
            'entreprise_id' => $cv->entreprise_id,
            'poste_id' => $cv->poste_id,
        ];
    }
}
