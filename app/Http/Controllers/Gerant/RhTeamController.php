<?php

namespace App\Http\Controllers\Gerant;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Poste;
use App\Models\User;
use App\Services\UserDeactivationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RhTeamController extends Controller
{
    public function __construct(private UserDeactivationService $deactivation) {}

    public function index(Request $request)
    {
        $gerant = $request->user();

        return Inertia::render('Gerant/RhTeam', [
            'entreprise' => $gerant->entreprise?->only(['id', 'nom']),
            'rhList' => User::where('role', Role::SousAdmin)
                ->where('super_admin_id', $gerant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'telephone', 'est_actif', 'created_at'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'telephone' => $u->telephone,
                    'est_actif' => $u->est_actif,
                    'created_at' => $u->created_at?->format('d/m/Y'),
                    'postes_count' => Poste::where('user_id', $u->id)->count(),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $gerant = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password' => $validated['password'],
            'role' => Role::SousAdmin,
            'super_admin_id' => $gerant->id,
            'entreprise_id' => $gerant->entreprise_id,
            'est_actif' => true,
        ]);

        return back()->with('success', 'Compte RH ajouté.');
    }

    public function edit(User $rh)
    {
        $this->authorizeRh($rh);
        $gerant = auth()->user();

        return Inertia::render('Gerant/RhEdit', [
            'entreprise' => $gerant->entreprise?->only(['id', 'nom']),
            'rh' => [
                'id' => $rh->id,
                'name' => $rh->name,
                'email' => $rh->email,
                'telephone' => $rh->telephone,
                'est_actif' => $rh->est_actif,
                'created_at' => $rh->created_at?->format('d/m/Y'),
            ],
        ]);
    }

    public function update(Request $request, User $rh)
    {
        $this->authorizeRh($rh);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$rh->id],
            'telephone' => ['required', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $rh->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $rh->password = $validated['password'];
        }

        $rh->save();

        return redirect()
            ->route('gerant.rh.index')
            ->with('success', 'Profil RH mis à jour.');
    }

    public function toggleActif(User $rh)
    {
        $this->authorizeRh($rh);

        $activer = ! $rh->est_actif;
        $rh->update(['est_actif' => $activer]);

        if ($activer) {
            $this->deactivation->reactivateRh($rh);
        } else {
            $this->deactivation->deactivateRh($rh);
        }

        $msg = $activer
            ? 'RH réactivé : postes restaurés à leur état initial.'
            : 'RH désactivé : tous ses postes ont été fermés.';

        return back()->with('success', $msg);
    }

    public function destroy(User $rh)
    {
        $this->authorizeRh($rh);
        $rh->delete();

        return back()->with('success', 'Compte RH supprimé.');
    }

    private function authorizeRh(User $rh): void
    {
        if ($rh->super_admin_id !== auth()->id() || $rh->role !== Role::SousAdmin) {
            abort(403);
        }
    }
}
