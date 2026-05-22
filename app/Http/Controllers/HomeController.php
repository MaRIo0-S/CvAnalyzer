<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($user = $request->user()) {
            return match ($user->role) {
                Role::Admin => redirect()->route('admin.subadmins'),
                Role::SousAdmin => redirect()->route('rh.dashboard'),
                Role::Candidat => redirect()->route('candidat.statut'),
            };
        }

        return Inertia::render('Home');
    }
}
