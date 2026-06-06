<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Notification;
use App\Support\CandidatureSession;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name', 'CV Analyzer'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                    'entreprise' => $request->user()->entreprise?->nom,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'candidatNotifications' => fn () => $this->candidatNotifications($request),
            'candidatureEnCours' => fn () => CandidatureSession::enCours($request),
            'staffPaths' => [
                'admin' => '/'.trim(config('cvanalyzer.admin_app_prefix'), '/'),
                'gerant' => '/'.trim(config('cvanalyzer.gerant_app_prefix'), '/'),
            ],
        ];
    }

    private function candidatNotifications(Request $request): ?array
    {
        $user = $request->user();
        if (! $user || $user->role !== Role::Candidat) {
            return null;
        }

        $items = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'message' => $n->message,
                'statut_label' => \App\Enums\StatutCv::tryFrom($n->statut_au_moment)?->label() ?? 'Compte',
                'lu' => (bool) $n->lu,
                'date' => $n->created_at?->format('d/m/Y H:i'),
            ]);

        return [
            'unread_count' => Notification::where('user_id', $user->id)->where('lu', false)->count(),
            'items' => $items->values()->all(),
        ];
    }
}
