<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageContactController extends Controller
{
    public function index(): Response
    {
        $messages = MessageContact::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MessageContact $m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'email' => $m->email,
                'entreprise' => $m->entreprise,
                'message' => $m->message,
                'lu' => $m->lu,
                'recu_le' => $m->created_at?->format('d/m/Y H:i'),
                'recu_at' => $m->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/MessagesContact', [
            'messages' => $messages,
            'stats' => [
                'total' => $messages->count(),
                'non_lus' => $messages->where('lu', false)->count(),
            ],
        ]);
    }

    public function marquerLu(MessageContact $messageContact): RedirectResponse
    {
        $messageContact->update(['lu' => true]);

        return back()->with('success', 'Message marqué comme lu.');
    }
}
