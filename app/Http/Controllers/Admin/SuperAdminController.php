<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\User;
use App\Services\UserDeactivationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    public function __construct(private UserDeactivationService $deactivation) {}
    public function index()
    {
        return Inertia::render('Admin/SuperAdmins', [
            'entreprises' => Entreprise::orderBy('nom')->get(['id', 'nom']),
            'superAdmins' => User::where('role', Role::SuperAdmin)
                ->where('admin_id', auth()->id())
                ->with(['entreprise:id,nom', 'rhEquipe:id,super_admin_id,name,email,telephone,est_actif'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'telephone' => $u->telephone,
                    'est_actif' => $u->est_actif,
                    'entreprise' => $u->entreprise?->nom,
                    'entreprise_id' => $u->entreprise_id,
                    'rh_count' => $u->rhEquipe->count(),
                    'rh_actifs' => $u->rhEquipe->where('est_actif', true)->count(),
                    'created_at' => $u->created_at?->format('d/m/Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'entreprise_nom' => ['required', 'string', 'max:255'],
        ]);

        $entreprise = $this->resolveEntreprise($validated['entreprise_nom']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password' => $validated['password'],
            'role' => Role::SuperAdmin,
            'admin_id' => $request->user()->id,
            'entreprise_id' => $entreprise->id,
            'est_actif' => true,
        ]);

        return back()->with('success', 'Gérant (super-admin) ajouté pour « '.$entreprise->nom.' ».');
    }

    public function edit(User $user)
    {
        if ($user->admin_id !== auth()->id() || $user->role !== Role::SuperAdmin) {
            abort(403);
        }

        return Inertia::render('Admin/GerantEdit', [
            'gerant' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'entreprise' => $user->entreprise?->nom,
                'est_actif' => $user->est_actif,
            ],
            'entreprises' => Entreprise::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->admin_id !== auth()->id() || $user->role !== Role::SuperAdmin) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'telephone' => ['required', 'string', 'max:30'],
            'entreprise_nom' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $entreprise = $this->resolveEntreprise($validated['entreprise_nom']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'entreprise_id' => $entreprise->id,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.super-admins')
            ->with('success', 'Gérant mis à jour.');
    }

    public function toggleActif(User $user)
    {
        if ($user->admin_id !== auth()->id() || $user->role !== Role::SuperAdmin) {
            abort(403);
        }

        $activer = ! $user->est_actif;
        $user->update(['est_actif' => $activer]);

        if ($activer) {
            $this->deactivation->reactivateGerant($user);
        } else {
            $this->deactivation->deactivateGerant($user);
        }

        return back()->with(
            'success',
            $activer
                ? 'Gérant réactivé : RH et postes restaurés à leur état initial.'
                : 'Gérant désactivé : ses RH et leurs postes ont été fermés en cascade.'
        );
    }

    public function destroy(User $user)
    {
        if ($user->admin_id !== auth()->id() || $user->role !== Role::SuperAdmin) {
            abort(403);
        }

        $user->rhEquipe()->delete();
        $user->delete();

        return back()->with('success', 'Gérant et son équipe RH retirés.');
    }

    private function resolveEntreprise(string $nom): Entreprise
    {
        $nom = trim($nom);
        $existante = Entreprise::query()
            ->whereRaw('LOWER(nom) = ?', [mb_strtolower($nom)])
            ->first();

        return $existante ?? Entreprise::create(['nom' => $nom]);
    }
}
