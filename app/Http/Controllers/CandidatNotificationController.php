<?php

namespace App\Http\Controllers;

use App\Enums\StatutCv;
use App\Models\Notification;
use Illuminate\Http\Request;

class CandidatNotificationController extends Controller
{
    public function marquerToutLu(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        $request->session()->forget(['success', 'error', 'info']);

        if ($request->header('X-Inertia')) {
            return back();
        }

        if ($request->expectsJson()) {
            return response()->json($this->payloadNotifications($user->id));
        }

        return back();
    }

    private function payloadNotifications(int $userId): array
    {
        $items = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'message' => $n->message,
                'statut_label' => StatutCv::tryFrom($n->statut_au_moment)?->label() ?? 'Compte',
                'lu' => (bool) $n->lu,
                'date' => $n->created_at?->format('d/m/Y H:i'),
            ]);

        return [
            'unread_count' => Notification::where('user_id', $userId)->where('lu', false)->count(),
            'items' => $items->values()->all(),
        ];
    }
}