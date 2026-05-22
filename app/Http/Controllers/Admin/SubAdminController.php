<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubAdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/SubAdmins', [
            'sousAdmins' => User::where('role', Role::SousAdmin)
                ->where('admin_id', auth()->id())
                ->with('entreprise:id,nom')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'entreprise_id', 'created_at']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'entreprise_nom' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'e-mail est obligatoire.',
            'email.email' => 'L\'e-mail n\'est pas valide.',
            'email.unique' => 'Cet e-mail est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'entreprise_nom.required' => 'Le nom de l\'entreprise est obligatoire.',
        ]);

        $entreprise = $this->resolveEntreprise($validated['entreprise_nom']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => Role::SousAdmin,
            'admin_id' => $request->user()->id,
            'entreprise_id' => $entreprise->id,
        ]);

        return back()->with('success', 'Sub-admin ajouté et rattaché à « '.$entreprise->nom.' ».');
    }

    private function resolveEntreprise(string $nom): Entreprise
    {
        $nom = trim($nom);

        $existante = Entreprise::query()
            ->whereRaw('LOWER(nom) = ?', [mb_strtolower($nom)])
            ->first();

        if ($existante) {
            return $existante;
        }

        return Entreprise::create(['nom' => $nom]);
    }

    public function destroy(User $user)
    {
        if ($user->admin_id !== auth()->id() || $user->role !== Role::SousAdmin) {
            abort(403);
        }

        $user->delete();

        return back()->with('success', 'Sub-admin retiré.');
    }
}
