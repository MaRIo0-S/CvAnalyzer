<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Entreprise;
use App\Models\MessageContact;
use App\Models\Poste;
use App\Support\DepotOffreSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($user = $request->user()) {
            return match ($user->role) {
                Role::Admin => redirect()->route('admin.backoffice'),
                Role::SuperAdmin => redirect()->route('super-admin.dashboard'),
                Role::SousAdmin => redirect()->route('rh.dashboard'),
                Role::Candidat => redirect()->route('candidat.statut'),
            };
        }

        return Inertia::render('Home', [
            'landingStats' => [
                ['value' => '12 400+', 'label' => 'CV reçus', 'tone' => 'cyan'],
                ['value' => '380+', 'label' => 'Postes publiés', 'tone' => 'indigo'],
                ['value' => '48', 'label' => 'Entreprises actives', 'tone' => 'emerald'],
                ['value' => '96 %', 'label' => 'Satisfaction RH', 'tone' => 'indigo'],
            ],
        ]);
    }

    public function offres(Request $request)
    {
        $q = $request->string('q')->trim();
        $entrepriseId = $request->integer('entreprise_id') ?: null;
        $tri = $request->input('tri', 'recent');

        $query = Poste::query()
            ->where('est_ouvert', true)
            ->whereHas('entreprise', fn ($e) => $e->avecRh())
            ->with('entreprise:id,nom,description');

        if ($q->isNotEmpty()) {
            $term = '%'.mb_strtolower($q).'%';
            $query->where(function ($builder) use ($term) {
                $builder->whereRaw('LOWER(titre) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$term])
                    ->orWhereHas('entreprise', fn ($e) => $e->whereRaw('LOWER(nom) LIKE ?', [$term]));
            });
        }

        if ($entrepriseId) {
            $query->where('entreprise_id', $entrepriseId);
        }

        $query = match ($tri) {
            'ancien' => $query->orderBy('date_creation'),
            'entreprise' => $query->orderBy(
                Entreprise::select('nom')->whereColumn('entreprises.id', 'postes.entreprise_id')
            ),
            default => $query->orderByDesc('date_creation'),
        };

        return Inertia::render('Home/Offres', [
            'postes' => $query->get(['id', 'titre', 'description', 'entreprise_id', 'date_creation'])
                ->map(fn (Poste $p) => [
                    'id' => $p->id,
                    'titre' => $p->titre,
                    'description' => \Illuminate\Support\Str::limit($p->description ?? '', 200),
                    'entreprise' => $p->entreprise?->nom,
                    'entreprise_id' => $p->entreprise_id,
                    'date' => $p->date_creation?->format('d/m/Y'),
                    'share_url' => route('offres.show', $p),
                ]),
            'entreprises' => Entreprise::avecRh()->orderBy('nom')->get(['id', 'nom']),
            'filters' => [
                'q' => $q->toString(),
                'entreprise_id' => $entrepriseId,
                'tri' => $tri,
            ],
        ]);
    }

    public function offreShow(Poste $poste)
    {
        if (! $poste->est_ouvert) {
            abort(404);
        }

        $poste->load('entreprise:id,nom,description');

        DepotOffreSession::autoriser($poste);

        return Inertia::render('Home/OffreShow', [
            'poste' => [
                'id' => $poste->id,
                'titre' => $poste->titre,
                'description' => $poste->description,
                'entreprise_id' => $poste->entreprise_id,
                'created_at' => $poste->date_creation?->format('d/m/Y'),
            ],
            'entreprise' => $poste->entreprise ? [
                'nom' => $poste->entreprise->nom,
                'description' => $poste->entreprise->description,
            ] : null,
            'shareUrl' => route('offres.show', $poste),
            'deposerUrl' => route('guest.deposer', ['poste_id' => $poste->id, 'entreprise_id' => $poste->entreprise_id]),
        ]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:30'],
            'entreprise' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        MessageContact::create($validated);

        return redirect()
            ->route('home')
            ->with('success', 'Merci ! Votre message a bien été envoyé. Nous vous répondrons sous 48 h ouvrées.');
    }
}
